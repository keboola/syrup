<?php
/**
 * Created by PhpStorm.
 * User: miroslavcillik
 * Date: 25/02/16
 * Time: 13:54
 */

namespace Keboola\Syrup\Tests\Command;

use Symfony\Component\Console\Application;
use Keboola\Syrup\Test\Command\ErrorCommand;
use Symfony\Component\Console\Tester\CommandTester;


class ErrorCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testNoticeToExceptionCommand()
    {
        $application = new Application();
        $command = $application->add(new ErrorCommand());

        $commandTester = new CommandTester($command);
        $exceptionOccured = false;
        try {
            $commandTester->execute([
                'command' => $command->getName(),
                'error' => 'notice'
            ]);
        } catch (\Exception $e) {
            $exceptionOccured = true;
            $this->assertInstanceOf('\Symfony\Component\Debug\Exception\ContextErrorException', $e);
        }

        $this->assertTrue($exceptionOccured);
    }
}
