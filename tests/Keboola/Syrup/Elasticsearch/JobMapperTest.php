<?php
/**
 * @package syrup-component-bundle
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace Keboola\Syrup\Tests\Elasticsearch;

use Elasticsearch\Client;
use Keboola\Syrup\Elasticsearch\ComponentIndex;
use Keboola\Syrup\Elasticsearch\JobMapper;
use Keboola\Syrup\Encryption\Encryptor;
use Keboola\Syrup\Job\Metadata\Job;
use Keboola\Syrup\Job\Metadata\JobFactory;
use Keboola\Syrup\Job\Metadata\JobInterface;

class JobMapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Client
     */
    private static $client;
    /**
     * @var ComponentIndex
     */
    private static $index;
    /**
     * @var JobFactory
     */
    private static $jobFactory;
    /**
     * @var JobMapper
     */
    private static $jobMapper;

    public static function setUpBeforeClass()
    {
        self::$client = new Client(['hosts' => [SYRUP_ELASTICSEARCH_HOST]]);
        self::$index = new ComponentIndex(SYRUP_APP_NAME, 'devel', self::$client);
        self::$jobFactory = new JobFactory(SYRUP_APP_NAME, new Encryptor(md5(uniqid())));
        self::$jobFactory->setStorageApiClient(new \Keboola\StorageApi\Client(['token' => SYRUP_SAPI_TEST_TOKEN]));
        self::$jobMapper = new JobMapper(self::$client, self::$index);
    }

    private function assertJob(JobInterface $job, $resJob)
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

        $this->assertEquals(substr_count($job->getRunId(), '.'), $resJob['nestingLevel']);
    }

    public function testCreateJob()
    {
        $job = self::$jobFactory->create(uniqid());
        $id = self::$jobMapper->create($job);

        $res = self::$client->get([
            'index' => self::$index->getIndexNameCurrent(),
            'type'  => 'jobs',
            'id'    => $id
        ]);

        $resJob = $res['_source'];
        $job = self::$jobMapper->get($id);
        $this->assertJob($job, $resJob);
        $this->assertEquals($job->getVersion(), $res['_version']);
    }

    public function testGetJob()
    {
        $job = self::$jobFactory->create(uniqid());
        $id = self::$jobMapper->create($job);
        $resJob = self::$jobMapper->get($id);
        $this->assertJob($job, $resJob->getData());
    }

    public function testUpdateJob()
    {
        $job = self::$jobFactory->create(uniqid());
        $id = self::$jobMapper->create($job);

        $job = self::$jobMapper->get($id);
        $job->setStatus(Job::STATUS_CANCELLED);
        self::$jobMapper->update($job);
        $job = self::$jobMapper->get($id);
        $this->assertEquals($job->getStatus(), Job::STATUS_CANCELLED);

        $job->setStatus(Job::STATUS_WARNING);
        self::$jobMapper->update($job);
        $job = self::$jobMapper->get($id);
        $this->assertEquals($job->getStatus(), Job::STATUS_WARNING);
    }
}
