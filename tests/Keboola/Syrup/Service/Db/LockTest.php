<?php
/**
 * @author Jakub Matejka <jakub@keboola.com>
 * @date 2014-08-28
 */
namespace Keboola\Syrup\Tests\Service\Db;

use Keboola\Syrup\Service\Db\Lock;

class LockTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers \Keboola\Syrup\Service\Db\Lock::lock
     * @covers \Keboola\Syrup\Service\Db\Lock::isFree
     * @covers \Keboola\Syrup\Service\Db\Lock::unlock
     */
    public function testLocks()
    {
        $connectionParams = [
            'host' => SYRUP_DATABASE_HOST,
            'dbname' => SYRUP_DATABASE_NAME,
            'user' => SYRUP_DATABASE_USER,
            'password' => SYRUP_DATABASE_PASSWORD,
            'port' => SYRUP_DATABASE_PORT,
            'driver' => 'pdo_mysql',
            'charset' => 'utf8'
        ];

        $db1 = \Doctrine\DBAL\DriverManager::getConnection($connectionParams);
        $db1->exec('SET wait_timeout = 31536000;');

        $db2 = \Doctrine\DBAL\DriverManager::getConnection($connectionParams);
        $db2->exec('SET wait_timeout = 31536000;');

        $lockName = uniqid();

        $lock1 = new Lock($db1, $lockName);
        $lock2 = new Lock($db2, $lockName);

        $this->assertTrue($lock1->lock(), 'Should successfully lock');
        $this->assertFalse($lock1->isFree(), 'Should tell lock not free');
        $this->assertFalse($lock2->isFree(), 'Should tell lock not free');
        $this->assertFalse($lock2->lock(), 'Should fail locking');
        $this->assertTrue($lock1->unlock(), 'Should successfully unlock');

        $this->assertTrue($lock2->lock(), 'Should successfully lock');
        $this->assertFalse($lock2->isFree(), 'Should tell lock not free');
        $this->assertFalse($lock1->isFree(), 'Should tell lock not free');
        $this->assertFalse($lock1->lock(), 'Should fail locking');
        $this->assertTrue($lock2->unlock(), 'Should successfully unlock');
    }
}
