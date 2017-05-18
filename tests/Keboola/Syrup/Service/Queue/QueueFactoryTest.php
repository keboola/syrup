<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 11/03/15
 * Time: 17:26
 */

namespace Keboola\Syrup\Tests\Service\Queue;

use Aws\Sqs\SqsClient;
use Doctrine\DBAL\DriverManager;
use Keboola\Syrup\Service\Queue\QueueFactory;

class QueueFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $db = DriverManager::getConnection([
            'driver' => 'pdo_mysql',
            'host' => DATABASE_HOST,
            'dbname' => DATABASE_NAME,
            'user' => DATABASE_USER,
            'password' => DATABASE_PASSWORD,
            'port' => DATABASE_PORT
        ]);

        $factory = new QueueFactory($db, ['db_table' => 'queues'], SYRUP_APP_NAME);

        $sqsQueue = $factory->create(AWS_SQS_TEST_QUEUE_NAME, AWS_REGION);

        $queueUrlArr = explode('/', $sqsQueue->get('QueueUrl'));
        $this->assertEquals(AWS_SQS_TEST_QUEUE_NAME, array_pop($queueUrlArr));

        // delete the queue from AWS
        $sqsClient = new SqsClient([
            'version' => '2012-11-05',
            'region' => AWS_REGION
        ]);
        $sqsClient->deleteQueue([
            'QueueUrl' => $sqsQueue->get('QueueUrl')
        ]);
        sleep(60);
    }
}
