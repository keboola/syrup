<?php
/**
 * Debug.php
 *
 * @author: Miroslav Čillík <miro@keboola.com>
 * @created: 22.8.13
 */

namespace Keboola\Syrup\Debug;

use Symfony\Component\Debug\DebugClassLoader;
use Symfony\Component\Debug\ErrorHandler;

class Debug
{
    private static $enabled = false;

    public static function enable($environment = 'dev')
    {
        if (static::$enabled) {
            return;
        }

        static::$enabled = true;

        error_reporting(-1);

        // Beware, ExceptionHandler::register and ErrorHandler::register must be called in this order
        // to fatal errors handling work
        ExceptionHandler::register(true, $environment);
        ErrorHandler::register();
        DebugClassLoader::enable();
    }
}
