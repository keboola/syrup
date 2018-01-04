<?php
namespace Keboola\Syrup\Debug;

class ServerApi
{
    static $sapi = 'cli-server';
    static $headers = array();

    public static function testHeader()
    {
        self::$headers[] = func_get_args();
    }
}

function headers_sent()
{
    return false;
}

function header($str, $replace = true, $status = null)
{
    ServerApi::testHeader($str, $replace, $status);
}

function php_sapi_name()
{
    return ServerApi::$sapi;
}