<?php

namespace Keboola\Syrup\Test\Job\Executor;

use Keboola\Syrup\Elasticsearch\JobMapper;
use Keboola\Syrup\Exception\JobException;
use Keboola\Syrup\Job\Metadata\Job;
use Keboola\Syrup\Job\Executor as SyrupJobExecutor;

class UsageUpdateExecutor extends SyrupJobExecutor
{
    /** @var JobMapper */
    private $jobMapper;

    public function __construct(JobMapper $jobMapper)
    {
        $this->jobMapper = $jobMapper;
    }

    public function execute(Job $job)
    {
        $this->setUsage($job->getId());

        parent::execute($job);

        $e = new JobException(200, 'All done');
        $e
            ->setStatus(Job::STATUS_SUCCESS)
            ->setResult(array('testing' => 'value'));

        throw $e;
    }

    private function setUsage($jobId)
    {
        // we need to fetch job again to have new object
        $jobFromElastic = $this->jobMapper->get($jobId);
        $jobFromElastic->setUsage([
            [
                'metric' => 'documents',
                'value' => 234,
            ]
        ]);
        $this->jobMapper->update($jobFromElastic);
    }
}
