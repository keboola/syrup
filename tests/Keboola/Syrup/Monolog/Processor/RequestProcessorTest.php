<?php
/**
 * @package syrup-component-bundle
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace Keboola\Syrup\Tests\Monolog\Processor;

use Keboola\Syrup\Aws\S3\Uploader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Keboola\Syrup\Monolog\Processor\RequestProcessor;
use Keboola\Syrup\Test\Monolog\TestCase;

class RequestProcessorTest extends TestCase
{

    /**
     * @covers \Keboola\Syrup\Monolog\Processor\RequestProcessor::__invoke
     * @covers \Keboola\Syrup\Monolog\Processor\RequestProcessor::processRecord
     */
    public function testProcessor()
    {
        $s3Uploader = new Uploader([
            'aws-access-key' => SYRUP_AWS_KEY,
            'aws-secret-key' => SYRUP_AWS_SECRET,
            'aws-region' => SYRUP_AWS_REGION,
            's3-upload-path' => SYRUP_S3_BUCKET
        ]);

        $params = [
            'parameter1' => 'val1',
            'parameter2' => 'val2'
        ];

        $requestStack = new RequestStack();
        $request = new Request($params);
        $requestStack->push($request);

        $processor = new RequestProcessor($requestStack, $s3Uploader);
        $record = $processor($this->getRecord());
        $this->assertArrayHasKey('http', $record);
        $this->assertArrayHasKey('url', $record['http']);
        $this->assertStringStartsWith('[GET] [', $record['http']['url']);
        $this->assertArrayHasKey('get', $record['http']);
        $this->assertEquals($params, $record['http']['get']);
        $this->assertArrayHasKey('cliCommand', $record);

        $requestStack = new RequestStack();
        $request = new Request([], [], [], [], [], [], json_encode($params));
        $requestStack->push($request);

        $processor = new RequestProcessor($requestStack, $s3Uploader);
        $record = $processor($this->getRecord());
        $this->assertArrayHasKey('http', $record);
        $this->assertArrayHasKey('json', $record['http']);
        $this->assertEquals($params, $record['http']['json']);
    }
}
