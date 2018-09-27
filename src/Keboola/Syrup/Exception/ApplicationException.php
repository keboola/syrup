<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 15/01/14
 * Time: 13:11
 */

namespace Keboola\Syrup\Exception;

class ApplicationException extends SyrupComponentException
{
    public function __construct($message, $previous = null, array $data = [])
    {
        parent::__construct(500, $message, $previous, $data);
    }
}
