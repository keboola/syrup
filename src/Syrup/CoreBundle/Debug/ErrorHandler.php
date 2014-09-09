<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 29/08/14
 * Time: 16:04
 */

namespace Syrup\CoreBundle\Debug;


use Symfony\Component\Debug\ErrorHandler as BaseErrorHandler;

class ErrorHandler extends BaseErrorHandler
{
	public static function register($level = null, $displayErrors = true)
	{
		$handler = new static();
		$handler->setLevel($level);
		$handler->setDisplayErrors($displayErrors);

		ini_set('display_errors', 0);
		set_error_handler(array($handler, 'handle'));

		return $handler;
	}
}
