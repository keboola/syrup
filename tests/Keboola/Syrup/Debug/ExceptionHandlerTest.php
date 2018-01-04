<?php
/**
 * Created by PhpStorm.
 * User: miroslavcillik
 * Date: 11/01/16
 * Time: 13:52
 */
namespace Keboola\Syrup\Tests\Debug;

use Keboola\StorageApi\MaintenanceException;
use Keboola\Syrup\Debug\ServerApi;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ExceptionHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function testHandleCli()
    {
        ServerApi::$sapi = 'cli';
        ServerApi::$headers = [];

        $message = 'Project disabled';

        $exceptionHandler = new \Keboola\Syrup\Debug\ExceptionHandler();

        ob_start();
        $exceptionHandler->handle(new MaintenanceException($message, 600, []));
        $response = ob_get_clean();

        $this->assertContains($message, $response);
        $this->assertContains('status', $response);

        $this->assertEquals(6, count(explode(PHP_EOL, $response)));

        $response = json_decode($response, true);
        $this->assertNull($response);

        $this->assertCount(0, ServerApi::$headers);
    }

    public function testHandleWebServerEnvProd()
    {
        ServerApi::$sapi = 'cli-server';
        $exceptionHandler = new \Keboola\Syrup\Debug\ExceptionHandler(true, 'UTF-8', 'prod');
        $message = 'Project disabled';

        // exception handling
        ServerApi::$headers = [];

        ob_start();
        $exceptionHandler->handle(new MaintenanceException($message, 600, []));
        $response = ob_get_clean();

        $response = json_decode($response, true);
        $this->assertTrue(is_array($response));

        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('error', $response['status']);
        $this->assertArrayHasKey('message', $response);
        $this->assertEquals('An error occured. Please contact support@keboola.com', $response['message']);

        $this->assertCount(2, ServerApi::$headers);

        $httpHeaders = array_filter(ServerApi::$headers, function (array $header) {
            return preg_match('/^http\/1\.0/ui', $header[0]);
        });

        $this->assertCount(1, $httpHeaders);

        $httpHeader = reset($httpHeaders);
        $this->assertContains('503', $httpHeader[0]);
    }

    public function testHandleWebServer()
    {
        ServerApi::$sapi = 'cli-server';
        $exceptionHandler = new \Keboola\Syrup\Debug\ExceptionHandler();
        $message = 'Project disabled';

        // exception handling
        ServerApi::$headers = [];

        ob_start();
        $exceptionHandler->handle(new MaintenanceException($message, 600, []));
        $response = ob_get_clean();

        $response = json_decode($response, true);
        $this->assertTrue(is_array($response));

        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('error', $response['status']);
        $this->assertArrayHasKey('message', $response);
        $this->assertEquals($message, $response['message']);

        $this->assertCount(2, ServerApi::$headers);

        $httpHeaders = array_filter(ServerApi::$headers, function (array $header) {
            return preg_match('/^http\/1\.0/ui', $header[0]);
        });

        $this->assertCount(1, $httpHeaders);

        $httpHeader = reset($httpHeaders);
        $this->assertContains('503', $httpHeader[0]);

        // http exception handling
        ServerApi::$headers = [];

        ob_start();
        $exceptionHandler->handle(new HttpException(500, $message, null, ['foo' => 'bar']));
        $response = ob_get_clean();

        $response = json_decode($response, true);
        $this->assertTrue(is_array($response));

        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('error', $response['status']);
        $this->assertArrayHasKey('message', $response);
        $this->assertEquals($message, $response['message']);

        $this->assertCount(3, ServerApi::$headers);

        $hasFoo = false;
        foreach (ServerApi::$headers as $header) {
            if ($header[0] === 'foo: bar') {
                $hasFoo = true;
            }
        }
        $this->assertTrue($hasFoo);

        $httpHeaders = array_filter(ServerApi::$headers, function (array $header) {
            return preg_match('/^http\/1\.0/ui', $header[0]);
        });

        $this->assertCount(1, $httpHeaders);

        $httpHeader = reset($httpHeaders);
        $this->assertContains('500', $httpHeader[0]);
    }
    public function testGetContent()
    {
        $array = array_fill(0, 1000, "dummy stuff");
        $largeErrorContent = json_encode($array);

        $exception = new \Exception("error message [0]: " . $largeErrorContent, 500);
        for ($i=1; $i<=50; $i++) {
            $prevException = $exception;
            $exception = new \Exception("error message [$i]: " . $largeErrorContent, 500, $prevException);
        }

        $flattenException = \Keboola\Syrup\Debug\Exception\FlattenException::create($exception);
        $exceptionHandler = new \Keboola\Syrup\Debug\ExceptionHandler();
        $content = $exceptionHandler->getContent($flattenException);

        $found = strstr($content, "exceptions truncated");

        $this->assertNotFalse($found);
    }
}

require_once __DIR__ . '/ServerApiMock.php';
