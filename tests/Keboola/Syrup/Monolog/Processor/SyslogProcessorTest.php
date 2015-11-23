<?php
/**
 * @package syrup-component-bundle
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace Keboola\Syrup\Tests\Monolog\Processor;

use Keboola\Syrup\Aws\S3\Uploader;
use Symfony\Component\HttpFoundation\Request;
use Keboola\Syrup\Monolog\Processor\SyslogProcessor;
use Keboola\Syrup\Service\StorageApi\StorageApiService;
use Keboola\Syrup\Test\Monolog\TestCase;

class SyslogProcessorTest extends TestCase
{

    /**
     * @covers \Keboola\Syrup\Monolog\Processor\SyslogProcessor::__invoke
     * @covers \Keboola\Syrup\Monolog\Processor\SyslogProcessor::processRecord
     */
    public function testProcessor()
    {
        $s3Uploader = new Uploader([
            'aws-access-key' => SYRUP_AWS_KEY,
            'aws-secret-key' => SYRUP_AWS_SECRET,
            'aws-region' => SYRUP_AWS_REGION,
            's3-upload-path' => SYRUP_S3_BUCKET
        ]);

        $request = new Request();
        $request->headers->add(['x-storageapi-token' => SYRUP_SAPI_TEST_TOKEN]);
        $storageApiService = new StorageApiService();
        $storageApiService->setRequest($request);

        $processor = new SyslogProcessor(SYRUP_APP_NAME, $storageApiService, $s3Uploader);
        $record = $processor($this->getRecord());
        $this->assertArrayHasKey('component', $record);
        $this->assertEquals(SYRUP_APP_NAME, $record['component']);
        $this->assertArrayHasKey('pid', $record);
        $this->assertArrayHasKey('priority', $record);
        $this->assertArrayHasKey('runId', $record);
        $this->assertArrayHasKey('token', $record);
        $this->assertArrayHasKey('id', $record['token']);
        $this->assertArrayHasKey('description', $record['token']);
        $this->assertArrayHasKey('token', $record['token']);
        $this->assertArrayHasKey('owner', $record['token']);
        $this->assertArrayHasKey('id', $record['token']['owner']);
        $this->assertArrayHasKey('name', $record['token']['owner']);
    }

    /**
     * Test that explicitly provided component name is honored.
     * @covers \Keboola\Syrup\Monolog\Processor\SyslogProcessor::processRecord
     */
    public function testProcessorExplicitComponent()
    {
        $s3Uploader = new Uploader([
            'aws-access-key' => SYRUP_AWS_KEY,
            'aws-secret-key' => SYRUP_AWS_SECRET,
            'aws-region' => SYRUP_AWS_REGION,
            's3-upload-path' => SYRUP_S3_BUCKET
        ]);

        $request = new Request();
        $request->headers->add(['x-storageapi-token' => SYRUP_SAPI_TEST_TOKEN]);
        $storageApiService = new StorageApiService();
        $storageApiService->setRequest($request);

        $record = $this->getRecord();
        $record['component'] = 'fooBar';
        $processor = new SyslogProcessor(SYRUP_APP_NAME, $storageApiService, $s3Uploader);
        $newRecord = $processor($record);
        $this->assertArrayHasKey('component', $newRecord);
        $this->assertEquals('fooBar', $newRecord['component']);
    }

    public function testProcessorInitInvalidToken()
    {
        $s3Uploader = new Uploader([
            'aws-access-key' => SYRUP_AWS_KEY,
            'aws-secret-key' => SYRUP_AWS_SECRET,
            'aws-region' => SYRUP_AWS_REGION,
            's3-upload-path' => SYRUP_S3_BUCKET
        ]);

        $request = new Request();
        $request->headers->add(['x-storageapi-token' => 'invalid']);
        $storageApiService = new StorageApiService();
        $storageApiService->setRequest($request);

        $record = $this->getRecord();
        $record['component'] = 'fooBar';
        new SyslogProcessor(SYRUP_APP_NAME, $storageApiService, $s3Uploader);
    }
}
