<?php
/**
 * Created by PhpStorm.
 * User: miroslavcillik
 * Date: 25/02/16
 * Time: 13:54
 */

namespace Keboola\Syrup\Tests\Command;

use Symfony\Component\Process\Process;

class ErrorCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testNoticeToExceptionCommand()
    {
        $process = $this->runErrorCommand('notice');
        $this->assertContains('Symfony\Component\Debug\Exception\ContextErrorException', $process->getErrorOutput());
    }

    public function testWarningToExceptionCommand()
    {
        $process = $this->runErrorCommand('warning');
        $this->assertContains('Symfony\Component\Debug\Exception\ContextErrorException', $process->getErrorOutput());
    }

    public function testFatalErrorToExceptionCommand()
    {
        $process = $this->runErrorCommand('fatal');
        $this->assertContains('Symfony\Component\Debug\Exception\ClassNotFoundException', $process->getErrorOutput());
        $this->assertContains('exceptionId', (string) $process->getOutput());
    }

    public function testFatalErrorMemoryToExceptionCommand()
    {
        $this->markTestSkipped("Unstable");
        $process = $this->runErrorCommand('memory');
        $this->assertContains('Allowed memory size of', $process->getErrorOutput());
        $this->assertContains('exceptionId', (string) $process->getOutput());
    }

    private function runErrorCommand($errorType)
    {
        $cmd = sprintf('php ' . __DIR__ . '/../../../../app/console syrup:test:error %s --env prod', $errorType);
        $process = new Process($cmd);
        $process->run();

        return $process;
    }
}
