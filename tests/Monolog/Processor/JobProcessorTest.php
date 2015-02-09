<?php
/**
 * @package syrup-component-bundle
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace Keboola\Syrup\Tests\Monolog\Processor;

use Keboola\Syrup\Job\Metadata\Job;
use Keboola\Syrup\Monolog\Processor\JobProcessor;
use Keboola\Syrup\Test\Monolog\TestCase;

class JobProcessorTest extends TestCase
{

    /**
     * @covers Syrup\ComponentBundle\Monolog\Processor\JobProcessor::__invoke
     * @covers Syrup\ComponentBundle\Monolog\Processor\JobProcessor::processRecord
     * @covers Syrup\ComponentBundle\Monolog\Processor\JobProcessor::setJob
     */
    public function testProcessor()
    {
        $processor = new JobProcessor();
        $processor->setJob(new Job([
            'id' => uniqid(),
            'runId' => uniqid(),
            'lockName' => uniqid()
        ]));
        $record = $processor($this->getRecord());
        $this->assertArrayHasKey('job', $record);
        $this->assertArrayHasKey('id', $record['job']);
    }
}
