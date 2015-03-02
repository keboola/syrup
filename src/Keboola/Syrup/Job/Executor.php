<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 06/06/14
 * Time: 14:50
 */

namespace Keboola\Syrup\Job;

use Keboola\StorageApi\Client as SapiClient;
use Keboola\Syrup\Exception\JobException;
use Keboola\Syrup\Job\Metadata\Job;

class Executor implements ExecutorInterface
{
    /** @var SapiClient */
    protected $storageApi;

    public function setStorageApi(SapiClient $sapi)
    {
        $this->storageApi = $sapi;
    }

    public function execute(Job $job)
    {
        // do stuff
    }

    public function onTerminate()
    {
        $e = new JobException(500, "Job terminated");
        $e->setStatus(Job::STATUS_TERMINATED);
        throw $e;
    }

}
