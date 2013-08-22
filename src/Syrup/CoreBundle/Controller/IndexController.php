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

class IndexController extends Controller
{

	public function indexAction()
	{
		$components = $this->container->getParameter('components');

		return new JsonResponse(array(
			"components"    => array_keys($components),
			"documentation" => "http://documentation.keboola.com/syrup"
		));
	}

}
