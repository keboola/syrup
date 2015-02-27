<?php
/**
 * @package syrup
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace Keboola\Syrup\Elasticsearch;

use Elasticsearch\Client;
use Keboola\Syrup\Exception\ApplicationException;
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

        $this->client->indices()->refresh([
            'index' => $this->index->getIndexNameCurrent()
        ]);

        return $response['_id'];
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

        $this->client->indices()->refresh([
            'index' => $job->getIndex()
        ]);

        return $response['_id'];
    }

    public function get($jobId)
    {
        $params = [
            'index' => $this->index->getIndexName(),
            'body' => [
                'size'  => 1,
                'query' => [
                    'match_all' => []
                ],
                'filter' => [
                    'ids' => [
                        'values' => [$jobId]
                    ]
                ]
            ]
        ];

        $result = $this->client->search($params);

        if ($result['hits']['total'] > 0) {
            $job = new \Keboola\Syrup\Job\Metadata\Job(
                $result['hits']['hits'][0]['_source'],
                $result['hits']['hits'][0]['_index'],
                $result['hits']['hits'][0]['_type']
            );

            return $job;
        }
        return null;
    }
}
