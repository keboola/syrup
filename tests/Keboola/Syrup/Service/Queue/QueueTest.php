<?php
/**
 * @package syrup-component-bundle
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */
namespace Keboola\Syrup\Tests\Service\Queue;

use Keboola\Syrup\Service\Queue\QueueFactory;

class QueueTest extends \PHPUnit_Framework_TestCase
{
    public function testQueue()
    {
        $db = \Doctrine\DBAL\DriverManager::getConnection([
            'driver' => 'pdo_mysql',
            'host' => DATABASE_HOST,
            'dbname' => DATABASE_NAME,
            'user' => DATABASE_USER,
            'password' => DATABASE_PASSWORD,
            'port' => DATABASE_PORT
        ]);
        $queueFactory = new QueueFactory($db, ['db_table' => 'queues'], SYRUP_APP_NAME);
        $queueService = $queueFactory->get();

        // clear queue first
        $queueNotEmpty = true;
        do {
            $messages = $queueService->receive(10);
            if (count($messages)) {
                foreach ($messages as $message) {
                    $queueService->deleteMessage($message);
                }
            } else {
                $queueNotEmpty = false;
            }
        } while ($queueNotEmpty);

        $jobId = rand(0, 128);
        $messageId = $queueService->enqueue($jobId, ['test' => 'test']);
        $this->assertNotNull($messageId);

        $result = $queueService->receive();
        $this->assertGreaterThan(0, $result);
        foreach ($result as $message) {
            $queueService->deleteMessage($message);
        }

        // assert that test did not fail
        $this->assertTrue(true);
    }
}
