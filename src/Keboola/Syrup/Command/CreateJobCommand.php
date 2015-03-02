<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 18/09/14
 * Time: 12:34
 */

namespace Keboola\Syrup\Command;

use Keboola\Encryption\EncryptorInterface;
use Keboola\StorageApi\Client as SapiClient;
use Keboola\Syrup\Job\Metadata\JobFactory;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Keboola\Syrup\Service\Queue\QueueService;
use Keboola\Syrup\Service\StorageApi\StorageApiService;
use Keboola\Syrup\Elasticsearch\JobMapper;

class CreateJobCommand extends ContainerAwareCommand
{
    /** @var SapiClient */
    private $storageApi;

    /** @var EncryptorInterface $encryptor */
    private $encryptor;

    private $componentName;

    protected function configure()
    {
        $this
            ->setName('syrup:create-job')
            ->setDescription('Command to execute jobs')
            ->addArgument('token', InputArgument::REQUIRED, 'SAPI token')
            ->addArgument('component', InputArgument::REQUIRED, 'Component name')
            ->addArgument('cmd', InputArgument::REQUIRED, 'Job command name')
            ->addArgument('params', InputArgument::OPTIONAL, 'Job command parameters as JSON', '{}')
            ->addOption('no-run', 'norun', InputOption::VALUE_NONE, "Dont run the job, just create it")
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $token = $input->getArgument('token');
        $this->componentName = $input->getArgument('component');

        $this->storageApi = new SapiClient([
            'url'       => $this->getContainer()->getParameter('storage_api.url'),
            'token'     => $token,
            'userAgent' => $this->componentName
        ]);
        /** @var StorageApiService $storageApiService */
        $storageApiService = $this->getContainer()->get('syrup.storage_api');
        $storageApiService->setClient($this->storageApi);

        $this->encryptor = $this->getContainer()->get('syrup.encryptor');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $command = $input->getArgument('cmd');
        $params = json_decode($input->getArgument('params'), true);

        // Create new job
        /** @var JobFactory $jobFactory */
        $jobFactory = $this->getContainer()->get('syrup.job_factory');
        $jobFactory->setStorageApiClient($this->storageApi);
        $job = $jobFactory->create($command, $params);

        // Add job to Elasticsearch
        /** @var JobMapper $jobMapper */
        $jobMapper = $this->getContainer()->get('syrup.elasticsearch.current_component_job_mapper');
        $jobId = $jobMapper->create($job);

        $output->writeln('Created job id ' . $jobId);

        // Run Job
        if (!$input->getOption('no-run')) {
            $runJobCommand = $this->getApplication()->find('syrup:run-job');

            $returnCode = $runJobCommand->run(
                new ArrayInput([
                    'command'   => 'syrup:run-job',
                    'jobId'     => $jobId
                ]),
                $output
            );

            if ($returnCode == 0) {
                $output->writeln('Job successfully executed');
            } elseif ($returnCode == 2 || $returnCode == 64) {
                $output->writeln('DB is locked. Run job later using syrup:run-job');
            } else {
                $output->writeln('Error occured');
            }
        }

        return 0;
    }

    protected function enqueue($jobId, $queueName = 'default', $otherData = [])
    {
        /** @var QueueService $queue */
        $queue = $this->getContainer()->get('syrup.queue_factory')->get($queueName);
        return $queue->enqueue($jobId, $otherData);
    }
}
