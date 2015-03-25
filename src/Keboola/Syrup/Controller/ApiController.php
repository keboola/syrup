<?php

namespace Keboola\Syrup\Controller;

use Keboola\Encryption\EncryptorInterface;
use Keboola\Syrup\Elasticsearch\ComponentIndex;
use Keboola\Syrup\Elasticsearch\JobMapper;
use Keboola\Syrup\Job\Metadata\JobFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\Event as SapiEvent;
use Keboola\Syrup\Exception\ApplicationException;
use Keboola\Syrup\Exception\UserException;
use Keboola\Syrup\Job\Metadata\JobInterface;
use Keboola\Syrup\Service\Queue\QueueService;

class ApiController extends BaseController
{
    /** @var Client */
    protected $storageApi;

    protected function initStorageApi()
    {
        $this->storageApi = $this->container->get('syrup.storage_api')->getClient();
    }

    public function preExecute(Request $request)
    {
        parent::preExecute($request);

        $this->initStorageApi();
    }

    /**
     * Run Action
     *
     * Creates new job, saves it to Elasticsearch and add to SQS
     *
     * @param Request $request
     * @return Response
     */
    public function runAction(Request $request)
    {
        // Get params from request
        $params = $this->getPostJson($request);

        // check params against ES mapping
        $this->checkMappingParams($params);

        // Create new job
        $job = $this->createJob('run', $params);

        // Add job to Elasticsearch
        try {
            /** @var JobMapper $jobMapper */
            $jobMapper = $this->container->get('syrup.elasticsearch.current_component_job_mapper');
            $jobId = $jobMapper->create($job);
        } catch (\Exception $e) {
            throw new ApplicationException("Failed to create job", $e);
        }

        // Add job to SQS
        $queueName = 'default';
        $queueParams = $this->container->getParameter('queue');

        if (isset($queueParams['sqs'])) {
            $queueName = $queueParams['sqs'];
        }
        $messageId = $this->enqueue($jobId, $queueName);

        $this->logger->info('Job created', [
            'sqsQueue' => $queueName,
            'sqsMessageId' => $messageId,
            'job' => $job->getLogData()
        ]);

        // Response with link to job resource
        return $this->createJsonResponse([
            'id'        => $jobId,
            'url'       => $this->getJobUrl($jobId),
            'status'    => $job->getStatus()
        ], 202);
    }

    public function optionsAction()
    {
        $response = new Response();
        $response->headers->set('Accept', 'application/json');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'content-type, x-requested-with, x-requested-by, '
            . 'x-storageapi-url, x-storageapi-token, x-kbc-runid, x-user-agent');
        $response->headers->set('Access-Control-Max-Age', '86400');
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /** Jobs */

    /**
     * @param $jobId
     * @return string
     */
    protected function getJobUrl($jobId)
    {
        $queueParams = $this->container->getParameter('queue');
        return $queueParams['url'] . '/job/' . $jobId;
    }

    protected function checkMappingParams($params)
    {
        $mapping = ComponentIndex::buildMapping($this->container->get('kernel')->getRootDir());
        if (isset($mapping['mappings']['jobs']['properties']['params']['properties'])) {
            $mappingParams = $mapping['mappings']['jobs']['properties']['params']['properties'];

            foreach (array_keys($params) as $paramKey) {
                if (!in_array($paramKey, array_keys($mappingParams))) {
                    throw new UserException(sprintf(
                        "Parameter '%s' is not allowed. Allowed params are '%s'",
                        $paramKey,
                        implode(',', array_keys($mappingParams))
                    ));
                }
            }
        }
    }

    /**
     * @param string $command
     * @param array $params
     * @return JobInterface
     */
    protected function createJob($command, $params)
    {
        $jobFactory = $this->container->get('syrup.job_factory');
        $jobFactory->setStorageApiClient($this->storageApi);
        return $jobFactory->create($command, $params);
    }

    /**
     * Add JobId to queue
     * @param        $jobId
     * @param string $queueName
     * @param array  $otherData
     * @return int $messageId
     */
    protected function enqueue($jobId, $queueName = 'default', $otherData = [])
    {
        /** @var QueueService $queue */
        $queue = $this->container->get('syrup.queue_factory')->get($queueName);
        return $queue->enqueue($jobId, $otherData);
    }

    /** Stuff */

    /**
     * @return EncryptorInterface
     */
    protected function getEncryptor()
    {
        return $this->container->get('syrup.encryptor');
    }

    protected function sendEventToSapi($type, $message, $componentName)
    {
        $sapiEvent = new SapiEvent();
        $sapiEvent->setComponent($componentName);
        $sapiEvent->setMessage($message);
        $sapiEvent->setRunId($this->storageApi->getRunId());
        $sapiEvent->setType($type);

        $this->storageApi->createEvent($sapiEvent);
    }

    /**
     * @deprecated
     */
    public function camelize($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        $chunks = explode('-', $value);
        $ucfirsted = array_map(function ($s) {
            return ucfirst($s);
        }, $chunks);

        return lcfirst(implode('', $ucfirsted));
    }
}
