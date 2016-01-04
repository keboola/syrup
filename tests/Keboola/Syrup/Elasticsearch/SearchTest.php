<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 11/06/14
 * Time: 16:36
 */
namespace Keboola\Syrup\Tests\Elasticsearch;

use Elasticsearch\Client as ElasticClient;
use Keboola\StorageApi\Client as SapiClient;
use Keboola\Syrup\Elasticsearch\ComponentIndex;
use Keboola\Syrup\Elasticsearch\Search;
use Keboola\Syrup\Service\ObjectEncryptor;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Keboola\Syrup\Job\Metadata\Job;
use Keboola\Syrup\Elasticsearch\JobMapper;

class SearchTest extends WebTestCase
{
    /** @var Search */
    protected static $search;

    /** @var SapiClient */
    protected static $sapiClient;

    /** @var ElasticClient */
    protected static $elasticClient;

    /** @var ComponentIndex */
    protected static $index;

    /** @var JobMapper */
    protected static $jobMapper;

    public function setUp()
    {
        self::bootKernel();
    }

    public static function setUpBeforeClass()
    {
        self::$kernel = static::createKernel();
        self::$kernel->boot();

        self::$elasticClient = self::$kernel->getContainer()->get('syrup.elasticsearch.client');

        self::$search = self::$kernel->getContainer()->get('syrup.elasticsearch.search');
        self::$index = self::$kernel->getContainer()->get('syrup.elasticsearch.current_component_index');
        self::$jobMapper = self::$kernel->getContainer()->get('syrup.elasticsearch.current_component_job_mapper');

        self::$sapiClient = new SapiClient([
            'token' => self::$kernel->getContainer()->getParameter('storage_api.test.token'),
            'url' => self::$kernel->getContainer()->getParameter('storage_api.test.url'),
            'userAgent' => SYRUP_APP_NAME,
        ]);

        // clear data
        $sapiData = self::$sapiClient->getLogData();
        $projectId = $sapiData['owner']['id'];

        $jobs = self::$search->getJobs(['projectId' => $projectId, 'component' => SYRUP_APP_NAME]);
        foreach ($jobs as $job) {
            self::$elasticClient->delete([
                'index' => $job['_index'],
                'type' => $job['_type'],
                'id' => $job['id']
            ]);
        }
    }

    private function createJob()
    {
        $tokenData = self::$sapiClient->verifyToken();
        /** @var ObjectEncryptor $configEncryptor */
        $configEncryptor = self::$kernel->getContainer()->get('syrup.object_encryptor');
        return new Job($configEncryptor, [
                'id'        => self::$sapiClient->generateId(),
                'runId'     => self::$sapiClient->generateId(),
                'project'   => [
                    'id'        => $tokenData['owner']['id'],
                    'name'      => $tokenData['owner']['name']
                ],
                'token'     => [
                    'id'            => $tokenData['id'],
                    'description'   => $tokenData['description'],
                    'token'         => $configEncryptor->encrypt(self::$sapiClient->getTokenString())
                ],
                'component' => SYRUP_APP_NAME,
                'command'   => 'run',
                'process'   => [
                    'host'  => 'test',
                    'pid'   => posix_getpid()
                ],
                'createdTime'   => date('c')
            ], null, null, null);
    }

    private function assertJob(Job $job, $resJob)
    {
        $this->assertEquals($job->getId(), $resJob['id']);
        $this->assertEquals($job->getRunId(), $resJob['runId']);
        $this->assertEquals($job->getLockName(), $resJob['lockName']);

        $this->assertEquals($job->getProject()['id'], $resJob['project']['id']);
        $this->assertEquals($job->getProject()['name'], $resJob['project']['name']);

        $this->assertEquals($job->getToken()['id'], $resJob['token']['id']);
        $this->assertEquals($job->getToken()['description'], $resJob['token']['description']);
        $this->assertEquals($job->getToken()['token'], $resJob['token']['token']);

        $this->assertEquals($job->getComponent(), $resJob['component']);
        $this->assertEquals($job->getStatus(), $resJob['status']);
    }

    public function testGetJob()
    {
        $job = $this->createJob();
        $id = self::$jobMapper->create($job);

        $resJob = self::$search->getJob($id);

        $this->assertJob($job, $resJob->getData());
    }

    public function testGetJobs()
    {
        $job = $this->createJob();
        self::$jobMapper->create($job);

        $job2 = $this->createJob();
        self::$jobMapper->create($job2);

        $retries = 0;

        $res = [];
        while ($retries < 7) {
            $delaySecs = 2 * pow(2, $retries);
            sleep($delaySecs);
            $retries++;

            $projectId = $job->getProject()['id'];

            $res = self::$search->getJobs([
                'projectId' => $projectId,
                'component' => SYRUP_APP_NAME,
                'since' => '-1 day',
                'until' => 'now'
            ]);

            if (count($res) >= 2) {
                break;
            }
        }

        $job1Asserted = false;
        $job2Asserted = false;

        foreach ($res as $r) {
            if ($r['id'] == $job->getId()) {
                $this->assertJob($job, $r);
                $job1Asserted = true;
            }
            if ($r['id'] == $job2->getId()) {
                $this->assertJob($job2, $r);
                $job2Asserted = true;
            }
        }

        $this->assertTrue($job1Asserted);
        $this->assertTrue($job2Asserted);
    }

    public function testGetIndices()
    {
        $indices = self::$search->getIndices();

        $this->assertNotEmpty($indices);
    }
}
