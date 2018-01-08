<?php
/**
 * @package syrup-component-bundle
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace Keboola\Syrup\Tests\Monolog\Processor;

use Keboola\DebugLogUploader\UploaderS3;
use Keboola\ObjectEncryptor\Legacy\Wrapper\BaseWrapper;
use Keboola\ObjectEncryptor\ObjectEncryptor;
use Keboola\Syrup\Job\Metadata\Job;
use Keboola\Syrup\Monolog\Processor\JobProcessor;
use Keboola\Syrup\Monolog\Processor\RequestProcessor;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;
use Keboola\Syrup\Monolog\Processor\SyslogProcessor;
use Keboola\Syrup\Service\StorageApi\StorageApiService;
use Keboola\Syrup\Test\Monolog\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;

class SyslogProcessorTest extends TestCase
{
    private function getSysLogProcessor()
    {
        $s3Uploader = new UploaderS3([
            'aws-access-key' => AWS_ACCESS_KEY_ID,
            'aws-secret-key' => AWS_SECRET_ACCESS_KEY,
            's3-upload-path' => AWS_S3_BUCKET . AWS_S3_BUCKET_LOGS_PATH,
            'aws-region' => AWS_REGION,
            'url-prefix' => 'https://connection.keboola.com/admin/utils/logs?file=',
        ]);

        $request = new Request();
        $request->headers->add(['x-storageapi-token' => SAPI_TOKEN]);
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $storageApiService = new StorageApiService($requestStack, SAPI_URL);

        return new SyslogProcessor(SYRUP_APP_NAME, $storageApiService, $s3Uploader);
    }

    public function testProcessorTokenLong()
    {
        $processor = $this->getSysLogProcessor();
        $record = $this->getRecord(Logger::WARNING, str_repeat('batman', 1000));
        $newRecord = $processor($record);

        $this->assertEquals(8, count($newRecord));
        $this->assertArrayHasKey('message', $newRecord);
        $this->assertArrayHasKey('component', $newRecord);
        $this->assertArrayHasKey('runId', $newRecord);
        $this->assertArrayHasKey('pid', $newRecord);
        $this->assertArrayHasKey('priority', $newRecord);
        $this->assertArrayHasKey('level', $newRecord);
        $this->assertArrayHasKey('token', $newRecord);
        $this->assertArrayHasKey('attachment', $newRecord);

        $this->assertArrayHasKey('id', $newRecord['token']);
        $this->assertArrayHasKey('description', $newRecord['token']);
        $this->assertArrayNotHasKey('token', $newRecord['token']);
        $this->assertArrayHasKey('owner', $newRecord['token']);

        $this->assertArrayHasKey('id', $newRecord['token']['owner']);
        $this->assertArrayHasKey('name', $newRecord['token']['owner']);

        $this->assertEquals(SYRUP_APP_NAME, $newRecord['component']);
        $this->assertEquals(Logger::WARNING, $newRecord['level']);
        $this->assertEquals('WARNING', $newRecord['priority']);
        $this->assertLessThan(strlen($record['message']), strlen($newRecord['message']));
    }

    public function testProcessorTokenShort()
    {
        $processor = $this->getSysLogProcessor();
        $record = $this->getRecord(Logger::WARNING, 'batman');
        $newRecord = $processor($record);

        $this->assertGreaterThan(7, count($newRecord));
        $this->assertArrayHasKey('message', $newRecord);
        $this->assertArrayHasKey('component', $newRecord);
        $this->assertArrayHasKey('runId', $newRecord);
        $this->assertArrayHasKey('pid', $newRecord);
        $this->assertArrayHasKey('priority', $newRecord);
        $this->assertArrayHasKey('level', $newRecord);
        $this->assertArrayHasKey('component', $newRecord);
        $this->assertArrayHasKey('token', $newRecord);
        $this->assertArrayNotHasKey('attachment', $newRecord);

        $this->assertArrayHasKey('id', $newRecord['token']);
        $this->assertArrayHasKey('description', $newRecord['token']);
        $this->assertArrayNotHasKey('token', $newRecord['token']);
        $this->assertArrayHasKey('owner', $newRecord['token']);

        $this->assertArrayHasKey('id', $newRecord['token']['owner']);
        $this->assertArrayHasKey('name', $newRecord['token']['owner']);

        $this->assertEquals(SYRUP_APP_NAME, $newRecord['component']);
        $this->assertEquals(Logger::WARNING, $newRecord['level']);
        $this->assertEquals('WARNING', $newRecord['priority']);
        $this->assertEquals($record['message'], $newRecord['message']);
    }

    public function testProcessorInitInvalidTokenLong()
    {
        $s3Uploader = new UploaderS3([
            'aws-access-key' => AWS_ACCESS_KEY_ID,
            'aws-secret-key' => AWS_SECRET_ACCESS_KEY,
            's3-upload-path' => AWS_S3_BUCKET . AWS_S3_BUCKET_LOGS_PATH,
            'aws-region' => AWS_REGION,
            'url-prefix' => 'https://connection.keboola.com/admin/utils/logs?file=',
        ]);

        $request = new Request();
        $request->headers->add(['x-storageapi-token' => 'invalid']);
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $storageApiService = new StorageApiService($requestStack, SAPI_URL);

        $record = $this->getRecord(Logger::WARNING, str_repeat('batman', 1000));
        // instantiation must not fail
        $processor = new SyslogProcessor(SYRUP_APP_NAME, $storageApiService, $s3Uploader);
        $newRecord = $processor($record);

        $this->assertEquals(7, count($newRecord));
        $this->assertArrayHasKey('message', $newRecord);
        $this->assertArrayHasKey('component', $newRecord);
        $this->assertArrayHasKey('runId', $newRecord);
        $this->assertArrayHasKey('pid', $newRecord);
        $this->assertArrayHasKey('priority', $newRecord);
        $this->assertArrayHasKey('level', $newRecord);
        $this->assertArrayHasKey('attachment', $newRecord);

        $this->assertArrayNotHasKey('token', $newRecord);

        $this->assertEquals(SYRUP_APP_NAME, $newRecord['component']);
        $this->assertEquals(Logger::WARNING, $newRecord['level']);
        $this->assertEquals('WARNING', $newRecord['priority']);
        $this->assertLessThan(strlen($record['message']), strlen($newRecord['message']));
    }

    public function testHTTPRequestLong()
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

        $_SERVER['argv'] = ['foo', 'bar'];
        $_SERVER['REMOTE_ADDR'] = '123.123.123.123';
        $_SERVER['HTTP_USER_AGENT'] = 'testingAgent';
        $requestStack = new RequestStack();
        $request = new Request($params);
        $requestStack->push($request);

        $processor = new RequestProcessor($requestStack, $s3Uploader);
        $record = $this->getRecord(Logger::WARNING, str_repeat('batman', 1000));
        $record = $processor($record);
        $processor = $this->getSysLogProcessor();
        $newRecord = $processor($record);

        $this->assertEquals(10, count($newRecord));
        $this->assertArrayHasKey('message', $newRecord);
        $this->assertArrayHasKey('component', $newRecord);
        $this->assertArrayHasKey('runId', $newRecord);
        $this->assertArrayHasKey('pid', $newRecord);
        $this->assertArrayHasKey('priority', $newRecord);
        $this->assertArrayHasKey('level', $newRecord);
        $this->assertArrayHasKey('cliCommand', $newRecord);
        $this->assertArrayHasKey('attachment', $newRecord);

        $this->assertEquals('foo bar', $newRecord['cliCommand']);
        $this->assertEquals(SYRUP_APP_NAME, $newRecord['component']);

        $this->assertArrayHasKey('token', $newRecord);
        $this->assertArrayHasKey('http', $newRecord);
        $this->assertEquals(3, count($newRecord['http']));
        $this->assertArrayHasKey('url', $newRecord['http']);
        $this->assertArrayHasKey('userAgent', $newRecord['http']);
        $this->assertArrayHasKey('ip', $newRecord['http']);
    }

    public function testJobLong()
    {
        $processor = new JobProcessor();
        $configEncryptor = new ObjectEncryptor();
        $wrapper = new BaseWrapper();
        $wrapper->setKey(uniqid('foobar'));
        $configEncryptor->pushWrapper($wrapper);
        $jobId = intval(uniqid());
        $processor->setJob(new Job($configEncryptor, [
            'id' => $jobId,
            'runId' => uniqid(),
            'lockName' => uniqid()
        ], null, null, null));
        $record = $this->getRecord(Logger::WARNING, str_repeat('batman', 1000));
        $record = $processor($record);
        $processor = $this->getSysLogProcessor();
        $newRecord = $processor($record);

        $this->assertEquals(9, count($newRecord));
        $this->assertArrayHasKey('message', $newRecord);
        $this->assertArrayHasKey('component', $newRecord);
        $this->assertArrayHasKey('runId', $newRecord);
        $this->assertArrayHasKey('pid', $newRecord);
        $this->assertArrayHasKey('priority', $newRecord);
        $this->assertArrayHasKey('level', $newRecord);
        $this->assertArrayHasKey('component', $newRecord);
        $this->assertArrayHasKey('attachment', $newRecord);

        $this->assertArrayHasKey('token', $newRecord);
        $this->assertEquals(1, count($newRecord['job']));
        $this->assertArrayHasKey('id', $newRecord['job']);

        $this->assertEquals($jobId, $newRecord['job']['id']);
    }

    public function testProcessorExceptionLong()
    {
        $processor = $this->getSysLogProcessor();
        $record = $this->getRecord(
            Logger::WARNING,
            str_repeat('batman', 1000),
            ['exceptionId' => '1234', 'exception' => new \Exception("barFoo message")]
        );
        $newRecord = $processor($record);

        $this->assertEquals(10, count($newRecord));
        $this->assertArrayHasKey('message', $newRecord);
        $this->assertArrayHasKey('component', $newRecord);
        $this->assertArrayHasKey('runId', $newRecord);
        $this->assertArrayHasKey('pid', $newRecord);
        $this->assertArrayHasKey('priority', $newRecord);
        $this->assertArrayHasKey('level', $newRecord);
        $this->assertArrayHasKey('component', $newRecord);
        $this->assertArrayHasKey('attachment', $newRecord);

        $this->assertEquals(SYRUP_APP_NAME, $newRecord['component']);

        $this->assertArrayHasKey('token', $newRecord);
        $this->assertArrayHasKey('exceptionId', $newRecord);
        $this->assertArrayHasKey('exception', $newRecord);
        $this->assertEquals('1234', $newRecord['exceptionId']);
        $this->assertEquals('barFoo message', $newRecord['exception']['message']);
    }

    /**
     * Test that explicitly provided component name is honored.
     */
    public function testProcessorExplicitComponent()
    {
        $record = $this->getRecord();
        $record['component'] = 'fooBar';
        $record['app'] = 'baz';
        $processor = $this->getSysLogProcessor();
        $newRecord = $processor($record);
        $this->assertArrayHasKey('component', $newRecord);
        $this->assertArrayHasKey('app', $newRecord);
        $this->assertEquals('fooBar', $newRecord['component']);
        $this->assertEquals('baz', $newRecord['app']);
    }

    public function testProcessorExplicitComponentLong()
    {
        $record = $this->getRecord(Logger::WARNING, str_repeat('batman', 1000));
        $record['component'] = 'fooBar';
        $record['app'] = 'baz';
        $processor = $this->getSysLogProcessor();
        $newRecord = $processor($record);
        $this->assertArrayHasKey('component', $newRecord);
        $this->assertArrayHasKey('app', $newRecord);
        $this->assertEquals('fooBar', $newRecord['component']);
        $this->assertEquals('baz', $newRecord['app']);
    }
}
