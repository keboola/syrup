<?php
/**
 * @package syrup
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace Keboola\Syrup\Job\Metadata;

use Keboola\StorageApi\Client;
use Keboola\Syrup\Encryption\Encryptor;
use Keboola\Syrup\Service\ObjectEncryptor;
use Keboola\Syrup\Service\StorageApi\StorageApiService;

class JobFactory
{
    /* @var Encryptor */
    protected $encryptor;

    /* @var ObjectEncryptor */
    protected $configEncryptor;

    protected $componentName;

    /** @var StorageApiService */
    protected $storageApiService;

    /**
     * @var Client
     */
    protected $storageApiClient;

    public function __construct(
        $componentName,
        Encryptor $encryptor,
        ObjectEncryptor $configEncryptor,
        StorageApiService $storageApiService = null
    ) {
        $this->encryptor = $encryptor;
        $this->configEncryptor = $configEncryptor;
        $this->componentName = $componentName;
        $this->storageApiService = $storageApiService;
    }

    /**
     * @deprecated StorageApi Client should be set via StorageApiService
     * @param Client $storageApiClient
     */
    public function setStorageApiClient(Client $storageApiClient)
    {
        $this->storageApiClient = $storageApiClient;
    }

    public function create($command, array $params = [], $lockName = null)
    {
        //@todo remove after setStorageApiClient method is removed
        if ($this->storageApiClient == null) {
            $this->storageApiClient = $this->storageApiService->getClient();
            $tokenData = $this->storageApiService->getTokenData();
        } else {
            $tokenData = $this->storageApiClient->verifyToken();
        }

        $job = new Job($this->configEncryptor, [
                'id' => $this->storageApiClient->generateId(),
                'runId' => $this->storageApiClient->generateRunId($this->storageApiClient->getRunId()),
                'project' => [
                    'id' => $tokenData['owner']['id'],
                    'name' => $tokenData['owner']['name']
                ],
                'token' => [
                    'id' => $tokenData['id'],
                    'description' => $tokenData['description'],
                    'token' => $this->encryptor->encrypt($this->storageApiClient->getTokenString())
                ],
                'component' => $this->componentName,
                'command' => $command,
                'params' => $params,
                'process' => [
                    'host' => gethostname(),
                    'pid' => getmypid()
                ],
                'nestingLevel' => 0,
                'createdTime' => date('c')
            ], null, null, null);

        if ($lockName) {
            $job->setLockName($lockName);
        }

        $componentConfiguration = $this->getComponentConfiguration();
        if (isset($componentConfiguration['flags']) && in_array('encrypt', $componentConfiguration['flags'])) {
            $job->setEncrypted(true);
        }

        return $job;
    }

    protected function getComponentConfiguration()
    {
        // Check list of components
        $components = $this->storageApiClient->indexAction();

        foreach ($components["components"] as $c) {
            if ($c["id"] == $this->componentName) {
                return $c;
            }
        }

        // no component configuration found
        return [];
    }
}
