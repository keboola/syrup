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

    public function testWrongToken()
    {
        $client = static::createClient();
        $container = $client->getContainer();

        $request = Request::create('/ex-dummy/run', 'POST');
        $request->headers->set('X-StorageApi-Token', 'hereBeDragons');
        $container->get('request_stack')->push($request);

        /** @var StorageApiService $storageApiService */
        $storageApiService = $container->get('syrup.storage_api');

        $this->expectException('Keboola\Syrup\Exception\SimpleException');
        $this->expectExceptionMessage('Invalid StorageApi Token');

        $sapiClient = $storageApiService->getClient();
    }

    public function testGetTokenData()
    {
        $client = static::createClient();
        $container = $client->getContainer();

        $sapiClient = new Client([
            'token' => $container->getParameter('storage_api.test.token'),
            'url' => $container->getParameter('storage_api.test.url'),
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

    public function testGetBackoffTries()
    {
        $client = static::createClient();
        $container = $client->getContainer();

        /** @var StorageApiService $storageApiService */
        $storageApiService = $container->get('syrup.storage_api');

        putenv('STORAGE_API_CLIENT_BACKOFF_MAX_TRIES=7');
        $backoffTries = $storageApiService->getBackoffTries(gethostname());
        $this->assertEquals(7, $backoffTries);
        putenv('STORAGE_API_CLIENT_BACKOFF_MAX_TRIES');

        $backoffTries = $storageApiService->getBackoffTries('syrup-worker.keboola.com');
        $this->assertEquals(11, $backoffTries);

        $backoffTries = $storageApiService->getBackoffTries('syrup.keboola.com');
        $this->assertEquals(3, $backoffTries);
    }
}
