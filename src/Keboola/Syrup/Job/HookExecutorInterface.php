<?php
namespace Keboola\Syrup\Job;

use Keboola\Syrup\Job\Metadata\Job;

/**
 * Interface HookExecutorInterface
 * @deprecated will be removed in 4.0, use postExecute() function in ExecutorInterface instead
 * @package Keboola\Syrup\Job
 */
interface HookExecutorInterface extends ExecutorInterface
{
    /**
     * @param Job $job
     * @deprecated use postExecute() function in ExecutorInterface instead
     * @return void
     */
    public function postExecution(Job $job);
}
