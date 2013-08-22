<?php
/**
 * Debug.php
 *
 * @author: Miroslav Čillík <miro@keboola.com>
 * @created: 22.8.13
 */

namespace Syrup\CoreBundle\Debug;

use Symfony\Component\ClassLoader\DebugClassLoader;
use Symfony\Component\Debug\Debug as BaseDebug;
use Symfony\Component\Debug\ErrorHandler;

class Debug extends BaseDebug
{
	private static $enabled = false;

	/**
	 * Enables the debug tools.
	 *
	 * This method registers an error handler and an exception handler.
	 *
	 * If the Symfony ClassLoader component is available, a special
	 * class loader is also registered.
	 *
	 * @param integer $errorReportingLevel The level of error reporting you want
	 * @param Boolean $displayErrors       Whether to display errors (for development) or just log them (for production)
	 * @param string $environment
	 */
	public static function enable($errorReportingLevel = null, $displayErrors = true, $environment = 'dev')
	{
		if (static::$enabled) {
			return;
		}

		static::$enabled = true;

		error_reporting(-1);

		ErrorHandler::register($errorReportingLevel, $displayErrors);
		if ('cli' !== php_sapi_name()) {
			ExceptionHandler::register(true, $environment);
			// CLI - display errors only if they're not already logged to STDERR
		} elseif ($displayErrors && (!ini_get('log_errors') || ini_get('error_log'))) {
			ini_set('display_errors', 1);
		}

		if (class_exists('Symfony\Component\ClassLoader\DebugClassLoader')) {
			DebugClassLoader::enable();
		}
	}
}
