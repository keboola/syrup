<?php
/**
 * @package syrup
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace Keboola\Syrup\Job\Metadata;

use Keboola\ObjectEncryptor\ObjectEncryptor;
use Keboola\ObjectEncryptor\ObjectEncryptorFactory;
use Keboola\StorageApi\Client;
use Keboola\Syrup\Service\StorageApi\StorageApiService;

class JobFactory
{
    /* @var ObjectEncryptor */
    protected $objectEncryptor;

    protected $componentName;

    /** @var StorageApiService */
    protected $storageApiService;

    /**
     * @var Client
     */
    protected $storageApiClient;

    public function __construct(
        $componentName,
        ObjectEncryptorFactory $objectEncryptor,
        StorageApiService $storageApiService = null
    ) {
        $this->objectEncryptor = $objectEncryptor->getEncryptor();
        $this->componentName = $componentName;
        $this->storageApiService = $storageApiService;
    }

    public function create($command, array $params = [], $lockName = null)
    {
        $this->storageApiClient = $this->storageApiService->getClient();
        $tokenData = $this->storageApiService->getTokenData();

        $job = new Job($this->objectEncryptor, [
                'id' => $this->storageApiClient->generateId(),
                'runId' => $this->storageApiClient->generateRunId($this->storageApiClient->getRunId()),
                'project' => [
                    'id' => $tokenData['owner']['id'],
                    'name' => $tokenData['owner']['name']
                ],
                'token' => [
                    'id' => $tokenData['id'],
                    'description' => $tokenData['description'],
                    'token' => $this->objectEncryptor->encrypt($this->storageApiClient->getTokenString())
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
