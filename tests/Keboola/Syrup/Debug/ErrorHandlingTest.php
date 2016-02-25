<?php
/**
 * Created by PhpStorm.
 * User: miroslavcillik
 * Date: 24/02/16
 * Time: 14:30
 */

namespace Keboola\Syrup\Tests\Debug;

use Keboola\Syrup\Test\WebTestCase;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\Process\Process;

class ErrorHandlingTest extends WebTestCase
{
    /** @var Client */
    private $client;

    protected $testPerformed = false;

    public function testControllerNoticeToException()
    {
        $this->client = $this->createClient();

        $errorOccured = false;

        $syslogProcessorMock = $this->getMockBuilder('Keboola\\Syrup\\Monolog\\Processor\\SyslogProcessor')
            ->disableOriginalConstructor()
            ->getMock();
        $syslogProcessorMock->expects($this->any())
            ->method("processRecord")
            ->with($this->callback(function ($subject) use (&$errorOccured) {
                if ($subject['message'] == 'Notice: Undefined offset: 3') {
                    $e = $subject['context']['exception'];
                    $errorOccured = true;
                    return ($e instanceof \Symfony\Component\Debug\Exception\ContextErrorException);
                }
                return true;
            }))
            ->willReturn([
                'level' => 100
            ]);

        $container = $this->client->getContainer();
        $container->set('syrup.monolog.syslog_processor', $syslogProcessorMock);

        $this->client->request('GET', '/tests/notice');
        $response = $this->client->getResponse();
        $responseJson = json_decode($response->getContent(), true);

        $this->assertEquals('error', $responseJson['status']);
        $this->assertEquals('Application error', $responseJson['error']);
        $this->assertEquals(500, $responseJson['code']);
        $this->assertArrayHasKey('exceptionId', $responseJson);
        $this->assertArrayHasKey('runId', $responseJson);
        $this->assertTrue($errorOccured);
    }
}
