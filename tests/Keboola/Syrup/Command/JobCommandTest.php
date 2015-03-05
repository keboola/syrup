<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 23/10/14
 * Time: 16:53
 */
namespace Keboola\Syrup\Tests\Command;

use Keboola\Syrup\Test\Job\Executor\ErrorExecutor;
use Keboola\Syrup\Test\Job\Executor\HookExecutor;
use Keboola\Syrup\Test\Job\Executor\MaintenanceExecutor;
use Keboola\Syrup\Test\Job\Executor\SuccessExecutor;
use Keboola\Syrup\Test\Job\Executor\WarningExecutor;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Keboola\Syrup\Test\WebTestCase;
use Keboola\Syrup\Command\JobCommand;
use Keboola\Syrup\Job\Metadata\Job;
use Keboola\Syrup\Tests\Job as TestExecutor;
use Keboola\Syrup\Elasticsearch\JobMapper;
use Keboola\StorageApi\Client as StorageApiClient;

/**
 * @covers \Keboola\Syrup\Command\JobCommand
 */
class JobCommandTest extends WebTestCase
{
    /**
     * @var Application
     */
    protected $application;
    protected $storageApiToken;
    /**
     * @var StorageApiClient;
     */
    protected $storageApiClient;
    /**
     * @var JobMapper $jobMapper
     */
    protected $jobMapper;
    /**
     * @var CommandTester
     */
    protected $commandTester;


    protected function setUp()
    {
        $this->bootKernel();

        $this->application = new Application(self::$kernel);
        $this->application->add(new JobCommand());

        $this->storageApiToken = self::$kernel->getContainer()->getParameter('storage_api.test.token');
        $this->storageApiClient = new StorageApiClient([
            'token' => $this->storageApiToken,
            'url' => self::$kernel->getContainer()->getParameter('storage_api.test.url')
        ]);

        /** @var JobMapper $jobMapper */
        $this->jobMapper = self::$kernel->getContainer()->get('syrup.elasticsearch.current_component_job_mapper');

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
    }

    public function testRunJobWithWarning()
    {
        self::$kernel->getContainer()->set('syrup.job_executor', new WarningExecutor());
        $jobId = $this->jobMapper->create($this->createJob());
        $this->commandTester->execute(['jobId'   => $jobId]);
        $this->assertEquals(0, $this->commandTester->getStatusCode());

        $job = $this->jobMapper->get($jobId);
        $this->assertArrayHasKey('testing', $job->getResult());
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
    }

    public function testRunJobWithMaintenance()
    {
        self::$kernel->getContainer()->set('syrup.job_executor', new MaintenanceExecutor());
        $jobId = $this->jobMapper->create($this->createJob());
        $this->commandTester->execute(['jobId'   => $jobId]);
        $this->assertEquals(JobCommand::STATUS_LOCK, $this->commandTester->getStatusCode());
    }

    protected function createJob()
    {
        return new Job([
            'id' => $this->storageApiClient->generateId(),
            'runId' => $this->storageApiClient->generateId(),
            'project' => [
                'id' => '123',
                'name' => 'Syrup TEST'
            ],
            'token' => [
                'id' => '123',
                'description' => 'fake token',
                'token' => self::$kernel->getContainer()->get('syrup.encryptor')->encrypt($this->storageApiToken)
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
