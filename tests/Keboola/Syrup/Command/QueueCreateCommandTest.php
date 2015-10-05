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
        $queueName = 'test_queue';

        $db = DriverManager::getConnection([
            'driver' => 'pdo_mysql',
            'host' => SYRUP_DATABASE_HOST,
            'dbname' => SYRUP_DATABASE_NAME,
            'user' => SYRUP_DATABASE_USER,
            'password' => SYRUP_DATABASE_PASSWORD,
            'port' => SYRUP_DATABASE_PORT
        ]);

        $db->query("DELETE FROM queues WHERE id='$queueName'")->execute();

        $command = $this->application->find('syrup:queue:create');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'name' => $queueName,
            '--register' => null,
            '--no-watch'
        ]);

        $this->assertEquals(0, $commandTester->getStatusCode());

        $factory = new QueueFactory($db, ['db_table' => 'queues'], SYRUP_APP_NAME);
        $dbQueue = $factory->get($queueName);

        $queueUrlArr = explode('/', $dbQueue->getUrl());
        $this->assertEquals($queueName, array_pop($queueUrlArr));

        $sqsClient = SqsClient::factory([
            'region' => 'us-east-1'
        ]);
        $sqsClient->deleteQueue([
            'QueueUrl' => $dbQueue->getUrl()
        ]);
    }
}
