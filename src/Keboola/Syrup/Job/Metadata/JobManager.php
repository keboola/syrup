<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 28/05/14
 * Time: 14:29
 */

namespace Keboola\Syrup\Job\Metadata;

use Elasticsearch\Client as ElasticsearchClient;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Keboola\StorageApi\Client;
use Keboola\Syrup\Encryption\Encryptor;
use Keboola\Syrup\Exception\ApplicationException;

class JobManager
{
    const PAGING = 100;

    /**
     * @var ElasticsearchClient
     */
    protected $client;

    protected $config;

    protected $componentName;

    /**
     * @var Encryptor
     */
    protected $encryptor;

    /**
     * @var \Keboola\StorageApi\Client
     */
    protected $storageApiClient;

    public function __construct(ElasticsearchClient $client, array $config, $componentName, Encryptor $encryptor)
    {
        $this->client = $client;
        $this->config = $config;
        $this->componentName = $componentName;
        $this->encryptor = $encryptor;
    }

    public function setStorageApiClient($client)
    {
        $this->storageApiClient = $client;
    }

    /**
     * @param null $mappings
     * @return string Updated index name
     */
    public function putMappings($mappings = null)
    {
        $params['index'] = $this->getLastIndex();
        $params['type'] = 'jobs';
        $params['body'] = $mappings;

        $this->client->indices()->putMapping($params);
        return $params['index'];
    }

    public function createIndex($settings = null, $mappings = null)
    {
        // Assemble new index name

        $nextIndexNumber = 1;
        $lastIndexName = $this->getLastIndex();

        if (null != $lastIndexName) {
            $lastIndexNameArr = explode('_', $lastIndexName);
            $nextIndexNumber = array_pop($lastIndexNameArr) + 1;
        }

        $nextIndexName = $this->getIndex() . '_' . date('Y') . '_' . $nextIndexNumber;

        // Create new index
        $params['index'] = $nextIndexName;
        if (null != $settings) {
            $params['body']['settings'] = $settings;
        }
        if (null != $mappings) {
            $params['body']['mappings'] = $mappings;
        }

        $this->client->indices()->create($params);

        // Update aliases
        $params = [];
        $params['body'] = [
            'actions' => [
                [
                    'add' => [
                        'index' => $nextIndexName,
                        'alias' => $this->getIndexCurrent()
                    ]
                ],
                [
                    'add' => [
                        'index' => $nextIndexName,
                        'alias' => $this->getIndex()
                    ]
                ]
            ]
        ];

        if (null != $lastIndexName) {
            array_unshift($params['body']['actions'], [
                'remove' => [
                    'index' => $lastIndexName,
                    'alias' => $this->getIndexCurrent()
                ]
            ]);
        }

        $this->client->indices()->updateAliases($params);

        return $nextIndexName;
    }

    public function createJob($command, $params)
    {
        if (!$this->storageApiClient) {
            throw new \Exception('Storage API client must be set');
        }

        $tokenData = $this->storageApiClient->verifyToken();
        return new Job([
            'id' => $this->storageApiClient->generateId(),
            'runId' => $this->storageApiClient->generateRunId(),
            'project' => [
                'id' => $tokenData['owner']['id'],
                'name' => $tokenData['owner']['name']
            ],
            'token' => [
                'id' => $tokenData['id'],
                'description' => $tokenData['description'],
                'token' => $this->encryptor->encrypt($this->storageApiClient->getTokenString())
            ],
            'component' => $this->componentName,
            'command' => $command,
            'params' => $params,
            'process' => [
                'host' => gethostname(),
                'pid' => getmypid()
            ],
            'createdTime' => date('c')
        ]);
    }

    /**
     * @param JobInterface $job
     * @return string jobId
     */
    public function indexJob(JobInterface $job)
    {
        $job->validate();

        $jobData = [
            'index' => $this->getIndexCurrent(),
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
            'index' => $this->getIndexCurrent()
        ]);

        return $response['_id'];
    }

    /**
     * @param JobInterface $job
     * @return string jobId
     */
    public function updateJob(JobInterface $job)
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

    public function getJob($jobId, $component = null)
    {
        $params = [];
        $params['index'] = $this->config['index_prefix'] . '_syrup*';

        if (!is_null($component)) {
            $params['index'] = $this->config['index_prefix'] . '_syrup_' . $component;
        }

        $params['body'] = [
            'size'  => 1,
            'query' => [
                'match_all' => []
            ],
            'filter' => [
                'ids' => [
                    'values' => [$jobId]
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

    public function getJobs(
        $projectId = null,
        $component = null,
        $runId = null,
        $queryString = null,
        $since = null,
        $until = null,
        $offset = 0,
        $limit = self::PAGING,
        $status = null
    ) {
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

        $query = ['match_all' => []];
        if ($queryString != null) {
            $query = [
                'query_string' => [
                    'allow_leading_wildcard' => 'false',
                    'default_operator' => 'AND',
                    'query' => $queryString
                ]
            ];
        }

        $rangeFilter = [];
        if ($since != null) {
            if ($until == null) {
                $until = 'now';
            }

            $rangeFilter = [
                'range' => ['createdTime'  => [
                    'gte' => date('c', strtotime($since)),
                    'lte' => date('c', strtotime($until)),
                ]]
            ];
        }

        $params = [];
        $params['index'] = $this->config['index_prefix'] . '_syrup_*';

        if (!is_null($component)) {
            $params['index'] = $this->config['index_prefix'] . '_syrup_' . $component;
        }

        if (!empty($rangeFilter)) {
            $filter[] = $rangeFilter;
        }

        $params['body'] = [
            'from' => $offset,
            'size' => $limit,
            'query' => [
                'filtered' => [
                    'filter' => [
                        'bool' => [
                            'must' => $filter
                        ]
                    ],
                    'query' => $query
                ]
            ],
            'sort' => [
                'id' => [
                    'order' => 'desc'
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

    public function getIndex()
    {
        return $this->config['index_prefix'] . '_syrup_' . $this->componentName;
    }

    public function getIndexCurrent()
    {
        return $this->getIndex() . '_current' ;
    }

    public function getIndexPrefix()
    {
        return $this->config['index_prefix'];
    }

    protected function getLastIndex()
    {
        try {
            $indices = $this->client->indices()->getAlias([
                'name'  => $this->getIndex()
            ]);

            return IndexNameResolver::getLastIndexName(array_keys($indices));

        } catch (Missing404Exception $e) {
            return null;

        }
    }
}
