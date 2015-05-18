<?php
/**
 * @package syrup
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace Keboola\Syrup\Elasticsearch;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\ServerErrorResponseException;
use Keboola\Syrup\Exception\ApplicationException;
use Keboola\Syrup\Job\Metadata\Job;
use Keboola\Syrup\Job\Metadata\JobInterface;
use Monolog\Logger;

class JobMapper
{
    /** @var Client */
    protected $client;

    /** @var ComponentIndex */
    protected $index;

    /** @var Logger */
    protected $logger;

    public function __construct(Client $client, ComponentIndex $index, $logger = null)
    {
        $this->client = $client;
        $this->index = $index;
        $this->logger = $logger;
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

        $response = null;
        $i = 0;
        while ($i < 5) {
            try {
                $response = $this->client->index($jobData);
                break;
            } catch (ServerErrorResponseException $e) {
                // ES server error, try again
                $this->log('error', 'Elastic server error response', [
                    'attemptNo' => $i,
                    'jobId' => $job->getId(),
                    'exception' => $e
                ]);
            }

            sleep(1 + intval(pow(2, $i)/2));
            $i++;
        }

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

        throw new ApplicationException("Unable to find job in index after creation", null, [
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

        $response = null;
        $i = 0;
        while ($i < 5) {
            try {
                $response = $this->client->update($jobData);
                break;
            } catch (ServerErrorResponseException $e) {
                // ES server error, try again
                $this->log('error', 'Elastic server error response', [
                    'attemptNo' => $i,
                    'jobId' => $job->getId(),
                    'exception' => $e
                ]);
            }

            sleep(1 + intval(pow(2, $i)/2));
            $i++;
        }

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

        $i = 0;
        while ($i < 5) {
            try {
                $result = $this->client->mget([
                    'body' => ['docs' => $docs]
                ]);

                foreach ($result['docs'] as $doc) {
                    if ($doc['found']) {
                        return new Job($doc['_source'], $doc['_index'], $doc['_type'], $doc['_version']);
                    }
                }
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

    protected function log($level, $message, $context = [])
    {
        // do nothing if logger is null
        if ($this->logger != null) {
            $this->logger->$level($message, $context);
        }
    }
}
