<?php

namespace Syrup\StorageApi;

use Keboola\StorageApi\Client;
use Keboola\Syrup\Service\StorageApi\Limits;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Keboola\Syrup\Service\StorageApi\StorageApiService;

class LimitsTest extends WebTestCase
{
    public function testParallelLimits()
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
        $this->assertArrayHasKey('limits', $tokenData['owner']);
        $this->assertArrayHasKey(Limits::PARALLEL_LIMIT_NAME, $tokenData['owner']['limits']);

        $this->assertTrue(Limits::hasParallelLimit($tokenData));
        $this->assertGreaterThan(0, Limits::getParallelLimit($tokenData));
        $this->assertTrue(is_int(Limits::getParallelLimit($tokenData)));

        // limit with 0 value
        $tokenData['owner']['limits'][Limits::PARALLEL_LIMIT_NAME]['value'] = 0;
        $this->assertFalse(Limits::hasParallelLimit($tokenData));
        $this->assertNull(Limits::getParallelLimit($tokenData));

        // limit not set
        unset($tokenData['owner']['limits'][Limits::PARALLEL_LIMIT_NAME]);
        $this->assertFalse(Limits::hasParallelLimit($tokenData));
        $this->assertNull(Limits::getParallelLimit($tokenData));
    }
}
