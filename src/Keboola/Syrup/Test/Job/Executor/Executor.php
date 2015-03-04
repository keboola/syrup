<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 02/03/15
 * Time: 14:52
 */

namespace Keboola\Syrup\Test\Job\Executor;

use Keboola\Syrup\Job\Metadata\Job;
use Monolog\Logger;

class Executor extends \Keboola\Syrup\Job\Executor
{
    /** @var Logger */
    protected $logger;

    public function __construct($logger)
    {
        $this->logger = $logger;
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
}
