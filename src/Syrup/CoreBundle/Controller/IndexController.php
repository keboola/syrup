<?php
/**
 * IndexController.php
 *
 * @author: Miroslav Čillík <miro@keboola.com>
 * @created: 22.8.13
 */

namespace Syrup\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;

class IndexController extends Controller
{
	/**
	 * Displays Syrup components with their recent version
	 *
	 * @return JsonResponse
	 */
	public function indexAction()
	{
		$cmd = "cd " . ROOT_PATH .  "; ./composer.phar show --installed | awk '{ print $1 \":\" $2 }'";

		$output = array();
		$return_var = null;
		exec($cmd, $output, $return_var);

		$syrupComponents = array();

		foreach ($output as $row) {
			$rArr = explode(":", $row);

			$kArr = explode("/", $rArr[0]);

			if ($kArr[0] == 'syrup' || $kArr[0] ==  'keboola') {
				$syrupComponents[$rArr[0]] = $rArr[1];
			}
		}

		return new JsonResponse(array(
			"host"          => $_SERVER["HTTP_HOST"],
			"components"    => $syrupComponents,
			"documentation" => "http://documentation.keboola.com/syrup"
		));
	}

}
