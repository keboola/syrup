<?php
/**
 * Debug.php
 *
 * @author: Miroslav Čillík <miro@keboola.com>
 * @created: 22.8.13
 */

namespace Keboola\Syrup\Debug;

use Symfony\Component\Debug\DebugClassLoader;
use Symfony\Component\Debug\Debug as BaseDebug;
use Symfony\Component\Debug\ErrorHandler;

class Debug extends BaseDebug
{
    private static $enabled = false;

    public static function enable($environment = 'dev')
    {
        if (static::$enabled) {
            return;
        }

        static::$enabled = true;

        error_reporting(-1);

        ExceptionHandler::register(true, $environment);
        ErrorHandler::register();
        DebugClassLoader::enable();
    }
}
