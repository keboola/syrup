<?php
/**
 * IndexController.php
 *
 * @author: Miroslav Čillík <miro@keboola.com>
 * @created: 22.8.13
 */

namespace Syrup\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class IndexController extends Controller
{
	/**
	 * Displays Syrup components with their recent version
	 *
	 * @return JsonResponse
	 */
	public function indexAction()
	{
		$rootPath = str_replace('web/../', '', ROOT_PATH);

		$filepath = $rootPath . '/../../composer/installed.json';

		if (!file_exists($filepath)) {
			$filepath = $rootPath . '/vendor/composer/installed.json';
		}

		$installedJson = file_get_contents($filepath);

		$jsonDecoder = new JsonDecode(true);
		$installedArr = $jsonDecoder->decode($installedJson, JsonEncoder::FORMAT);

		$syrupComponents = array();
		foreach ($installedArr as $package) {
			$nameArr = explode("/", $package['name']);

			if ($nameArr[0] == 'syrup' || $nameArr[0] == 'keboola') {
				$syrupComponents[$package['name']] = $package['version'];
			}
		}

		return new JsonResponse(array(
			"host"          => gethostname(),
			"components"    => $syrupComponents,
			"documentation" => "http://documentation.keboola.com/syrup"
		));
	}

}
