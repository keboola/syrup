<?php

namespace Keboola\Syrup\Monolog\Processor;

use Keboola\Syrup\Debug\Exception\FlattenException;
use Keboola\Syrup\Debug\ExceptionHandler;
use Keboola\Syrup\Aws\S3\Uploader;
use Keboola\Syrup\Exception\SyrupComponentException;
use Keboola\Syrup\Service\StorageApi\StorageApiService;

/**
 * Injects info about component and used Storage Api token
 */
class SyslogProcessor
{

    private $componentName;
    private $tokenData;
    private $runId;

    /**
     * @var Uploader
     */
    private $s3Uploader;

    public function __construct($componentName, StorageApiService $storageApiService, Uploader $s3Uploader)
    {
        $this->componentName = $componentName;
        $this->s3Uploader = $s3Uploader;
        try {
            // does not work for some commands
            $storageApiClient = $storageApiService->getClient();
            $this->tokenData = $storageApiClient->getLogData();
            $this->runId = $storageApiClient->getRunId();
        } catch (SyrupComponentException $e) {
        }
    }

    public function setRunId($runId)
    {
        $this->runId = $runId;
    }

    public function setTokenData($tokenData)
    {
        $this->tokenData = $tokenData;
    }

    /**
     * @param  array $record
     * @return array
     */
    public function __invoke(array $record)
    {
        return $this->processRecord($record);
    }

    public function processRecord(array $record)
    {
        if (empty($record['component'])) {
            $record['component'] = $this->componentName;
        }
        $record['runId'] = $this->runId;
        $record['pid'] = getmypid();
        $record['priority'] = $record['level_name'];

        if ($this->tokenData) {
            $record['token'] = [
                'id' => $this->tokenData['id'],
                'description' => $this->tokenData['description'],
                'token' => $this->tokenData['token'],
                'owner' => [
                    'id' => $this->tokenData['owner']['id'],
                    'name' => $this->tokenData['owner']['name']
                ]
            ];
        }

        if (isset($record['context']['exceptionId'])) {
            $record['exceptionId'] = $record['context']['exceptionId'];
            unset($record['context']['exceptionId']);
        }
        if (isset($record['context']['exception'])) {
            /** @var \Exception $e */
            $e = $record['context']['exception'];
            unset($record['context']['exception']);
            if ($e instanceof \Exception) {
                $flattenException = FlattenException::create($e);
                $eHandler = new ExceptionHandler(true);
                $html = $eHandler->getHtml($flattenException);
                $record['exception'] = [
                    'class' => get_class($e),
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'attachment' => $this->s3Uploader->uploadString('exception', $html, 'text/html')
                ];
            }
        }

        if (isset($record['context']['data']) && !count($record['context']['data'])) {
            unset($record['context']['data']);
        }
        if (!count($record['extra'])) {
            unset($record['extra']);
        }
        if (!count($record['context'])) {
            unset($record['context']);
        }


        $json = json_encode($record);
        if (strlen($json) > 1024) {
            $r = [
                'message' => strlen($record['message']) > 256 ? substr($record['message'], 0, 256) . '...' : $record['message'],
                'component' => $this->componentName,
                'runId' => $this->runId,
                'pid' => getmypid(),
                'priority' => $record['level_name'],
                'level_name' => $record['level_name'],
                'level' => $record['level'],
                'attachment' => $this->s3Uploader->uploadString('log', $json, 'text/json')
            ];
            if (isset($record['app'])) {
                $r['app'] = $record['app'];
            }
            $record = $r;
        }

        return $record;
    }
}
