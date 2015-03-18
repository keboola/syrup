<?php
/**
 * Created by PhpStorm.
 * User: mirocillik
 * Date: 05/11/13
 * Time: 13:37
 */

namespace Keboola\Syrup\Command;

use Doctrine\DBAL\Connection;
use Keboola\Encryption\EncryptorInterface;
use Keboola\Syrup\Exception\MaintenanceException;
use Keboola\Syrup\Job\ExecutorFactory;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Keboola\Syrup\Exception\JobException;
use Keboola\Syrup\Exception\SyrupExceptionInterface;
use Keboola\Syrup\Exception\UserException;
use Keboola\StorageApi\Client as SapiClient;
use Keboola\Syrup\Job\Exception\InitializationException;
use Keboola\Syrup\Job\ExecutorInterface;
use Keboola\Syrup\Job\HookExecutorInterface;
use Keboola\Syrup\Job\Metadata\Job;
use Keboola\Syrup\Service\Db\Lock;
use Keboola\Syrup\Elasticsearch\JobMapper;

class JobCommand extends ContainerAwareCommand
{
    const STATUS_SUCCESS = 0;
    const STATUS_ERROR = 1;
    const STATUS_LOCK = 64;
    const STATUS_RETRY = 65;

    /** @var Job */
    protected $job;

    /** @var JobMapper */
    protected $jobMapper;

    /** @var SapiClient */
    protected $sapiClient;

    /** @var Logger */
    protected $logger;

    /** @var Lock */
    protected $lock;

    protected function configure()
    {
        $this
            ->setName('syrup:job:run')
            ->setAliases(['syrup:run-job'])
            ->setDescription('Command to execute jobs')
            ->addArgument('jobId', InputArgument::REQUIRED, 'ID of the job')
        ;
    }

    protected function init($jobId)
    {
        $this->jobMapper = $this->getContainer()->get('syrup.elasticsearch.current_component_job_mapper');

        // Get job from ES
        $this->job = $this->jobMapper->get($jobId);

        if ($this->job == null) {
            $this->logger->error("Job id '".$jobId."' not found.");
            return self::STATUS_ERROR;
        }

        // SAPI init
        /** @var EncryptorInterface $encryptor */
        $encryptor = $this->getContainer()->get('syrup.encryptor');

        $this->sapiClient = new SapiClient([
            'token' => $encryptor->decrypt($this->job->getToken()['token']),
            'url' => $this->getContainer()->getParameter('storage_api.url'),
            'userAgent' => $this->job->getComponent(),
        ]);
        $this->sapiClient->setRunId($this->job->getRunId());

        /** @var \Keboola\Syrup\Service\StorageApi\StorageApiService $storageApiService */
        $storageApiService = $this->getContainer()->get('syrup.storage_api');
        $storageApiService->setClient($this->sapiClient);

        /** @var \Keboola\Syrup\Monolog\Handler\StorageApiHandler $logHandler */
        $logHandler = $this->getContainer()->get('syrup.monolog.sapi_handler');
        $logHandler->setStorageApiClient($this->sapiClient);

        /** @var \Keboola\Syrup\Monolog\Processor\JobProcessor $logProcessor */
        $logProcessor = $this->getContainer()->get('syrup.monolog.job_processor');
        $logProcessor->setJob($this->job);

        /** @var \Keboola\Syrup\Monolog\Processor\SyslogProcessor $logProcessor */
        $logProcessor = $this->getContainer()->get('syrup.monolog.syslog_processor');
        $logProcessor->setRunId($this->job->getRunId());
        $logProcessor->setTokenData($this->sapiClient->getLogData());


        // Lock DB
        /** @var Connection $conn */
        $conn = $this->getContainer()->get('doctrine.dbal.lock_connection');
        $conn->exec('SET wait_timeout = 31536000;');
        $this->lock = new Lock($conn, $this->job->getLockName());
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jobId = $input->getArgument('jobId');

        $this->logger = $this->getContainer()->get('logger');

        try {
            $this->init($jobId);
        } catch (\Exception $e) {
            // Initialization error -> job will be requeued
            $this->logException('error', $e);

            // Don't update job status or result -> error could be related to ES
            // Don't unlock DB, error happened either before lock creation or when creating the lock,
            // so the DB isn't locked

            return self::STATUS_RETRY;
        }

        if (is_null($jobId)) {
            throw new UserException("Missing jobId argument.");
        }

        if (!$this->lock->lock()) {
            return self::STATUS_LOCK;
        }

        $startTime = time();

        // Update job status to 'processing'
        $this->job->setStatus(Job::STATUS_PROCESSING);
        $this->job->setStartTime(date('c', $startTime));
        $this->job->setEndTime(null);
        $this->job->setDurationSeconds(null);
        $this->job->setResult(null);
        $this->job->setProcess([
            'host'  => gethostname(),
            'pid'   => getmypid()
        ]);

        $this->jobMapper->update($this->job);

        // Instantiate jobExecutor based on component name
        /** @var ExecutorFactory $jobExecutorFactory */
        $jobExecutorFactory = $this->getContainer()->get('syrup.job_executor_factory');

        /** @var ExecutorInterface $jobExecutor */
        $jobExecutor = $jobExecutorFactory->create($this->job);

        // Execute job
        try {
            $jobResult = $jobExecutor->execute($this->job);
            $jobStatus = Job::STATUS_SUCCESS;
            $status = self::STATUS_SUCCESS;
        } catch (InitializationException $e) {
            // job will be requeued
            $exceptionId = $this->logException('error', $e);
            $jobResult = [
                'message'       => $e->getMessage(),
                'exceptionId'   => $exceptionId
            ];
            $jobStatus = Job::STATUS_PROCESSING;
            $status = self::STATUS_RETRY;

        } catch (MaintenanceException $e) {
            $jobResult = [];
            $jobStatus = Job::STATUS_WAITING;
            $status = self::STATUS_LOCK;
        } catch (UserException $e) {
            $exceptionId = $this->logException('error', $e);
            $jobResult = [
                'message'       => $e->getMessage(),
                'exceptionId'   => $exceptionId
            ];
            $jobStatus = Job::STATUS_ERROR;
            $status = self::STATUS_SUCCESS;

        } catch (JobException $e) {
            $logLevel = 'error';
            if ($e->getStatus() === Job::STATUS_WARNING) {
                $logLevel = Job::STATUS_WARNING;
            }

            $exceptionId = $this->logException($logLevel, $e);
            $jobResult = [
                'message'       => $e->getMessage(),
                'exceptionId'   => $exceptionId
            ];

            if ($e->getResult()) {
                $jobResult += $e->getResult();
            }

            $jobStatus = $e->getStatus();
            $status = self::STATUS_SUCCESS;
        } catch (\Exception $e) {
            // make sure that the job is recorded as failed
            $jobStatus = Job::STATUS_ERROR;
            $jobResult = [
                'message' => 'Internal error occurred, evaluating details'
            ];
            $this->job->setStatus($jobStatus);
            $this->job->setResult($jobResult);
            $this->jobMapper->update($this->job);

            // try to log the exception
            $exceptionId = $this->logException('critical', $e);
            $jobResult = [
                'message' => 'Internal error occurred, please contact support@keboola.com',
                'exceptionId'   => $exceptionId
            ];
            $status = self::STATUS_ERROR;
        }

        // Update job with results
        $endTime = time();

        $this->job->setStatus($jobStatus);
        $this->job->setResult($jobResult);
        $this->job->setEndTime(date('c', $endTime));
        $this->job->setDurationSeconds($endTime - $startTime);
        $this->job->setWaitSeconds($endTime - strtotime($this->job->getCreatedTime()));
        $this->jobMapper->update($this->job);

        // postExecution action
        try {
            if ($jobExecutor instanceof HookExecutorInterface) {
                /** @var HookExecutorInterface $jobExecutor */
                $jobExecutor->postExecution($this->job);
            }
        } catch (\Exception $e) {
            $this->logException('critical', $e);
        }

        // DB unlock
        $this->lock->unlock();

        return $status;
    }

    protected function logException($level, \Exception $exception)
    {
        $component = 'unknown';
        if ($this->job != null) {
            $component = $this->job->getComponent();
        }

        $exceptionId = $component . '-' . md5(microtime());

        $logData = [
            'exceptionId'   => $exceptionId,
            'exception'     => $exception,
        ];

        // SyrupExceptionInterface holds additional data
        if ($exception instanceof SyrupExceptionInterface) {
            $logData['data'] = $exception->getData();
        }

        $this->logger->$level($exception->getMessage(), $logData);

        return $exceptionId;
    }
}
