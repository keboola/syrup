<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 19/03/15
 * Time: 12:44
 */

namespace Keboola\Syrup\Tests\Command;

use Aws\Sqs\SqsClient;
use Keboola\Syrup\Command\QueueCreateCommand;
use Keboola\Syrup\Service\Queue\QueueFactory;
use Keboola\Syrup\Test\CommandTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Doctrine\DBAL\DriverManager;

class QueueCreateCommandTest extends CommandTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->application->add(new QueueCreateCommand());
    }

    public function testCreateQueue()
    {
        $queueName = AWS_SQS_TEST_QUEUE_NAME;

        $db = DriverManager::getConnection([
            'driver' => 'pdo_mysql',
            'host' => DATABASE_HOST,
            'dbname' => DATABASE_NAME,
            'user' => DATABASE_USER,
            'password' => DATABASE_PASSWORD,
            'port' => DATABASE_PORT
        ]);

        $db->query("DELETE FROM queues WHERE id='$queueName'")->execute();

        $command = $this->application->find('syrup:queue:create');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'name' => $queueName,
            'region' => AWS_REGION,
            '--register' => null,
            '--no-watch' => null
        ]);

        $this->assertEquals(0, $commandTester->getStatusCode());

        $factory = new QueueFactory($db, ['db_table' => 'queues'], SYRUP_APP_NAME);
        $dbQueue = $factory->get($queueName);

        $queueUrlArr = explode('/', $dbQueue->getUrl());
        $this->assertEquals($queueName, array_pop($queueUrlArr));

        $sqsClient = new SqsClient([
            'version' => '2012-11-05',
            'region' => AWS_REGION
        ]);
        $sqsClient->deleteQueue([
            'QueueUrl' => $dbQueue->getUrl()
        ]);
        sleep(60);
    }
}
