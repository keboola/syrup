<?php
/**
 * @package syrup-component-bundle
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace Keboola\Syrup\Tests\Monolog\Processor;

use Keboola\DebugLogUploader\UploaderS3;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Keboola\Syrup\Monolog\Processor\RequestProcessor;
use Keboola\Syrup\Test\Monolog\TestCase;

class RequestProcessorTest extends TestCase
{
    public function testProcessor()
    {
        $s3Uploader = new UploaderS3([
            'aws-access-key' => AWS_ACCESS_KEY_ID,
            'aws-secret-key' => AWS_SECRET_ACCESS_KEY,
            's3-upload-path' => AWS_S3_BUCKET . AWS_S3_BUCKET_LOGS_PATH,
            'aws-region' => AWS_REGION,
            'url-prefix' => 'https://connection.keboola.com/admin/utils/logs?file=',
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
