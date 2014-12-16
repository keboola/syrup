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
use Symfony\Component\Yaml\Parser;

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

		$yaml = new Parser();
		$parameters = $yaml->parse(file_get_contents(__DIR__.'/../../../../app/config/parameters.yml'));

		$appName = $parameters['parameters']['app_name'];
		$exceptionId = $appName . '-' . md5(microtime());

		$logData = array(
			'message'       => $exception->getMessage(),
			'level'         => $exception->getCode(),
			'channel'       => 'app',
			'datetime'      => array('date' => date('Y-m-d H:i:s')),
			'app'           => $appName,
			'priority'      => 'CRITICAL',
			'file'          => $exception->getFile(),
			'pid'           => getmypid(),
			'exceptionId'   => $exceptionId
		);

		// log to syslog
		syslog(LOG_ERR, json_encode($logData));

		$response = array(
			"status"    => "error",
			'message'   => 'An error occured. Please contact support@keboola.com',
			'exceptionId'   => $exceptionId
		);

		if (in_array($this->env, array('dev','test'))) {
			$response['message'] = $exception->getMessage();
		}

		$code = ($exception->getCode() >= 200 && $exception->getCode() < 600)?$exception->getCode():500;

		return new JsonResponse($response, $code, $exception->getHeaders());
	}
}
