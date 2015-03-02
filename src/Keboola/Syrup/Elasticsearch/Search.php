<?php
/**
 * @package syrup
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */
namespace Keboola\Syrup\Elasticsearch;

use Elasticsearch\Client;
use Keboola\Syrup\Job\Metadata\Job;

class Search
{
    /**
     * @var Client
     */
    protected $client;
    protected $indexPrefix;


    public function __construct(Client $client, $indexPrefix)
    {
        $this->client = $client;
        $this->indexPrefix = $indexPrefix;
    }


    public function getJob($jobId)
    {
        $params = [
            'index' => $this->indexPrefix . '_syrup*',
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
            $job = new Job(
                $result['hits']['hits'][0]['_source'],
                $result['hits']['hits'][0]['_index'],
                $result['hits']['hits'][0]['_type']
            );

            return $job;
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

        if ($projectId != null) {
            $filter[] = ['term' => ['project.id' => $projectId]];
        }

        if ($runId != null) {
            $filter[] = ['term' => ['runId' => $runId]];
        }

        if ($status != null) {
            $filter[] = ['term' => ['status' => $status]];
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
            'index' => $this->indexPrefix . '_syrup_' . ($component ?: '*'),
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
        $hits = $this->client->search($params);

        foreach ($hits['hits']['hits'] as $hit) {
            $res = $hit['_source'];
            $res['_index'] = $hit['_index'];
            $res['_type'] = $hit['_type'];
            $res['id'] = (int) $res['id'];
            $results[] = $res;
        }

        return $results;
    }
}
