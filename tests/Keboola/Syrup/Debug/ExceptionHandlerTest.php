<?php

/**
 * Created by PhpStorm.
 * User: miroslavcillik
 * Date: 11/01/16
 * Time: 13:52
 */
class ExceptionHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function testGetContent()
    {
        $exception = new \Exception("error message [0]", 500);
        for ($i=1; $i<=3000; $i++) {
            $prevException = $exception;
            $exception = new \Exception("error message [$i]", 500, $prevException);
        }

        $flattenException = \Keboola\Syrup\Debug\Exception\FlattenException::create($exception);
        $exceptionHandler = new \Keboola\Syrup\Debug\ExceptionHandler();
        $content = $exceptionHandler->getContent($flattenException);

        $found = strstr($content, "680 exceptions truncated");

        $this->assertNotFalse($found);
    }

}