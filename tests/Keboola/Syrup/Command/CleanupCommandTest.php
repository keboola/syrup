<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 09/03/15
 * Time: 16:02
 */

namespace Keboola\Syrup\Tests\Command;

use Keboola\Syrup\Command\CleanupCommand;
use Keboola\Syrup\Elasticsearch\JobMapper;
use Keboola\Syrup\Job\Metadata\Job;
use Keboola\Syrup\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Keboola\StorageApi\Client as StorageApiClient;
use Symfony\Component\Console\Tester\CommandTester;

class CleanupCommandTest extends WebTestCase
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
        $this->application->add(new CleanupCommand());

        $this->storageApiToken = self::$kernel->getContainer()->getParameter('storage_api.test.token');
        $this->storageApiClient = new StorageApiClient([
            'token' => $this->storageApiToken,
            'url' => self::$kernel->getContainer()->getParameter('storage_api.test.url')
        ]);
    }

    public function testCleanup()
    {
        /** @var JobMapper $jobMapper */
        $jobMapper = self::$kernel->getContainer()->get('syrup.elasticsearch.current_component_job_mapper');
        $encryptedToken = self::$kernel->getContainer()->get('syrup.encryptor')->encrypt($this->storageApiToken);

        // job execution test
        $jobId = $jobMapper->create($this->createJob($encryptedToken));

        $command = $this->application->find('syrup:cleanup');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'jobId'   => $jobId
        ]);

        $this->assertEquals(0, $commandTester->getStatusCode());

        $job = $jobMapper->get($jobId);
        $this->assertEquals($job->getStatus(), Job::STATUS_TERMINATED);
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
