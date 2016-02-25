<?php
/**
 * Created by PhpStorm.
 * User: miroslavcillik
 * Date: 22/02/16
 * Time: 10:52
 */

namespace Keboola\Syrup\Tests\Debug;

use Keboola\Syrup\Debug\Debug;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ErrorHandlingTest extends WebTestCase
{
    /** @var Client */
    private $client;

    protected $testPerformed = false;

    public function setUp()
    {
        $this->client = static::createClient();
        Debug::enable(null, true, 'prod');
    }

    public function testNoticeToException()
    {
        $errorOccured = false;

        $syslogProcessorMock = $this->getMockBuilder('Keboola\\Syrup\\Monolog\\Processor\\SyslogProcessor')
            ->disableOriginalConstructor()
            ->getMock();
        $syslogProcessorMock->expects($this->any())
            ->method("processRecord")
            ->with($this->callback(function($subject) use (&$errorOccured) {
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
