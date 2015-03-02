<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 02/03/15
 * Time: 14:52
 */

namespace Keboola\Syrup\Test\Job\Executor;

use Keboola\Syrup\Job\Metadata\Job;

class Executor extends \Keboola\Syrup\Job\Executor
{
    public function execute(Job $job)
    {
        // simulate long running job
        sleep(180);
    }
}
