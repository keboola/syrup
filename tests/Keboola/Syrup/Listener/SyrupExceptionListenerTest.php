<?php
/**
 * @package syrup-component-bundle
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace Keboola\Syrup\Tests\Listener;

use Keboola\DebugLogUploader\UploaderS3;
use Keboola\StorageApi\ClientException;
use Keboola\StorageApi\MaintenanceException;
use Monolog\Handler\TestHandler;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Keboola\Syrup\Command\JobCommand;
use Keboola\Syrup\Exception\UserException;
use Keboola\Syrup\Listener\SyrupExceptionListener;
use Keboola\Syrup\Monolog\Formatter\JsonFormatter;
use Keboola\Syrup\Monolog\Processor\StdoutProcessor;
use Keboola\Syrup\Service\StorageApi\StorageApiService;

class SyrupExceptionListenerTest extends KernelTestCase
{
    /**
     * @var TestHandler
     */
    protected $testLogHandler;
    /**
     * @var SyrupExceptionListener
     */
    protected $listener;

    public function setUp()
    {
        static::bootKernel();

        $request = new Request();
        $request->headers->add(['X-StorageApi-Token' => SAPI_TOKEN]);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $storageApiService = new StorageApiService($requestStack, SAPI_URL);

        $uploader = new UploaderS3([
            'aws-access-key' => AWS_ACCESS_KEY_ID,
            'aws-secret-key' => AWS_SECRET_ACCESS_KEY,
            's3-upload-path' => AWS_S3_BUCKET . AWS_S3_BUCKET_LOGS_PATH,
            'aws-region' => AWS_REGION,
            'url-prefix' => 'https://connection.keboola.com/admin/utils/logs?file=',
        ]);
        $this->testLogHandler = new TestHandler();
        $this->testLogHandler->setFormatter(new JsonFormatter());
        $this->testLogHandler->pushProcessor(new StdoutProcessor(SYRUP_APP_NAME, $storageApiService, $uploader));
        $logger = new \Monolog\Logger('test', [$this->testLogHandler]);
        $this->listener = new SyrupExceptionListener(SYRUP_APP_NAME, $storageApiService, $logger);
    }

    public function testMaintenance()
    {
        $request = new Request();
        $request->headers->add(['X-StorageApi-Token' => SAPI_TOKEN]);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $storageApiService = new StorageApiService($requestStack, 'https://maintenance-testing.us-east-1.keboola.com');

        $uploader = new UploaderS3([
            'aws-access-key' => AWS_ACCESS_KEY_ID,
            'aws-secret-key' => AWS_SECRET_ACCESS_KEY,
            's3-upload-path' => AWS_S3_BUCKET . AWS_S3_BUCKET_LOGS_PATH,
            'aws-region' => AWS_REGION,
            'url-prefix' => 'https://connection.keboola.com/admin/utils/logs?file=',
        ]);
        $testLogHandler = new TestHandler();
        $testLogHandler->setFormatter(new JsonFormatter());
        $testLogHandler->pushProcessor(new StdoutProcessor(SYRUP_APP_NAME, $storageApiService, $uploader));
        $logger = new \Monolog\Logger('test', [$testLogHandler]);

        $listener = new SyrupExceptionListener(SYRUP_APP_NAME, $storageApiService, $logger);
        $this->assertTrue($listener instanceof SyrupExceptionListener);

        try {
            $storageApiService->getClient();
            $this->fail('Create of sapi client should produce MaintenanceException');
        } catch (MaintenanceException $e) {
        }
    }

    public function testConsoleException()
    {
        $command = new JobCommand();
        $input = new ArrayInput([]);
        $output = new NullOutput();

        $message = uniqid();
        $level = 500;
        $event = new ConsoleExceptionEvent($command, $input, $output, new \Exception($message), $level);
        $this->listener->onConsoleException($event);
        $records = $this->testLogHandler->getRecords();
        $this->assertCount(1, $records);
        $record = current($records);
        $this->assertArrayHasKey('message', $record);
        $this->assertEquals($message, $record['message']);
        $this->assertArrayHasKey('level', $record);
        $this->assertEquals($level, $record['level']);
        $this->assertArrayHasKey('exceptionId', $record);
        $this->assertArrayHasKey('exception', $record);
        $this->assertArrayHasKey('message', $record['exception']);
        $this->assertArrayHasKey('code', $record['exception']);
        $this->assertArrayHasKey('attachment', $record['exception']);

        $message = uniqid();
        $level = 500;
        $event = new ConsoleExceptionEvent($command, $input, $output, new ClientException($message), $level);
        $this->listener->onConsoleException($event);
        $records = $this->testLogHandler->getRecords();
        $this->assertCount(2, $records);
        $record = array_pop($records);
        $this->assertArrayHasKey('exception', $record);
        $this->assertArrayHasKey('class', $record['exception']);
        $this->assertEquals('Keboola\StorageApi\ClientException', $record['exception']['class']);

        $message = uniqid();
        $level = 400;
        $event = new ConsoleExceptionEvent($command, $input, $output, new UserException($message), $level);
        $this->listener->onConsoleException($event);
        $records = $this->testLogHandler->getRecords();
        $this->assertCount(3, $records);
        $record = array_pop($records);
        $this->assertArrayHasKey('exception', $record);
        $this->assertArrayHasKey('class', $record['exception']);
        $this->assertEquals('Keboola\Syrup\Exception\UserException', $record['exception']['class']);
    }

    public function testKernelException()
    {
        $request = Request::create('/syrup/run', 'POST');
        $request->headers->set('X-StorageApi-Token', SAPI_TOKEN);

        $message = 'Disabled for tests';
        $event = new GetResponseForExceptionEvent(self::$kernel, $request, HttpKernelInterface::MASTER_REQUEST, new MaintenanceException($message, 60, []));
        $this->listener->onKernelException($event);
        $records = $this->testLogHandler->getRecords();
        $this->assertCount(1, $records);
        $record = array_pop($records);
        $this->assertArrayHasKey('priority', $record);
        $this->assertEquals('ERROR', $record['priority']);
        $this->assertArrayHasKey('exception', $record);
        $this->assertArrayHasKey('class', $record['exception']);
        $this->assertEquals('Keboola\StorageApi\MaintenanceException', $record['exception']['class']);
        $response = $event->getResponse();
        $this->assertEquals(503, $response->getStatusCode());
        $jsonResponse = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('status', $jsonResponse);
        $this->assertArrayHasKey('error', $jsonResponse);
        $this->assertContains('Project is disabled', $jsonResponse['error']);
        $this->assertArrayHasKey('message', $jsonResponse);
        $this->assertContains('Project is disabled', $jsonResponse['message']);
        $this->assertEquals('error', $jsonResponse['status']);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertEquals(503, $jsonResponse['code']);
        $this->assertArrayHasKey('exceptionId', $jsonResponse);
        $this->assertArrayHasKey('runId', $jsonResponse);

        $message = uniqid();
        $event = new GetResponseForExceptionEvent(self::$kernel, $request, HttpKernelInterface::MASTER_REQUEST, new UserException($message));
        $this->listener->onKernelException($event);
        $records = $this->testLogHandler->getRecords();
        $this->assertCount(2, $records);
        $record = array_pop($records);
        $this->assertArrayHasKey('priority', $record);
        $this->assertEquals('ERROR', $record['priority']);
        $this->assertArrayHasKey('exception', $record);
        $this->assertArrayHasKey('class', $record['exception']);
        $this->assertEquals('Keboola\Syrup\Exception\UserException', $record['exception']['class']);
        $response = $event->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
        $jsonResponse = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('status', $jsonResponse);
        $this->assertEquals('error', $jsonResponse['status']);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertEquals(400, $jsonResponse['code']);
        $this->assertArrayHasKey('exceptionId', $jsonResponse);
        $this->assertArrayHasKey('runId', $jsonResponse);

        $message = uniqid();
        $event = new GetResponseForExceptionEvent(self::$kernel, $request, HttpKernelInterface::MASTER_REQUEST, new ClientException($message));
        $this->listener->onKernelException($event);
        $records = $this->testLogHandler->getRecords();
        $this->assertCount(3, $records);
        $record = array_pop($records);
        $this->assertArrayHasKey('priority', $record);
        $this->assertEquals('CRITICAL', $record['priority']);
        $this->assertArrayHasKey('exception', $record);
        $this->assertArrayHasKey('class', $record['exception']);
        $this->assertEquals('Keboola\StorageApi\ClientException', $record['exception']['class']);
        $response = $event->getResponse();
        $this->assertEquals(500, $response->getStatusCode());
        $jsonResponse = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('status', $jsonResponse);
        $this->assertEquals('error', $jsonResponse['status']);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertEquals(500, $jsonResponse['code']);
        $this->assertArrayHasKey('exceptionId', $jsonResponse);
        $this->assertArrayHasKey('runId', $jsonResponse);

        $exception = new UserException(uniqid());
        $exception->setData(['d1' => uniqid(), 'd2' => uniqid()]);
        $event = new GetResponseForExceptionEvent(self::$kernel, $request, HttpKernelInterface::MASTER_REQUEST, $exception);
        $this->listener->onKernelException($event);
        $records = $this->testLogHandler->getRecords();
        $this->assertCount(4, $records);
        $record = array_pop($records);
        $this->assertArrayHasKey('context', $record);
        $this->assertArrayHasKey('data', $record['context']);
        $this->assertArrayHasKey('d1', $record['context']['data']);
        $this->assertArrayHasKey('d2', $record['context']['data']);
    }
}
