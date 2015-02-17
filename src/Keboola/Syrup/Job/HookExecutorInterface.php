<?php
namespace Keboola\Syrup\Job;

use Keboola\Syrup\Job\Metadata\Job;

interface HookExecutorInterface extends ExecutorInterface
{
    /**
     * @param Job $job
     * @return void
     */
    public function postExecution(Job $job);
}
