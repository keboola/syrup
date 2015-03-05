<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 23/10/14
 * Time: 16:53
 */
namespace Keboola\Syrup\Tests\Command;

use Keboola\Syrup\Test\Job\Executor\ErrorExecutor;
use Keboola\Syrup\Test\Job\Executor\HookExecutor;
use Keboola\Syrup\Test\Job\Executor\SignaledExectuor;
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
use Symfony\Component\Process\Process;

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
    }

    public function testSignalJob()
    {
        if (!extension_loaded('pcntl')) {
            $this->markTestSkipped("No pcntl extension, skipping...");
        }

        /** @var JobMapper $jobMapper */
        $jobMapper = self::$kernel->getContainer()->get('syrup.elasticsearch.current_component_job_mapper');
        $encryptedToken = self::$kernel->getContainer()->get('syrup.encryptor')->encrypt($this->storageApiToken);

        // job execution test
        $jobId = $jobMapper->create($this->createJob($encryptedToken));

        $process = new Process(self::$kernel->getRootDir() . '/console syrup:run-job ' . $jobId . ' --env=test');
        $process->setTimeout(60);
        $process->setIdleTimeout(60);
        $process->start();

        // let it run for a while
        sleep(10);

        $job = $jobMapper->get($jobId);
        $i=0;
        while ($job->getStatus() != Job::STATUS_PROCESSING && $i<10) {
            sleep(2);
            $i++;
        }

        // make sure the job is in processing state
        $this->assertEquals(Job::STATUS_PROCESSING, $job->getStatus());

        // terminate the job
        $process->signal(SIGTERM);

        while ($process->isRunning()) {
            // waiting for process to finish
            sleep(2);
        }

        $job = $jobMapper->get($jobId);

        $i = 0;
        while ($job->getVersion() < 3 && $i<5) {
            $job = $jobMapper->get($jobId);
            sleep(1 + pow(2, $i)/2);
            $i++;
        }

        var_dump($process->getOutput());
        var_dump($process->getErrorOutput());

        var_dump("exit code " . $process->getExitCodeText() . ' ' . $process->getExitCode());

        $this->assertEquals(Job::STATUS_TERMINATED, $job->getStatus());
    }

    public function testRunjob()
    {
        /** @var JobMapper $jobMapper */
        $jobMapper = self::$kernel->getContainer()->get('syrup.elasticsearch.current_component_job_mapper');
        $encryptedToken = self::$kernel->getContainer()->get('syrup.encryptor')->encrypt($this->storageApiToken);

        // job execution test
        $jobId = $jobMapper->create($this->createJob($encryptedToken));

        $command = $this->application->find('syrup:run-job');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'jobId'   => $jobId
        ]);

        $this->assertEquals(0, $commandTester->getStatusCode());

        $job = $jobMapper->get($jobId);
        $this->assertEquals($job->getStatus(), Job::STATUS_SUCCESS);

        // replace executor with warning executor
        self::$kernel->getContainer()->set('syrup.job_executor', new WarningExecutor());

        $jobId = $jobMapper->create($this->createJob($encryptedToken));

        echo "warning executor" . PHP_EOL;
        $command = $this->application->find('syrup:run-job');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'jobId'   => $jobId
        ]);

        $this->assertEquals(0, $commandTester->getStatusCode());

        $job = $jobMapper->get($jobId);
        $this->assertArrayHasKey('testing', $job->getResult());
        $this->assertEquals($job->getStatus(), Job::STATUS_WARNING);

        // replace executor with success executor
        self::$kernel->getContainer()->set('syrup.job_executor', new SuccessExecutor());

        $jobId = $jobMapper->create($this->createJob($encryptedToken));

        echo "success executor" . PHP_EOL;
        $command = $this->application->find('syrup:run-job');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'jobId'   => $jobId
        ]);

        $this->assertEquals(0, $commandTester->getStatusCode());

        $job = $jobMapper->get($jobId);
        $this->assertArrayHasKey('testing', $job->getResult());
        $this->assertEquals($job->getStatus(), Job::STATUS_SUCCESS);

        // replace executor with error executor
        self::$kernel->getContainer()->set('syrup.job_executor', new ErrorExecutor());

        $jobId = $jobMapper->create($this->createJob($encryptedToken));

        echo "error executor" . PHP_EOL;
        $command = $this->application->find('syrup:run-job');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'jobId'   => $jobId
        ]);

        $this->assertEquals(0, $commandTester->getStatusCode());

        $job = $jobMapper->get($jobId);
        $this->assertArrayHasKey('testing', $job->getResult());
        $this->assertEquals($job->getStatus(), Job::STATUS_ERROR);
    }

    public function testRunJobWithHook()
    {
        /** @var JobMapper $jobMapper */
        $jobMapper = self::$kernel->getContainer()->get('syrup.elasticsearch.current_component_job_mapper');
        $encryptedToken = self::$kernel->getContainer()->get('syrup.encryptor')
            ->encrypt(self::$kernel->getContainer()->getParameter('storage_api.test.token'));

        self::$kernel->getContainer()->set('syrup.job_executor', new HookExecutor($jobMapper));

        // job execution test
        $jobId = $jobMapper->create($this->createJob($encryptedToken));

        $command = $this->application->find('syrup:run-job');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'jobId'   => $jobId
        ]);

        $this->assertEquals(0, $commandTester->getStatusCode());

        $job = $jobMapper->get($jobId);

        $result = $job->getResult();

        $this->assertArrayHasKey('testing', $result);
        $this->assertArrayHasKey(HookExecutor::HOOK_RESULT_KEY, $result);
        $this->assertEquals(HookExecutor::HOOK_RESULT_VALUE, $result[HookExecutor::HOOK_RESULT_KEY]);
        $this->assertEquals($job->getStatus(), Job::STATUS_SUCCESS);
    }

    protected function createJob($token)
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
