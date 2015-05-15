<?php
/**
 * @package syrup
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace Keboola\Syrup\Elasticsearch;

use Elasticsearch\Client;
use Keboola\Syrup\Exception\ApplicationException;
use Keboola\Syrup\Job\Metadata\Job;
use Keboola\Syrup\Job\Metadata\JobInterface;

class JobMapper
{
    /**
     * @var Client
     */
    protected $client;
    /**
     * @var ComponentIndex
     */
    protected $index;


    public function __construct(Client $client, ComponentIndex $index)
    {
        $this->client = $client;
        $this->index = $index;
    }

    /**
     * @param JobInterface $job
     * @return string jobId
     */
    public function create(JobInterface $job)
    {
        $job->validate();

        $jobData = [
            'index' => $this->index->getIndexNameCurrent(),
            'type'  => 'jobs',
            'id'    => $job->getId(),
            'body'  => $job->getData()
        ];

        $response = $this->client->index($jobData);

        if (!isset($response['created'])) {
            throw new ApplicationException("Unable to index job", null, [
                'job' => $jobData,
                'elasticResponse' => $response
            ]);
        }

        //@todo: remove sleep in next (major) release
        sleep(1);
        $i = 0;
        while ($i < 5) {
            $resJob = $this->get($job->getId());

            if ($resJob != null) {
                return $response['_id'];
            }
            sleep(1 + intval(pow(2, $i)/2));
            $i++;
        }

        throw new ApplicationException("Unable to find the job in index", null, [
            'job' => $job->getData(),
            'elasticResponse' => $response
        ]);
    }

    /**
     * @param JobInterface $job
     * @return string jobId
     */
    public function update(JobInterface $job)
    {
        $job->validate();

        $jobData = [
            'index' => $job->getIndex(),
            'type'  => $job->getType(),
            'id'    => $job->getId(),
            'body'  => [
                'doc'   => $job->getData()
            ]
        ];

        $response = $this->client->update($jobData);

        //@todo: remove sleep in next (major) release
        sleep(1);
        $i = 0;
        while ($i < 5) {
            $resJob = $this->get($job->getId());
            if ($resJob != null && $resJob->getVersion() >= $response['_version']) {
                return $response['_id'];
            }
            sleep(1 + intval(pow(2, $i)/2));
            $i++;
        }

        throw new ApplicationException("Unable to find job after update", null, [
            'job' => $job->getData(),
            'elasticResponse' => $response
        ]);
    }

    public function get($jobId)
    {
        $indices = $this->index->getIndices();

        $docs = [];
        foreach ($indices as $index) {
            $docs[] = [
                '_index' => $index,
                '_type' => 'jobs',
                '_id' => $jobId
            ];
        }

        //@todo: backoff
        $result = $this->client->mget([
            'body' => ['docs' => $docs]
        ]);

        foreach ($result['docs'] as $doc) {
            if ($doc['found']) {
                return new Job($doc['_source'], $doc['_index'], $doc['_type'], $doc['_version']);
            }
        }

        return null;
    }
}
