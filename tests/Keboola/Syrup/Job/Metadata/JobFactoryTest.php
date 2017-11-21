<?php
/**
 * @package syrup
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */
namespace Keboola\Syrup\Tests\Job\Metadata;

use Keboola\ObjectEncryptor\ObjectEncryptor;
use Keboola\StorageApi\Client;
use Keboola\Syrup\Job\Metadata\JobFactory;
use Keboola\Syrup\Service\StorageApi\StorageApiService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class JobFactoryTest extends KernelTestCase
{
    public function setUp()
    {
        static::bootKernel();
    }

    public function testJobFactory()
    {
        $storageApiClient = new Client([
            'token' => SAPI_TOKEN,
            'userAgent' => SYRUP_APP_NAME,
            'url' => SAPI_URL,
        ]);

        /** @var ObjectEncryptor $encryptor */
        $encryptor = self::$kernel->getContainer()->get('syrup.object_encryptor_factory')->getEncryptor();
        /** @var StorageApiService $storageApiService */
        $storageApiService = self::$kernel->getContainer()->get('syrup.storage_api');
        $storageApiService->setClient($storageApiClient);
        $jobFactory = new JobFactory(SYRUP_APP_NAME, $encryptor, $storageApiService);

        $command = uniqid();
        $param = uniqid();
        $lock = uniqid();
        $tokenData = $storageApiClient->verifyToken();

        $job = $jobFactory->create($command, ['param' => $param], $lock);

        $this->assertEquals($command, $job->getCommand());
        $this->assertEquals($lock, $job->getLockName());
        $this->assertEquals(['param' => $param], $job->getParams());
        $this->assertArrayHasKey('id', $job->getProject());
        $this->assertEquals($tokenData['owner']['id'], $job->getProject()['id']);
        $this->assertArrayHasKey('name', $job->getProject());
        $this->assertEquals($tokenData['owner']['name'], $job->getProject()['name']);
        $this->assertArrayHasKey('id', $job->getToken());
        $this->assertEquals($tokenData['id'], $job->getToken()['id']);
        $this->assertArrayHasKey('description', $job->getToken());
        $this->assertEquals($tokenData['description'], $job->getToken()['description']);
        $this->assertArrayHasKey('token', $job->getToken());
        $this->assertEquals($tokenData['token'], $encryptor->decrypt($job->getToken()['token']));
    }
}
