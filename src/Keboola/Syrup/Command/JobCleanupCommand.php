<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 09/03/15
 * Time: 15:23
 */

namespace Keboola\Syrup\Command;

use Doctrine\DBAL\Connection;
use Keboola\Encryption\EncryptorInterface;
use Keboola\Syrup\Elasticsearch\JobMapper;
use Keboola\Syrup\Exception\ApplicationException;
use Keboola\Syrup\Job\ExecutorFactory;
use Keboola\Syrup\Job\ExecutorInterface;
use Keboola\Syrup\Job\Metadata\Job;
use Keboola\Syrup\Service\Db\Lock;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Keboola\StorageApi\Client as SapiClient;

class JobCleanupCommand extends ContainerAwareCommand
{
    /** @var Job */
    protected $job;

    /** @var JobMapper */
    protected $jobMapper;

    /** @var SapiClient */
    protected $sapiClient;

    /** @var Lock */
    protected $lock;

    protected function configure()
    {
        $this
            ->setName('syrup:job:cleanup')
            ->setDescription('Clean-up after a job has been terminated with extreme prejudice')
            ->addArgument('jobId', InputArgument::REQUIRED, 'ID of the job')
        ;
    }

    protected function init($jobId)
    {
        $this->jobMapper = $this->getContainer()->get('syrup.elasticsearch.current_component_job_mapper');

        // Get job from ES
        $this->job = $this->jobMapper->get($jobId);

        if ($this->job == null) {
            throw new ApplicationException(sprintf("Job id '%s' not found", $jobId));
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

        $this->init($jobId);

        // Instantiate jobExecutor based on component name
        /** @var ExecutorFactory $jobExecutorFactory */
        $jobExecutorFactory = $this->getContainer()->get('syrup.job_executor_factory');

        /** @var ExecutorInterface $jobExecutor */
        $jobExecutor = $jobExecutorFactory->create($this->job);

        // Ensure that job status is 'terminating'
        if ($this->job->getStatus() != Job::STATUS_TERMINATING) {
            $this->job->setStatus(Job::STATUS_TERMINATING);
            $this->jobMapper->update($this->job);
        }

        // run cleanup
        $jobExecutor->cleanup();

        // Update job status to 'terminated'
        $this->job->setStatus(Job::STATUS_TERMINATED);
        $this->jobMapper->update($this->job);

        // DB unlock
        $this->lock->unlock();
    }
}
