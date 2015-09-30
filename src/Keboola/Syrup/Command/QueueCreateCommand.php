<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 24/02/15
 * Time: 17:53
 */

namespace Keboola\Syrup\Command;

use Aws\CloudWatch\CloudWatchClient;
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
            ->addOption('no-watch', 'nw', InputOption::VALUE_NONE, 'Do not add Cloudwatch alarm to this queue')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $accessKey = $input->getArgument('access_key');
        $secretKey = $input->getArgument('secret_key');
        $region = $input->getArgument('region')?:'us-east-1';
        $register = $input->getOption('register');
        $noWatch = $input->getOption('no-watch');

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

        if (!$noWatch) {
            $data['region'] = $region;
            if ($accessKey != null && $secretKey != null) {
                $data['key'] = $accessKey;
                $data['secret'] = $secretKey;
            }
            $cwClient = CloudWatchClient::factory($data);

            $cwClient->putMetricAlarm([
                // AlarmName is required
                'AlarmName' => sprintf('Syrup %s queue is full', $name),
                'ActionsEnabled' => true,
                'AlarmActions' => [
                    'arn:aws:sns:us-east-1:147946154733:Connection_SQS_Alerts'
                ],
                // MetricName is required
                'MetricName' => 'ApproximateNumberOfMessagesVisible',
                // Namespace is required
                'Namespace' => 'AWS/SQS',
                // Statistic is required
                'Statistic' => 'Average',
                'Dimensions' => [
                        [
                            // Name is required
                            'Name' => 'QueueName',
                            // Value is required
                            'Value' => $name,
                        ],
                    ],
                // Period is required
                'Period' => 300,
                'Unit' => 'Seconds',
                // EvaluationPeriods is required
                'EvaluationPeriods' => 1,
                // Threshold is required
                'Threshold' => 5,
                // ComparisonOperator is required
                'ComparisonOperator' => 'GreaterThanOrEqualToThreshold',
            ]);
        }

        return 0;
    }
}
