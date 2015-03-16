<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 04/03/15
 * Time: 12:56
 */

namespace Keboola\Syrup\Monolog\Handler;

use Monolog\Handler\AbstractHandler;

class SignalHandler extends AbstractHandler
{
    public function handle(array $record)
    {
        // call handlers for pending signals
        if (php_sapi_name() == "cli") {
            pcntl_signal_dispatch();
        }

        // let other logging handlers handle the record
        return false;
    }
}
