<?php
/**
 * @package syrup-component-bundle
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace Keboola\Syrup\Tests\Elasticsearch;

use Elasticsearch\Client;
use Keboola\Syrup\Elasticsearch\Index;
use Keboola\Syrup\Elasticsearch\Job as ElasticsearchJob;
use Keboola\Syrup\Encryption\Encryptor;
use Keboola\Syrup\Job\Metadata\Job;
use Keboola\Syrup\Job\Metadata\JobFactory;
use Keboola\Syrup\Job\Metadata\JobInterface;

class JobTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Client
     */
    private static $client;
    /**
     * @var Index
     */
    private static $index;
    /**
     * @var JobFactory
     */
    private static $jobFactory;
    /**
     * @var ElasticsearchJob
     */
    private static $job;

    public static function setUpBeforeClass()
    {
        self::$client = new Client(['hosts' => [SYRUP_ELASTICSEARCH_HOST]]);
        self::$index = new Index(SYRUP_APP_NAME, 'devel', self::$client);
        self::$jobFactory = new JobFactory(SYRUP_APP_NAME, new Encryptor(md5(uniqid())));
        self::$jobFactory->setStorageApiClient(new \Keboola\StorageApi\Client(['token' => SYRUP_SAPI_TEST_TOKEN]));
        self::$job = new ElasticsearchJob(self::$client, self::$index);
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
    }

    public function testCreateJob()
    {
        $job = self::$jobFactory->create(uniqid());
        $id = self::$job->create($job);

        $res = self::$client->get([
            'index' => self::$index->getIndexNameCurrent(),
            'type'  => 'jobs',
            'id'    => $id
        ]);

        $resJob = $res['_source'];

        $this->assertJob($job, $resJob);
    }

    public function testGetJob()
    {
        $job = self::$jobFactory->create(uniqid());
        $id = self::$job->create($job);

        $resJob = self::$job->get($id);

        $this->assertJob($job, $resJob->getData());
    }

    public function testUpdateJob()
    {
        $job = self::$jobFactory->create(uniqid());
        $id = self::$job->create($job);

        $job = self::$job->get($id);

        $job->setStatus(Job::STATUS_CANCELLED);

        self::$job->update($job);

        $job = self::$job->get($id);

        $this->assertEquals($job->getStatus(), Job::STATUS_CANCELLED);


        $job->setStatus(Job::STATUS_WARNING);

        self::$job->update($job);

        $job = self::$job->get($id);

        $this->assertEquals($job->getStatus(), Job::STATUS_WARNING);
    }
}
