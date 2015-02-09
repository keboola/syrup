<?php
namespace Keboola\Syrup\Tests\Job;

use Keboola\Syrup\Exception\JobException;
use Keboola\Syrup\Job\Metadata\Job;

class SuccessExecutor extends \Keboola\Syrup\Job\Executor
{
    public function execute(Job $job)
    {
        parent::execute($job);

        $e = new JobException(200, 'All done');
        $e
            ->setStatus(Job::STATUS_SUCCESS)
            ->setResult(array('testing' => 'value'));

        throw $e;
    }
}
