<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 20/02/14
 * Time: 15:51
 */

namespace Syrup\StorageApi;

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
        $container->set('request', $request);

        /** @var StorageApiService $storageApiService */
        $storageApiService = $container->get('syrup.storage_api');

        $sapiClient = $storageApiService->getClient();

        $this->assertNotNull($sapiClient);
        $this->assertInstanceOf('Keboola\StorageApi\Client', $sapiClient);
    }
}
