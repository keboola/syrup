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

    /** @var Job */
    protected $job;

    public function setJob(Job $job)
    {
        $this->job = $job;
    }

    public function setStorageApi(SapiClient $sapi)
    {
        $this->storageApi = $sapi;
    }

    public function execute(Job $job)
    {
        // do stuff
    }

    /** @deprecated */
    public function onTerminate()
    {
        $this->cleanup();
        $e = new JobException(500, 'Job terminated by user');
        $e->setStatus(Job::STATUS_TERMINATED);
        throw $e;
    }

    public function cleanup()
    {

    }

    public function postCleanup()
    {

    }
}
