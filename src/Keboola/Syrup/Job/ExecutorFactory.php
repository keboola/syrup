<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 18/03/15
 * Time: 13:40
 */

namespace Keboola\Syrup\Job;

use Keboola\Syrup\Service\StorageApi\StorageApiService;

class ExecutorFactory
{
    protected $storageApiService;

    protected $executor;

    public function __construct(StorageApiService $storageApiService, ExecutorInterface $executor)
    {
        $this->storageApiService = $storageApiService;
        $this->executor = $executor;
    }

    public function create()
    {
        $this->executor->setStorageApi($this->storageApiService->getClient());

        return $this->executor;
    }
}
