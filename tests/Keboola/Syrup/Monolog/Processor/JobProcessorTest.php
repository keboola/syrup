<?php
/**
 * @package syrup-component-bundle
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace Keboola\Syrup\Tests\Monolog\Processor;

use Keboola\ObjectEncryptor\Legacy\Wrapper\BaseWrapper;
use Keboola\ObjectEncryptor\ObjectEncryptor;
use Keboola\Syrup\Job\Metadata\Job;
use Keboola\Syrup\Monolog\Processor\JobProcessor;
use Keboola\Syrup\Test\Monolog\TestCase;

class JobProcessorTest extends TestCase
{
    public function testProcessor()
    {
        $processor = new JobProcessor();
        $configEncryptor = new ObjectEncryptor();
        $wrapper = new BaseWrapper();
        $wrapper->setKey(uniqid('fooBar'));
        $configEncryptor->pushWrapper($wrapper);
        $processor->setJob(new Job($configEncryptor, [
                'id' => uniqid(),
                'runId' => uniqid(),
                'lockName' => uniqid()
            ], null, null, null));
        $record = $processor($this->getRecord());
        $this->assertArrayHasKey('job', $record);
        $this->assertArrayHasKey('id', $record['job']);
    }
}
