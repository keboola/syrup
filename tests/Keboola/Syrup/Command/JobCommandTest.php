<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 23/10/14
 * Time: 16:53
 */
namespace Keboola\Syrup\Tests\Command;

use Keboola\Syrup\Test\CommandTestCase;
use Keboola\Syrup\Test\Job\Executor\ErrorExecutor;
use Keboola\Syrup\Test\Job\Executor\HookExecutor;
use Keboola\Syrup\Test\Job\Executor\MaintenanceExecutor;
use Keboola\Syrup\Test\Job\Executor\SuccessExecutor;
use Keboola\Syrup\Test\Job\Executor\UserErrorExecutor;
use Keboola\Syrup\Test\Job\Executor\WarningExecutor;
use Keboola\Syrup\Test\Job\Executor\UsageUpdateExecutor;
use Symfony\Component\Console\Tester\CommandTester;
use Keboola\Syrup\Command\JobCommand;
use Keboola\Syrup\Job\Metadata\Job;
use Keboola\Syrup\Tests\Job as TestExecutor;

class JobCommandTest extends CommandTestCase
{
    /**
     * @var CommandTester
     */
    protected $commandTester;

    protected function setUp()
    {
        parent::setUp();
        $this->application->add(new JobCommand());

        // use old (alias) name of the job command to test BC
        $command = $this->application->find('syrup:run-job');
        $this->commandTester = new CommandTester($command);
    }

    public function testRunJob()
    {
        // job execution test
        $jobId = $this->jobMapper->create($this->createJob());
        $this->commandTester->execute(['jobId'   => $jobId]);
        $this->assertEquals(0, $this->commandTester->getStatusCode());

        $job = $this->jobMapper->get($jobId);
        $this->assertEquals($job->getStatus(), Job::STATUS_SUCCESS);
        $this->assertEmpty($job->getResult());
    }

    public function testRunJobWithWarning()
    {
        self::$kernel->getContainer()->set('syrup.job_executor', new WarningExecutor());
        $jobId = $this->jobMapper->create($this->createJob());
        $this->commandTester->execute(['jobId'   => $jobId]);
        $this->assertEquals(0, $this->commandTester->getStatusCode());

        $job = $this->jobMapper->get($jobId);
        $this->assertArrayHasKey('testing', $job->getResult());
        $this->assertArrayHasKey('context', $job->getResult());
        $this->assertArrayHasKey('key', $job->getResult()['context']);
        $this->assertEquals($job->getStatus(), Job::STATUS_WARNING);
    }

    public function testRunJobWithSuccess()
    {
        self::$kernel->getContainer()->set('syrup.job_executor', new SuccessExecutor());
        $jobId = $this->jobMapper->create($this->createJob());
        $this->commandTester->execute(['jobId'   => $jobId]);
        $this->assertEquals(0, $this->commandTester->getStatusCode());

        $job = $this->jobMapper->get($jobId);
        $this->assertArrayHasKey('testing', $job->getResult());
        $this->assertArrayNotHasKey('context', $job->getResult());
        $this->assertEquals($job->getStatus(), Job::STATUS_SUCCESS);
    }

    public function testRunJobWithError()
    {
        self::$kernel->getContainer()->set('syrup.job_executor', new ErrorExecutor());
        $jobId = $this->jobMapper->create($this->createJob());
        $this->commandTester->execute(['jobId'   => $jobId]);
        $this->assertEquals(0, $this->commandTester->getStatusCode());

        $job = $this->jobMapper->get($jobId);
        $this->assertArrayHasKey('testing', $job->getResult());
        $this->assertArrayHasKey('context', $job->getResult());
        $this->assertArrayHasKey('key', $job->getResult()['context']);
        $this->assertEquals($job->getStatus(), Job::STATUS_ERROR);
    }

    public function testRunJobWithUserException()
    {
        self::$kernel->getContainer()->set('syrup.job_executor', new UserErrorExecutor());
        $jobId = $this->jobMapper->create($this->createJob());
        $this->commandTester->execute(['jobId'   => $jobId]);
        $this->assertEquals(0, $this->commandTester->getStatusCode());

        $job = $this->jobMapper->get($jobId);
        $this->assertArrayHasKey('context', $job->getResult());
        $this->assertArrayHasKey('key', $job->getResult()['context']);
        $this->assertEquals($job->getStatus(), Job::STATUS_ERROR);
    }

    public function testRunJobWithHook()
    {
        self::$kernel->getContainer()->set('syrup.job_executor', new HookExecutor($this->jobMapper));
        $jobId = $this->jobMapper->create($this->createJob());
        $this->commandTester->execute(['jobId'   => $jobId]);
        $this->assertEquals(0, $this->commandTester->getStatusCode());

        $job = $this->jobMapper->get($jobId);
        $result = $job->getResult();
        $this->assertArrayHasKey('testing', $result);
        $this->assertArrayHasKey(HookExecutor::HOOK_RESULT_KEY, $result);
        $this->assertEquals(HookExecutor::HOOK_RESULT_VALUE, $result[HookExecutor::HOOK_RESULT_KEY]);
        $this->assertEquals($job->getStatus(), Job::STATUS_SUCCESS);
        $this->assertArrayNotHasKey('context', $job->getResult());
    }

    public function testRunJobWithMaintenance()
    {
        self::$kernel->getContainer()->set('syrup.job_executor', new MaintenanceExecutor());
        $jobId = $this->jobMapper->create($this->createJob());
        $this->commandTester->execute(['jobId'   => $jobId]);
        $this->assertEquals(JobCommand::STATUS_LOCK, $this->commandTester->getStatusCode());
    }

    public function testRunJobWithExternalUsageUpdate()
    {
        self::$kernel->getContainer()->set('syrup.job_executor', new UsageUpdateExecutor($this->jobMapper));
        $jobId = $this->jobMapper->create($this->createJob());
        $this->commandTester->execute(['jobId'   => $jobId]);
        $this->assertEquals(0, $this->commandTester->getStatusCode());

        $job = $this->jobMapper->get($jobId);
        $this->assertArrayHasKey('testing', $job->getResult());
        $this->assertEquals($job->getStatus(), Job::STATUS_SUCCESS);

        $this->assertEquals([
            [
                'metric' => 'documents',
                'value' => 234,
            ]
        ], $job->getUsage());
        $this->isEmpty('context', $job->getResult());
    }
}
