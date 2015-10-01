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

class JobFactory
{
    /**
     * @var Encryptor
     */
    protected $encryptor;

    /**
     * @var ObjectEncryptor
     */
    protected $configEncryptor;

    /**
     * @var
     */
    protected $componentName;

    /**
     * @var Client
     */
    protected $storageApiClient;

    public function __construct($componentName, Encryptor $encryptor, ObjectEncryptor $configEncryptor)
    {
        $this->encryptor = $encryptor;
        $this->configEncryptor = $configEncryptor;
        $this->componentName = $componentName;
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
            'component' => $this->componentName,
            'command' => $command,
            'params' => $this->configEncryptor->decrypt($params),
            'process' => [
                'host' => gethostname(),
                'pid' => getmypid()
            ],
            'nestingLevel' => 0,
            'createdTime' => date('c')
        ]);
        if ($lockName) {
            $job->setLockName($lockName);
        }
        return $job;
    }
}
