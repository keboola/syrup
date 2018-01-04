<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Miro
 * Date: 23.11.12
 * Time: 14:55
 */
namespace Keboola\Syrup\Listener;

use Keboola\StorageApi\Exception as SapiException;
use Keboola\StorageApi\MaintenanceException;
use Keboola\Syrup\Exception\SimpleException;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Monolog\Logger;
use Keboola\Syrup\Exception\NoRequestException;
use Keboola\Syrup\Exception\SyrupExceptionInterface;
use Keboola\Syrup\Exception\UserException;
use Keboola\Syrup\Service\StorageApi\StorageApiService;

class SyrupExceptionListener
{
    /**
     * @var \Monolog\Logger
     */
    protected $logger;

    protected $appName;
    protected $runId;

    public function __construct($appName, StorageApiService $storageApiService, Logger $logger)
    {
        $this->appName = $appName;
        try {
            $this->runId = $storageApiService->getClient()->getRunId();
        } catch (NoRequestException $e) {
        } catch (UserException $e) {
        } catch (SimpleException $e) {
        } catch (SapiException $e) {
        }
        $this->logger = $logger;
    }

    public function onConsoleException(ConsoleExceptionEvent $event)
    {
        $exception = $event->getException();
        $exceptionId = $this->appName . '-' . md5(microtime());

        $logData = array(
            'exception' => $exception,
            'exceptionId' => $exceptionId,
        );

        // exception is by default Application Exception
        $isUserException = false;

        // SyrupExceptionInterface holds additional data
        if ($exception instanceof SyrupExceptionInterface) {
            $logData['data'] = $exception->getData();
            $isUserException = ($exception->getStatusCode() < 500);
        }

        // Log exception
        $method = ($isUserException) ? 'error' : 'critical';
        $this->logger->$method($exception->getMessage(), $logData);
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        $exceptionId = $this->appName . '-' . md5(microtime());

        // Customize your response object to display the exception details
        $response = new Response();

        $code = ($exception->getCode() < 300 || $exception->getCode() >= 600) ? 500 : $exception->getCode();

        // exception is by default Application Exception
        $content = array(
            'status'  => 'error',
            'error'  => 'Application error',
            'code' => (int)$code,
            'message' => 'Contact support@keboola.com and attach this exception id.',
            'exceptionId' => $exceptionId,
            'runId' => (int)$this->runId
        );

        $method = 'critical';

        // HttpExceptionInterface is a special type of exception that
        // holds status code and header details
        if ($exception instanceof HttpExceptionInterface) {
            $code = $exception->getStatusCode();
            $response->headers->replace($exception->getHeaders());

            if ($code < 500) {
                $method = 'error';
                $content['error'] = 'User error';
                $content['message'] = $exception->getMessage();
            }
        }

        // MaintenanceException from SAPI client
        if ($exception instanceof MaintenanceException) {
            $method = 'error';
            $content['error'] = 'Project is disabled';
            $content['message'] = 'Project is disabled - ' . $exception->getMessage();
        }

        $logData = array(
            'exception' => $exception,
            'exceptionId' => $exceptionId,
        );

        // SyrupExceptionInterface holds additional data
        if ($exception instanceof SyrupExceptionInterface) {
            $data = $exception->getData();
            if ($data) {
                $logData['data'] = $data;
            }
        }

        $response->setContent(json_encode($content));
        $response->setStatusCode($code);
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', '*');
        $response->headers->set('Access-Control-Allow-Headers', '*');
        $response->headers->set('Cache-Control', 'private, no-cache, no-store, must-revalidate');

        // Send the modified response object to the event
        $event->setResponse($response);

        // Log exception
        $this->logger->$method($exception->getMessage(), $logData);
    }
}
