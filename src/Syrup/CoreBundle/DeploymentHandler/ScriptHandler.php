<?php

namespace Syrup\CoreBundle\DeploymentHandler;

use Composer\Script\Event;
use Symfony\Component\Yaml\Yaml;

class ScriptHandler
{
    public static function getSharedParameters(Event $event)
    {

		echo "dev?: ";
		var_dump($event->isDevMode());
    }
}
