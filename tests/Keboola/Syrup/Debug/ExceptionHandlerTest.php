<?php
/**
 * Created by PhpStorm.
 * User: miroslavcillik
 * Date: 11/01/16
 * Time: 13:52
 */
namespace Keboola\Syrup\Tests\Debug;

class ExceptionHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function testGetContent()
    {
        $array = array_fill(0, 1000, "dummy stuff");
        $largeErrorContent = json_encode($array);

        $exception = new \Exception("error message [0]: " . $largeErrorContent, 500);
        for ($i=1; $i<=250; $i++) {
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
