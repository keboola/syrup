<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 19/03/15
 * Time: 12:31
 */

namespace Keboola\Syrup\Tests\Command;

use Keboola\Syrup\Command\JobCommand;
use Keboola\Syrup\Command\JobCreateCommand;
use Keboola\Syrup\Job\Metadata\Job;
use Keboola\Syrup\Test\CommandTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class JobCreateCommandTest extends CommandTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->application->add(new JobCreateCommand());
        $this->application->add(new JobCommand());
    }

    public function testCreateJob()
    {
        $command = $this->application->find('syrup:job:create');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'token' => $this->storageApiToken,
            'cmd' => 'run'
        ]);

        $this->assertEquals(0, $commandTester->getStatusCode());

        $jobId = intval(str_replace('Created job id ', '', $commandTester->getDisplay()));
        $job = $this->jobMapper->get($jobId);
        $this->assertEquals($job->getStatus(), Job::STATUS_WAITING);
    }
}
