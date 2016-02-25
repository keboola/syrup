<?php
/**
 * Created by PhpStorm.
 * User: miroslavcillik
 * Date: 24/02/16
 * Time: 14:30
 */

namespace Keboola\Syrup\Tests\Debug;

use Keboola\Syrup\Test\Command\ErrorCommand;
use Keboola\Syrup\Test\CommandTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\Process\Process;

class ErrorHandlingTest extends CommandTestCase
{
    /** @var Client */
    private $client;

    protected $testPerformed = false;

    public function setUp()
    {

    }

    public function testControllerNoticeToException()
    {
        $this->client = $this->createClient();

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

    public function testNoticeToExceptionCommand()
    {
        $errorOccured = false;

        $exceptionListener = $this->getMockBuilder('Keboola\\Syrup\\Listener\\SyrupExceptionListener')
            ->disableOriginalConstructor()
            ->getMock();
        $exceptionListener->expects($this->any())
            ->method("onConsoleException")
            ->with($this->callback(function($event) use (&$errorOccured) {
                $errorOccured = true;
                $this->assertEquals("User Notice: This is NOTICE!", $event->getException()->getMessage());
                return true;
            }))
        ;

        $this->bootKernel(['debug' => true]);
        $this->application = new Application(self::$kernel);
        $container = $this->application->getKernel()->getContainer();
        $container->set('syrup.listener.exception', $exceptionListener);

        $command = $this->application->add(new ErrorCommand());

        $commandTester = new CommandTester($command);
        $exceptionOccured = false;
        try {
            $commandTester->execute([
                'command' => $command->getName(),
                'error' => 'notice'
            ]);
        } catch (\Exception $e) {
            // CommandTester doesn't work with events,
            // to test the OnConsoleException event
            // we have to dispatch the event manualy
            $dispatcher = $container->get('event_dispatcher');
            $event = new ConsoleExceptionEvent($command, $commandTester->getInput(), $commandTester->getOutput(), $e, $e->getCode());
            $dispatcher->dispatch(ConsoleEvents::EXCEPTION, $event);
            $exceptionOccured = true;
        }

        $this->assertTrue($errorOccured);
        $this->assertTrue($exceptionOccured);
    }
}
