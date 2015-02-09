<?php
namespace Keboola\Syrup\Tests\Job;

use Keboola\Syrup\Exception\JobException;
use Keboola\Syrup\Job\Metadata\Job;

class WarningExecutor extends \Keboola\Syrup\Job\Executor
{
    public function execute(Job $job)
    {
        parent::execute($job);

        $e = new JobException(400, 'One of orchestration tasks failed');
        $e
            ->setStatus(Job::STATUS_WARNING)
            ->setResult(array('testing' => 'value'));

        throw $e;
    }
}
