<?php
/**
 * ExceptionHandler.php
 *
 * @author: Miroslav Čillík <miro@keboola.com>
 * @created: 22.8.13
 */

namespace Syrup\CoreBundle\Debug;


use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\Debug\ExceptionHandler as BaseExceptionHandler;
use Symfony\Component\HttpFoundation\JsonResponse;

class ExceptionHandler extends BaseExceptionHandler
{
	protected $env;

	public function __construct($debug = true, $charset = 'UTF-8', $env = 'dev')
	{
		$this->env = $env;
		parent::__construct($debug, $charset);
	}

	/**
	 * Registers the exception handler.
	 *
	 * @param Boolean $debug
	 *
	 * @param string $env
	 * @internal param string $charser
	 * @return ExceptionHandler The registered exception handler
	 */
	public static function register($debug = true, $env = 'dev')
	{
		$handler = new static($debug, 'UTF-8', $env);

		set_exception_handler(array($handler, 'handle'));

		return $handler;
	}

	/**
	 * Creates the error Response associated with the given Exception.
	 *
	 * @param \Exception|FlattenException $exception An \Exception instance
	 *
	 * @return JsonResponse A JsonResponse instance
	 */
	public function createResponse($exception)
	{
		if (!$exception instanceof FlattenException) {
			$exception = FlattenException::create($exception);
		}

		$response = array(
			"status"    => "error",
			'message'   => 'An error occured. Please contact support@keboola.com'
		);

		if (in_array($this->env, array('dev','test'))) {
			$response['message'] = $exception->getMessage();
			$response['exception'] = $exception->toArray();
		}

		return new JsonResponse($response, $exception->getStatusCode(), $exception->getHeaders());
	}
}
