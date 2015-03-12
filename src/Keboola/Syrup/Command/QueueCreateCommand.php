<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 24/02/15
 * Time: 17:53
 */

namespace Keboola\Syrup\Command;

use Keboola\Syrup\Service\Queue\QueueFactory;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\DBAL\Connection;

class QueueCreateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('syrup:queue:create')
            ->setDescription('Create new SQS queue')
            ->addArgument('name', InputArgument::REQUIRED, 'Queue name')
            ->addArgument('access_key', InputArgument::OPTIONAL)
            ->addArgument('secret_key', InputArgument::OPTIONAL)
            ->addArgument('region', InputArgument::OPTIONAL)
            ->addOption('register', 'r', InputOption::VALUE_NONE, 'If set the queue will be registerd to DB')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $accessKey = $input->getArgument('access_key');
        $secretKey = $input->getArgument('secret_key');
        $region = $input->getArgument('region')?:'us-east-1';
        $register = $input->getOption('register');

        /** @var $queueFactory QueueFactory */
        $queueFactory = $this->getContainer()->get('syrup.queue_factory');

        $sqsQueue = $queueFactory->create($name, $region, $accessKey, $secretKey);

        if ($register) {
            /** @var Connection $conn */
            $conn = $this->getContainer()->get('database_connection');

            $conn->insert('queues', [
                'id' => $name,
                'access_key' => $accessKey?:'',
                'secret_key' => $secretKey?:'',
                'region' => $region?:'us-east-1',
                'url' => $sqsQueue->get('QueueUrl')
            ]);
        }
    }
}
