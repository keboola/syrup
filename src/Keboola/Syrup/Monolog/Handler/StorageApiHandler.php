<?php
/**
 * @package syrup-component-bundle
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace Keboola\Syrup\Monolog\Handler;

use Keboola\StorageApi\Event;
use Keboola\StorageApi\Exception as SapiException;
use Keboola\Syrup\Exception\SimpleException;
use Monolog\Logger;
use Keboola\Syrup\Exception\NoRequestException;
use Keboola\Syrup\Exception\UserException;
use Keboola\Syrup\Service\StorageApi\StorageApiService;

class StorageApiHandler extends \Monolog\Handler\AbstractHandler
{
    /** @var \Keboola\StorageApi\Client */
    protected $storageApiClient;

    /** @var  StorageApiService */
    protected $storageApiService;

    protected $appName;

    public function __construct($appName, StorageApiService $storageApiService)
    {
        $this->appName = $appName;
        $this->storageApiService = $storageApiService;
        parent::__construct();
    }

    protected function initStorageApiClient()
    {
        try {
            $this->storageApiClient = $this->storageApiService->getClient();
        } catch (NoRequestException $e) {
            // Ignore when no SAPI client setup
        } catch (UserException $e) {
            // Ignore when no SAPI client setup
        } catch (\Keboola\ObjectEncryptor\Exception\UserException $e) {
            // Ignore when no SAPI client setup
        } catch (SimpleException $e) {
            // Ignore when no SAPI client setup
        } catch (SapiException $e) {
            // Ignore when SAPI client setup is wrong
        }
    }

    public function handle(array $record)
    {
        $this->initStorageApiClient();

        if (!$this->storageApiClient || $record['level'] == Logger::DEBUG || $record['level'] == Logger::NOTICE) {
            return false;
        }

        $event = new Event();
        if (!empty($record['component'])) {
            $event->setComponent($record['component']);
        } else {
            $event->setComponent($this->appName);
        }
        $event->setMessage(\Keboola\Utils\sanitizeUtf8($record['message']));
        $event->setRunId($this->storageApiClient->getRunId());

        $params = [];
        if (isset($record['http'])) {
            $params['http'] = $record['http'];
        }
        $event->setParams($params);

        $results = [];
        if (isset($record['context']['exceptionId'])) {
            $results['exceptionId'] = $record['context']['exceptionId'];
        }
        if (isset($record['context']['job'])) {
            $results['job'] = $record['context']['job'];
        }
        $event->setResults($results);

        switch ($record['level']) {
            case Logger::ERROR:
                $type = Event::TYPE_ERROR;
                break;
            case Logger::CRITICAL:
            case Logger::EMERGENCY:
            case Logger::ALERT:
                $type = Event::TYPE_ERROR;
                $event->setMessage("Application error");
                $event->setDescription("Contact support@keboola.com");
                break;
            case Logger::WARNING:
            case Logger::NOTICE:
                $type = Event::TYPE_WARN;
                break;
            case Logger::INFO:
            default:
                $type = Event::TYPE_INFO;
                break;
        }
        $event->setType($type);

        $this->storageApiClient->createEvent($event);
        return false;
    }
}
