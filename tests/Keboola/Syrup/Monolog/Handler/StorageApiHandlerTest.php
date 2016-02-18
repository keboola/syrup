<?php
/**
 * @package syrup
 * @copyright 2015 Keboola
 */

namespace Keboola\Syrup\Tests\Monolog\Processor;

use Keboola\StorageApi\Client;
use Keboola\StorageApi\Event;
use Keboola\Syrup\Monolog\Handler\StorageApiHandler;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;
use Keboola\Syrup\Service\StorageApi\StorageApiService;
use Keboola\Syrup\Test\Monolog\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;

class StorageApiHandlerTest extends TestCase
{
    public function testHandler()
    {
        $storageApiService = new StorageApiService(new RequestStack());
        $handler = new StorageApiHandler(SYRUP_APP_NAME, $storageApiService);
        $record = $this->getRecord(Logger::INFO, 'infoMessage');
        $handler->handle($record);

        $request = new Request();
        $request->headers->add(['x-storageapi-token' => SYRUP_SAPI_TEST_TOKEN]);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $storageApiService = new StorageApiService($requestStack);
        $client = $storageApiService->getClient();
        $client->setRunId(uniqid());
        $events = $client->listEvents(['q' => 'message: infoMessage + runId:' . $client->getRunId()]);
        // nothing is logged, because SAPI client was not initialized
        $this->assertEquals(0, count($events));

        $handler = new StorageApiHandler(SYRUP_APP_NAME, $storageApiService);
        $record = $this->getRecord(Logger::INFO, 'infoMessage', ['exceptionId' => '123', 'job' => '345']);
        $record['component'] = 'fooBar';
        $record['http'] = 'fooBaz';
        $handler->handle($record);
        // wait for elastic search to update
        sleep(2);
        $events = $client->listEvents(['q' => 'message: infoMessage + runId:' . $client->getRunId()]);
        $this->assertEquals(1, count($events));
        $event = $events[0];

        $this->assertArrayHasKey('component', $event);
        $this->assertEquals('fooBar', $event['component']);
        $this->assertArrayHasKey('id', $event);
        $this->assertArrayHasKey('event', $event);
        $this->assertArrayHasKey('message', $event);
        $this->assertArrayHasKey('description', $event);
        $this->assertArrayHasKey('type', $event);
        $this->assertArrayHasKey('runId', $event);
        $this->assertEquals($client->getRunId(), $event['runId']);
        $this->assertArrayHasKey('results', $event);
        $this->assertArrayHasKey('exceptionId', $event['results']);
        $this->assertArrayHasKey('job', $event['results']);
        $this->assertArrayHasKey('params', $event);
        $this->assertArrayHasKey('http', $event['params']);

        $record = $this->getRecord(Logger::INFO, 'infoMessage2', []);
        $handler->handle($record);
        // wait for elastic search to update
        sleep(2);
        $events = $client->listEvents(['q' => 'message: infoMessage2 + runId:' . $client->getRunId()]);
        $this->assertEquals(1, count($events));
        $event = $events[0];
        $this->assertArrayHasKey('component', $event);
        $this->assertEquals(SYRUP_APP_NAME, $event['component']);
        $this->assertArrayHasKey('results', $event);
        $this->assertArrayNotHasKey('exceptionId', $event['results']);
        $this->assertArrayNotHasKey('job', $event['results']);
    }

    public function testHandlerMessageInfo()
    {
        /** @var Client $client */
        /** @var StorageApiHandler $handler */
        list($client, $handler) = $this->initHandlerAndClient();

        $record = $this->getRecord(Logger::INFO, 'infoMessage3');
        $record['description'] = 'some description';
        $handler->handle($record);
        // wait for elastic search to update
        sleep(2);
        $events = $client->listEvents(['q' => 'message: infoMessage3 + runId:' . $client->getRunId()]);
        $this->assertEquals(1, count($events));
        $event = $events[0];
        $this->assertEquals(Event::TYPE_INFO, $event['type']);
        $this->assertEquals('infoMessage3', $event['message']);
        $this->assertEquals('', $event['description']);
    }

    public function testHandlerMessageNotice()
    {
        /** @var Client $client */
        /** @var StorageApiHandler $handler */
        list($client, $handler) = $this->initHandlerAndClient();

        $record = $this->getRecord(Logger::NOTICE, 'noticeMessage');
        $record['description'] = 'some description';
        $handler->handle($record);
        // wait for elastic search to update
        sleep(2);
        $events = $client->listEvents(['q' => 'message: noticeMessage + runId:' . $client->getRunId()]);
        $this->assertEquals(1, count($events));
        $event = $events[0];
        $this->assertEquals(Event::TYPE_WARN, $event['type']);
        $this->assertEquals('noticeMessage', $event['message']);
        $this->assertEquals('', $event['description']);
    }

    public function testHandlerMessageWarning()
    {
        /** @var Client $client */
        /** @var StorageApiHandler $handler */
        list($client, $handler) = $this->initHandlerAndClient();

        $record = $this->getRecord(Logger::WARNING, 'warningMessage', []);
        $record['description'] = 'some description';
        $handler->handle($record);
        // wait for elastic search to update
        sleep(2);
        $events = $client->listEvents(['q' => 'message: warningMessage + runId:' . $client->getRunId()]);
        $this->assertEquals(1, count($events));
        $event = $events[0];
        $this->assertEquals(Event::TYPE_WARN, $event['type']);
        $this->assertEquals('warningMessage', $event['message']);
        $this->assertEquals('', $event['description']);
    }

    public function testHandlerMessageCritical()
    {
        /** @var Client $client */
        /** @var StorageApiHandler $handler */
        list($client, $handler) = $this->initHandlerAndClient();

        $record = $this->getRecord(Logger::CRITICAL, 'criticalMessage', []);
        $record['description'] = 'some description';
        $handler->handle($record);
        // wait for elastic search to update
        sleep(2);
        $events = $client->listEvents(['q' => 'runId:' . $client->getRunId()]);
        $this->assertEquals(1, count($events));
        $event = $events[0];
        $this->assertEquals(Event::TYPE_ERROR, $event['type']);
        $this->assertEquals('Application error', $event['message']);
        $this->assertNotEquals('', $event['description']);
    }

    public function testHandlerMessageEmergency()
    {
        /** @var Client $client */
        /** @var StorageApiHandler $handler */
        list($client, $handler) = $this->initHandlerAndClient();

        $record = $this->getRecord(Logger::EMERGENCY, 'emergencyMessage', []);
        $record['description'] = 'some description';
        $handler->handle($record);
        // wait for elastic search to update
        sleep(2);
        $events = $client->listEvents(['q' => 'runId:' . $client->getRunId()]);
        $this->assertEquals(1, count($events));
        $event = $events[0];
        $this->assertEquals(Event::TYPE_ERROR, $event['type']);
        $this->assertEquals('Application error', $event['message']);
        $this->assertNotEquals('', $event['description']);
    }

    public function testHandlerMessageAlert()
    {
        /** @var Client $client */
        /** @var StorageApiHandler $handler */
        list($client, $handler) = $this->initHandlerAndClient();

        $record = $this->getRecord(Logger::ALERT, 'alertMessage', []);
        $record['description'] = 'some description';
        $handler->handle($record);
        // wait for elastic search to update
        sleep(2);
        $events = $client->listEvents(['q' => 'runId:' . $client->getRunId()]);
        $this->assertEquals(1, count($events));
        $event = $events[0];
        $this->assertEquals(Event::TYPE_ERROR, $event['type']);
        $this->assertEquals('Application error', $event['message']);
        $this->assertNotEquals('', $event['description']);
    }

    public function testHandlerMessageError()
    {
        /** @var Client $client */
        /** @var StorageApiHandler $handler */
        list($client, $handler) = $this->initHandlerAndClient();
        $record = $this->getRecord(Logger::ERROR, 'errorMessage', []);
        $record['description'] = 'some description';
        $handler->handle($record);
        // wait for elastic search to update
        sleep(2);
        $events = $client->listEvents(['q' => 'message: errorMessage + runId:' . $client->getRunId()]);
        $this->assertEquals(1, count($events));
        $event = $events[0];
        $this->assertEquals(Event::TYPE_ERROR, $event['type']);
        $this->assertEquals('errorMessage', $event['message']);
        $this->assertEquals('', $event['description']);
    }

    public function testInitInvalidToken()
    {
        $request = new Request();
        $request->headers->add(['x-storageapi-token' => 'invalid']);
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $storageApiService = new StorageApiService($requestStack);
        $handler = new StorageApiHandler(SYRUP_APP_NAME, $storageApiService);
        $this->assertFalse($handler->handle($this->getRecord(Logger::ERROR, 'errorMessage', [])));
    }

    public function testSanitizeExceptionMessage()
    {
        $storageClientStub = $this->getMockBuilder("\\Keboola\\StorageApi\\Client")
            ->disableOriginalConstructor()
            ->getMock();
        $storageClientStub->expects($this->once())
            ->method("getRunId")
            ->will($this->returnValue("123456"));
        $storageClientStub->expects($this->once())
            ->method("createEvent")
            ->with($this->callback(function ($event) {
                if ($event->getMessage() == 'SQLSTATE[XX000]: ? abcd') {
                    return true;
                }
                return false;
            }));

        $storageServiceStub = $this->getMockBuilder("\\Keboola\\Syrup\\Service\\StorageApi\\StorageApiService")
            ->disableOriginalConstructor()
            ->getMock();
        $storageServiceStub->expects($this->atLeastOnce())
            ->method("getClient")
            ->will($this->returnValue($storageClientStub));

        $handler = new StorageApiHandler("testsuite", $storageServiceStub);
        $record = [
            "message" => "SQLSTATE[XX000]: " . chr(0x00000080) . " abcd",
            "level" => "info"
        ];
        $handler->handle($record);
    }

    private function initHandlerAndClient()
    {
        $request = new Request();
        $request->headers->add(['x-storageapi-token' => SYRUP_SAPI_TEST_TOKEN]);
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $storageApiService = new StorageApiService($requestStack);
        $client = $storageApiService->getClient();
        $client->setRunId(uniqid());
        $handler = new StorageApiHandler(SYRUP_APP_NAME, $storageApiService);
        return [$client, $handler];
    }
}
