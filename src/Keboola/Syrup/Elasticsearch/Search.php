<?php
/**
 * @package syrup
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */
namespace Keboola\Syrup\Elasticsearch;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\ServerErrorResponseException;
use Keboola\Syrup\Job\Metadata\Job;
use Keboola\Syrup\Service\ObjectEncryptor;
use Monolog\Logger;

class Search
{
    /** @var Client */
    protected $client;

    protected $indexPrefix;

    /** @var Logger */
    protected $logger;

    /**
     * @var ObjectEncryptor
     */
    protected $configEncryptor;

    public function __construct(Client $client, $indexPrefix, ObjectEncryptor $configEncryptor, $logger = null)
    {
        $this->client = $client;
        $this->indexPrefix = $indexPrefix;
        $this->configEncryptor = $configEncryptor;
        $this->logger = $logger;
    }


    public function getJob($jobId)
    {
        $indices = $this->getIndices();

        $docs = [];
        foreach ($indices as $index) {
            $docs[] = [
                '_index' => $index,
                '_type' => 'jobs',
                '_id' => $jobId
            ];
        }

        $i = 0;
        while ($i < 5) {
            try {
                $result = $this->client->mget([
                    'body' => ['docs' => $docs]
                ]);

                foreach ($result['docs'] as $doc) {
                    if (isset($doc['found']) && $doc['found'] === true) {
                        return new Job($this->configEncryptor, $doc['_source'], $doc['_index'], $doc['_type'], $doc['_version']);
                    }
                }

                return null;
            } catch (ServerErrorResponseException $e) {
                // ES server error, try again
                $this->log('error', 'Elastic server error response', [
                    'attemptNo' => $i,
                    'jobId' => $jobId,
                    'exception' => $e
                ]);
            }

            sleep(1 + intval(pow(2, $i)/2));
            $i++;
        }

        return null;
    }

    /**
     * @param array $params array of searching params:
     * - projectId
     * - component
     * - runId
     * - query
     * - since
     * - until
     * - offset
     * - limit
     * - status
     * @return array
     */
    public function getJobs(array $params)
    {
        $projectId = null;
        $component = null;
        $runId = null;
        $query = null;
        $since = null;
        $until = null;
        $offset = 0;
        $limit = 100;
        $status = null;
        extract($params);

        $filter = [];

        if ($projectId !== null) {
            $filter[] = ['term' => ['project.id' => $projectId]];
        }

        if ($runId !== null) {
            $filter[] = ['term' => ['runId' => $runId]];
        }

        if ($status !== null) {
            $filter[] = ['term' => ['status' => $status]];
        }

        if ($component !== null) {
            $filter[] = ['term' => ['component' => $component]];
        }

        $queryParam = ['match_all' => []];
        if ($query != null) {
            $queryParam = [
                'query_string' => [
                    'allow_leading_wildcard' => 'false',
                    'default_operator' => 'AND',
                    'query' => $query
                ]
            ];
        }

        $rangeFilter = [];
        if ($since != null) {
            if ($until == null) {
                $until = 'now';
            }

            $rangeFilter = [
                'range' => [
                    'createdTime'  => [
                        'gte' => date('c', strtotime($since)),
                        'lte' => date('c', strtotime($until)),
                    ]
                ]
            ];
        }

        if (!empty($rangeFilter)) {
            $filter[] = $rangeFilter;
        }

        $params = [
            'index' => $this->indexPrefix . '_syrup_*',
            'body' => [
                'from' => $offset,
                'size' => $limit,
                'query' => [
                    'filtered' => [
                        'filter' => [
                            'bool' => [
                                'must' => $filter
                            ]
                        ],
                        'query' => $queryParam
                    ]
                ],
                'sort' => [
                    'id' => [
                        'order' => 'desc'
                    ]
                ]
            ]
        ];

        $results = [];
        $i = 0;
        while ($i < 5) {
            try {
                $hits = $this->client->search($params);

                foreach ($hits['hits']['hits'] as $hit) {
                    $res = $hit['_source'];
                    $res['_index'] = $hit['_index'];
                    $res['_type'] = $hit['_type'];
                    $res['id'] = (int) $res['id'];
                    $results[] = $res;
                }

                return $results;
            } catch (ServerErrorResponseException $e) {
                // ES server error, try again
                $this->log('error', 'Elastic server error response', [
                    'attemptNo' => $i,
                    'params' => $params,
                    'exception' => $e
                ]);
            }

            sleep(1 + intval(pow(2, $i)/2));
            $i++;
        }

        return [];
    }

    public function getIndices()
    {
        $i = 0;
        while (true) {
            try {
                $indices = $this->client->indices()->get([
                    'index' => $this->indexPrefix . '_syrup*'
                ]);
                if (!empty($indices)) {
                    return array_keys($indices);
                }
                return [];
            } catch (ServerErrorResponseException $e) {

                if ($i > 5) {
                    throw $e;
                }

                // ES server error, try again
                $this->log('error', 'Elastic server error response', [
                    'attemptNo' => $i,
                    'exception' => $e
                ]);

            }

            sleep(1 + intval(pow(2, $i)/2));
            $i++;
        }
    }

    protected function log($level, $message, $context = [])
    {
        // do nothing if logger is null
        if ($this->logger != null) {
            $this->logger->$level($message, $context);
        }
    }
}
