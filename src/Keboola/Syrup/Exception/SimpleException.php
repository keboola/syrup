<?php
/**
 * Created by PhpStorm.
 * User: miroslavcillik
 * Date: 28/07/16
 * Time: 10:58
 *
 * This exception just logs the exception message, without stack trace, no attachments uploaded to S3
 */

namespace Keboola\Syrup\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class SimpleException extends HttpException
{
    public function __construct($code, $message, $previous = null)
    {
        parent::__construct($code, $message, $previous, [], $code);
    }
}
