<?php
/**
 * @package syrup
 * @copyright 2015 Keboola
 * @author Ondrej Hlavacek <ondra@keboola.com>
 */
namespace Keboola\Syrup\Tests\Job\Metadata;

use Keboola\ObjectEncryptor\ObjectEncryptorFactory;
use Keboola\StorageApi\Client;
use Keboola\Syrup\Job\Metadata\JobFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class JobTest extends KernelTestCase
{
    public function setUp()
    {
        static::bootKernel();
    }

    public function testGetParams()
    {
        $storageApiService = self::$kernel->getContainer()->get('syrup.storage_api');
        $storageApiService->setClient(new Client([
            'token' => SAPI_TOKEN,
            'userAgent' => SYRUP_APP_NAME,
            'url' => SAPI_URL,
        ]));

        /** @var ObjectEncryptorFactory $objectEncryptorFactory */
        $objectEncryptorFactory = self::$kernel->getContainer()->get('syrup.object_encryptor_factory');
        $jobFactory = new JobFactory(SYRUP_APP_NAME, $objectEncryptorFactory, $storageApiService);

        $command = uniqid();
        $param = ["key1" => "value1", "#key2" => "value2"];
        $lock = uniqid();

        $job = $jobFactory->create($command, $objectEncryptorFactory->getEncryptor(true)->encrypt($param), $lock);

        $this->assertEquals($command, $job->getCommand());
        $this->assertEquals($lock, $job->getLockName());
        $this->assertEquals("KBC::Encrypted==", substr($job->getData()["params"]["#key2"], 0, 16));
        $this->assertEquals($param, $job->getParams());
    }

    public function testSanitizeResult()
    {
        $storageApiService = self::$kernel->getContainer()->get('syrup.storage_api');
        $storageApiService->setClient(new Client([
            'token' => SAPI_TOKEN,
            'userAgent' => SYRUP_APP_NAME,
            'url' => SAPI_URL,
        ]));

        /** @var ObjectEncryptorFactory $objectEncryptorFactory */
        $objectEncryptorFactory = self::$kernel->getContainer()->get('syrup.object_encryptor_factory');
        $jobFactory = new JobFactory(SYRUP_APP_NAME, $objectEncryptorFactory, $storageApiService);

        $command = uniqid();
        $param = ["key1" => "value1", "#key2" => "value2"];
        $lock = uniqid();

        $job = $jobFactory->create($command, $objectEncryptorFactory->getEncryptor(true)->encrypt($param), $lock);
        $job->setResult(["message" => "SQLSTATE[XX000]: " . chr(0x00000080) . " abcd"]);
        $this->assertEquals(["message" => "SQLSTATE[XX000]:  abcd"], $job->getResult());
    }
}
