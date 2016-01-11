<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 06/06/14
 * Time: 14:50
 */

namespace Keboola\Syrup\Job;

use Keboola\StorageApi\Client as SapiClient;
use Keboola\Syrup\Job\Metadata\Job;
use Keboola\Syrup\Service\StorageApi\StorageApiService;

class Executor implements ExecutorInterface
{
    /** @var SapiClient */
    protected $storageApi;


    public function execute(Job $job)
    {
        // do stuff
    }

    public function postExecute(Job $job)
    {
        // do stuff after stuff
    }

    public function cleanup(Job $job)
    {
        // clean up after stuff
    }

    public function postCleanup(Job $job)
    {
        // do stuff after stuff has been cleaned
    }

    public function setStorageApi($client)
    {
        $this->storageApi = $client;
    }
}
