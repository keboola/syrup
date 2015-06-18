<?php
/**
 * @package syrup
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace Keboola\Syrup\Job\Metadata;

use Keboola\StorageApi\Client;
use Keboola\Syrup\Encryption\Encryptor;

class JobFactory
{
    /**
     * @var Encryptor
     */
    protected $encryptor;
    protected $appName;

    /**
     * @var Client
     */
    protected $storageApiClient;

    public function __construct($appName, Encryptor $encryptor)
    {
        $this->encryptor = $encryptor;
        $this->appName = $appName;
    }

    public function setStorageApiClient(Client $storageApiClient)
    {
        $this->storageApiClient = $storageApiClient;
    }

    public function create($command, array $params = [], $lockName = null)
    {
        if (!$this->storageApiClient) {
            throw new \Exception('Storage API client must be set');
        }

        $component = $this->appName;
        if (isset($params['component'])) {
            $component = $params['component'];
        }

        $tokenData = $this->storageApiClient->verifyToken();
        $job = new Job([
            'id' => $this->storageApiClient->generateId(),
            'runId' => $this->storageApiClient->getRunId(),
            'project' => [
                'id' => $tokenData['owner']['id'],
                'name' => $tokenData['owner']['name']
            ],
            'token' => [
                'id' => $tokenData['id'],
                'description' => $tokenData['description'],
                'token' => $this->encryptor->encrypt($this->storageApiClient->getTokenString())
            ],
            'app' => $this->appName,
            'component' => $component,
            'command' => $command,
            'params' => $params,
            'process' => [
                'host' => gethostname(),
                'pid' => getmypid()
            ],
            'createdTime' => date('c')
        ]);

        if ($lockName) {
            $job->setLockName($lockName);
        }

        return $job;
    }
}
