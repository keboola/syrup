<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 23/10/14
 * Time: 16:53
 */
namespace Keboola\Syrup\Tests\Command;

use Keboola\Syrup\Test\Job\Executor\ErrorExecutor;
use Keboola\Syrup\Test\Job\Executor\HookExecutor;
use Keboola\Syrup\Test\Job\Executor\SuccessExecutor;
use Keboola\Syrup\Test\Job\Executor\WarningExecutor;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Keboola\Syrup\Test\WebTestCase;
use Keboola\Syrup\Command\JobCommand;
use Keboola\Syrup\Job\Metadata\Job;
use Keboola\Syrup\Tests\Job as TestExecutor;
use Keboola\Syrup\Elasticsearch\Job as ElasticsearchJob;

class JobCommandTest extends WebTestCase
{
    /**
     * @var Application
     */
    protected $application;

    protected function setUp()
    {
        $this->bootKernel();

        $this->application = new Application(self::$kernel);
        $this->application->add(new JobCommand());
    }

    public function testRunjob()
    {
        /** @var ElasticsearchJob $elasticsearchJob */
        $elasticsearchJob = self::$kernel->getContainer()->get('syrup.elasticsearch.job');
        $encryptedToken = self::$kernel->getContainer()->get('syrup.encryptor')
            ->encrypt(self::$kernel->getContainer()->getParameter('storage_api.test.token'));

        // job execution test
        $jobId = $elasticsearchJob->create($this->createJob($encryptedToken));

        $command = $this->application->find('syrup:run-job');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'jobId'   => $jobId
            )
        );

        $this->assertEquals(0, $commandTester->getStatusCode());

        $job = $elasticsearchJob->get($jobId);
        $this->assertEquals($job->getStatus(), Job::STATUS_SUCCESS);

        // replace executor with warning executor
        self::$kernel->getContainer()->set('syrup.job_executor', new WarningExecutor());

        $jobId = $elasticsearchJob->create($this->createJob($encryptedToken));

        $this->application->find('syrup:run-job');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'jobId'   => $jobId
            )
        );

        $this->assertEquals(0, $commandTester->getStatusCode());

        $job = $elasticsearchJob->get($jobId);
        $this->assertArrayHasKey('testing', $job->getResult());
        $this->assertEquals($job->getStatus(), Job::STATUS_WARNING);

        // replace executor with success executor
        self::$kernel->getContainer()->set('syrup.job_executor', new SuccessExecutor());

        $jobId = $elasticsearchJob->create($this->createJob($encryptedToken));

        $this->application->find('syrup:run-job');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'jobId'   => $jobId
            )
        );

        $this->assertEquals(0, $commandTester->getStatusCode());

        $job = $elasticsearchJob->get($jobId);
        $this->assertArrayHasKey('testing', $job->getResult());
        $this->assertEquals($job->getStatus(), Job::STATUS_SUCCESS);

        // replace executor with error executor
        self::$kernel->getContainer()->set('syrup.job_executor', new ErrorExecutor());

        $jobId = $elasticsearchJob->create($this->createJob($encryptedToken));

        $this->application->find('syrup:run-job');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'jobId'   => $jobId
            )
        );

        $this->assertEquals(0, $commandTester->getStatusCode());

        $job = $elasticsearchJob->get($jobId);
        $this->assertArrayHasKey('testing', $job->getResult());
        $this->assertEquals($job->getStatus(), Job::STATUS_ERROR);
    }

    public function testRunJobWithHook()
    {
        /** @var ElasticsearchJob $elasticsearchJob */
        $elasticsearchJob = self::$kernel->getContainer()->get('syrup.elasticsearch.job');
        $encryptedToken = self::$kernel->getContainer()->get('syrup.encryptor')
            ->encrypt(self::$kernel->getContainer()->getParameter('storage_api.test.token'));

        self::$kernel->getContainer()->set('syrup.job_executor', new HookExecutor($elasticsearchJob));

        // job execution test
        $jobId = $elasticsearchJob->create($this->createJob($encryptedToken));

        $command = $this->application->find('syrup:run-job');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'jobId'   => $jobId
            )
        );

        $this->assertEquals(0, $commandTester->getStatusCode());

        $job = $elasticsearchJob->get($jobId);

        $result = $job->getResult();

        $this->assertArrayHasKey('testing', $result);
        $this->assertArrayHasKey(HookExecutor::HOOK_RESULT_KEY, $result);
        $this->assertEquals(HookExecutor::HOOK_RESULT_VALUE, $result[HookExecutor::HOOK_RESULT_KEY]);
        $this->assertEquals($job->getStatus(), Job::STATUS_SUCCESS);
    }

    protected function createJob($token)
    {
        return new Job([
            'id' => rand(0, 128),
            'runId' => rand(0, 128),
            'project' => [
                'id' => '123',
                'name' => 'Syrup TEST'
            ],
            'token' => [
                'id' => '123',
                'description' => 'fake token',
                'token' => $token
            ],
            'component' => 'syrup',
            'command' => 'run',
            'params' => [],
            'process' => [
                'host' => gethostname(),
                'pid' => getmypid()
            ],
            'createdTime' => date('c')
        ]);
    }
}
