<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 02/03/15
 * Time: 14:52
 */

namespace Keboola\Syrup\Test\Job\Executor;

use Keboola\Syrup\Elasticsearch\JobMapper;
use Keboola\Syrup\Job\Metadata\Job;
use Monolog\Logger;

class Executor extends \Keboola\Syrup\Job\Executor
{
    /** @var Logger */
    protected $logger;

    /** @var JobMapper */
    protected $jobMapper;

    public function __construct($logger, $jobMapper)
    {
        $this->logger = $logger;
        $this->jobMapper = $jobMapper;
    }

    public function execute(Job $job)
    {
        // simulate long running job
        for ($i=0; $i<20; $i++) {
            // this will trigger pcntl_signal_dispatch()
            $this->logger->info("I'm running!");

            sleep(3);
        }
    }

    public function cleanup(Job $job)
    {
        $job->setResult(['message' => 'cleaned']);

        $this->jobMapper->update($job);
    }

    public function postCleanup(Job $job)
    {
        $oldRes = $job->getResult();
        $job->setResult(['message' => $oldRes['message'] . '&cleared']);

        $this->jobMapper->update($job);
    }
}
