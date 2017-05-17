<?php
namespace Keboola\Syrup\Test\Job\Executor;

use Keboola\Syrup\Exception\JobException;
use Keboola\Syrup\Exception\UserException;
use Keboola\Syrup\Job\Metadata\Job;

class UserErrorExecutor extends \Keboola\Syrup\Job\Executor
{
    public function execute(Job $job)
    {
        $e = new UserException('One of orchestration tasks failed');
        $e
            ->setData(array('key' => 'value'));
        throw $e;
    }
}
