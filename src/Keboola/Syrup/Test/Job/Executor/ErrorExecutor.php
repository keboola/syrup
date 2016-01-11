<?php
namespace Keboola\Syrup\Test\Job\Executor;

use Keboola\Syrup\Exception\JobException;
use Keboola\Syrup\Job\Metadata\Job;

class ErrorExecutor extends \Keboola\Syrup\Job\Executor
{
    public function execute(Job $job)
    {
        $e = new JobException(500, 'One of orchestration tasks failed');
        $e
            ->setStatus(Job::STATUS_ERROR)
            ->setResult(array('testing' => 'value'));

        throw $e;
    }
}
