<?php
/**
 * Created by PhpStorm.
 * User: miroslavcillik
 * Date: 24/02/16
 * Time: 14:30
 */

namespace Keboola\Syrup\Tests\Debug;

use AppKernel;
use Composer\EventDispatcher\EventDispatcher;
use Keboola\Syrup\Debug\Debug;
use Keboola\Syrup\Test\Command\ErrorCommand;
use Keboola\Syrup\Test\CommandTestCase;
use Symfony\Bundle\FrameworkBundle\Command\EventDispatcherDebugCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Process\Process;

class CommandErrorHandlingTest extends CommandTestCase
{
    protected $testPerformed = false;

    public function setUp()
    {
        parent::setUp();
        Debug::enable(null, true, 'prod');
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
