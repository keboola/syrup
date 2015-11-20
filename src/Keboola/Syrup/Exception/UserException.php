<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 19/12/13
 * Time: 12:22
 */

namespace Keboola\Syrup\Exception;

class UserException extends SyrupComponentException
{
    public function __construct($message, $previous = null, array $data = [])
    {
        parent::__construct(400, $message, $previous, $data);
    }
}
