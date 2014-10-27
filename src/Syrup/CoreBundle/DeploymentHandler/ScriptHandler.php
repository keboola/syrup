<?php

namespace Syrup\CoreBundle\DeploymentHandler;

use Composer\Script\Event,
	Composer\IO\IOInterface;
use Aws\S3\S3Client;

class ScriptHandler
{
	const PARAMETERS_DIR = "vendor/keboola/syrup/app/config/";

	public static function getSharedParameters(Event $event)
	{
		self::getFile($event, "parameters_shared.yml", "syrup/parameters_shared.yml");
	}

	public static function getParameters(Event $event)
	{
		$extra = $event->getComposer()->getPackage()->getExtra();
		if (empty($extra['syrup-app-name'])) {
			// interactive if $event->getIO()->isInteractive() ?
			throw new \Exception('The app name (ie "ex-dummy") has to be set in composer.json "extra": {"syrup-app-name": "ex-dummy"}');
		} else {
			$appName = $extra['syrup-app-name'];
		}

		$appName = $event->getComposer()->getPackage()->getExtra()['syrup-app-name'];

		self::getFile($event, "parameters.yml", "syrup/{$appName}/parameters.yml");
	}

	protected static function getFile(Event $event, $filename, $s3key)
	{
		if ($event->isDevMode()) {
			if ($event->getIO()->isInteractive()
				&& !$event->getIO()->askConfirmation("<comment>Get <question>{$filename}</question> from development S3 bucket? [<options=bold>y</options=bold>/n]: </comment>", true)
			) {
				self::getFromIO($event->getIO(), $filename);
			} else {
				self::getFromS3($event->getIO(), $s3key, self::PARAMETERS_DIR . $filename, true);
			}
		} else {
			self::getFromS3($event->getIO(), $s3key, self::PARAMETERS_DIR . $filename);
		}
	}

	/**
	 * @brief Ask for a path to a file
	 *
	 * @param Event $event
	 * @param string $filename parameters.yml or parameters_shared.yml
	 * @param string $pathname file to look for before asking in IO
	 * @return void
	 */
	protected static function getFromIO(IOInterface $io, $filename, $pathname = "")
	{
		if (!file_exists($pathname)) {
			if ($io->isInteractive()) {
				$try = 0;
				while (!file_exists($pathname)) {
					if ($try >= 3) {
						throw new \InvalidArgumentException("3 attempts exhausted, {$filename} not found!");
					}
					if ($try > 0) {
						$io->write("<error>File<options=bold;fg=yellow> {$pathname} </options=bold;fg=yellow>does not exist!</error>");
					}

					$try++;
					$pathname = $io->ask("<comment>Path to '{$filename}':</comment> ", $pathname);
				}
			} else {
				throw new \Exception("Failed to retrieve {$filename} from IO: Input is not interactive");
			}
		}

		$dest = self::PARAMETERS_DIR . $filename;
		copy($pathname, $dest);
		$io->write("<info>File {$pathname} copied to {$dest}</info>");
	}

	/**
	 * @brief Download a file from S3 to $path
	 *
	 * @param string $key "path" to a file on S3
	 * @param string $path Local path to save the file within app
	 * @param bool $dev Development environment
	 * @return void
	 */
	protected static function getFromS3(IOInterface $io, $key, $path, $dev = false)
	{
		$client = S3Client::factory();
		$client->getObject(array(
			'Bucket' => (bool) $dev ? 'keboola-configs-testing' : 'keboola-configs',
			'Key'	=> $key,
			'SaveAs' => $path
		));
		$io->write("<info>File <comment>{$path}</comment> downloaded from S3</info>");
	}
}
