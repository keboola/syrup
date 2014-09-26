<?php

namespace Syrup\CoreBundle\DeploymentHandler;

use Composer\Script\Event,
	Composer\IO\IOInterface;
use Aws\S3\S3Client;

class ScriptHandler
{
	const SHARED_PARAMETERS_PATH = "vendor/keboola/syrup/app/config/parameters_shared.yml";

    public static function getSharedParameters(Event $event)
    {
		if ($event->isDevMode()) {
			// Ask for the file & copy
			/** @var IOInterface $io */
			$io = $event->getIO();

			$paramsFile = './parameters_shared.yml';
			$asked = false;
			while (!file_exists($paramsFile)) {
				if ($asked) {
					$io->write("<error>File <options=bold;fg=yellow>{$paramsFile}</options=bold;fg=yellow> not found!</error>");
				}
				$asked = true;

				$paramsFile = $io->ask('<comment>Path to "shared_parameters.yml" to use for the development env:</comment> ', $paramsFile);
			}

			copy($paramsFile, self::SHARED_PARAMETERS_PATH);
		} else {
			$client = S3Client::factory();
			$client->getObject(array(
				'Bucket' => 'keboola-configs',
				'Key'    => 'syrup/parameters_shared.yml',
				'SaveAs' => self::SHARED_PARAMETERS_PATH
			));
		}
    }
}
