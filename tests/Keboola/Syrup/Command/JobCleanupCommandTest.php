<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 09/03/15
 * Time: 16:02
 */

namespace Keboola\Syrup\Tests\Command;

use Keboola\Syrup\Command\JobCleanupCommand;
use Keboola\Syrup\Job\Metadata\Job;
use Keboola\Syrup\Test\CommandTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class JobCleanupCommandTest extends CommandTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->application->add(new JobCleanupCommand());
    }

    public function testCleanup()
    {
        // job execution test
        $jobId = $this->jobMapper->create($this->createJob());

        $command = $this->application->find('syrup:job:cleanup');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'jobId' => $jobId
        ]);

        $this->assertEquals(0, $commandTester->getStatusCode());

        $job = $this->jobMapper->get($jobId);
        $this->assertEquals($job->getStatus(), Job::STATUS_TERMINATED);
        $this->assertEquals('cleaned&cleared', $job->getResult()['message']);
    }
}
