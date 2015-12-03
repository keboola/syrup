<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 20/02/14
 * Time: 15:51
 */

namespace Syrup\StorageApi;

use Keboola\StorageApi\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Keboola\Syrup\Service\StorageApi\StorageApiService;

class StorageApiServiceTest extends WebTestCase
{
    /**
     * @covers \Keboola\Syrup\Service\StorageApi\StorageApiService::getClient
     */
    public function testStorageApiService()
    {
        $client = static::createClient();
        $container = $client->getContainer();

        $request = Request::create('/ex-dummy/run', 'POST');
        $request->headers->set('X-StorageApi-Token', $container->getParameter('storage_api.test.token'));
        $container->get('request_stack')->push($request);

        /** @var StorageApiService $storageApiService */
        $storageApiService = $container->get('syrup.storage_api');

        $sapiClient = $storageApiService->getClient();

        $this->assertNotNull($sapiClient);
        $this->assertInstanceOf('Keboola\StorageApi\Client', $sapiClient);
    }

    /**
     * @covers \Keboola\Syrup\Service\StorageApi\StorageApiService::getClient
     */
    public function testWrongToken()
    {
        $client = static::createClient();
        $container = $client->getContainer();

        $request = Request::create('/ex-dummy/run', 'POST');
        $request->headers->set('X-StorageApi-Token', 'hereBeDragons');
        $container->get('request_stack')->push($request);

        /** @var StorageApiService $storageApiService */
        $storageApiService = $container->get('syrup.storage_api');

        $this->setExpectedException('Keboola\Syrup\Exception\UserException', 'Invalid StorageApi Token');

        $sapiClient = $storageApiService->getClient();
    }

    /**
     * @covers \Keboola\Syrup\Service\StorageApi\StorageApiService::setClient
     * @covers \Keboola\Syrup\Service\StorageApi\StorageApiService::getTokenData
     */
    public function testGetTokenData()
    {
        $client = static::createClient();
        $container = $client->getContainer();

        $sapiClient = new Client([
            'token' => $container->getParameter('storage_api.test.token')
        ]);

        /** @var StorageApiService $storageApiService */
        $storageApiService = $container->get('syrup.storage_api');
        $storageApiService->setClient($sapiClient);

        $tokenData = $storageApiService->getTokenData();

        $this->assertNotEmpty($tokenData);
        $this->assertArrayHasKey('id', $tokenData);
        $this->assertArrayHasKey('token', $tokenData);
        $this->assertArrayHasKey('owner', $tokenData);
    }
}
