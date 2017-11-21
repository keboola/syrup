<?php
/**
 * IndexController.php
 *
 * @author: Miroslav Čillík <miro@keboola.com>
 * @created: 22.8.13
 */

namespace Keboola\Syrup\Controller;

use Keboola\ObjectEncryptor\ObjectEncryptor;
use Keboola\Syrup\Exception\SyrupComponentException;
use Keboola\Syrup\Exception\UserException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

/**
 * Class PublicController - controller for actions not requiring Storage Tokens
 * @package Keboola\Syrup\Controller
 */
class PublicController extends BaseController
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

    public function notFoundAction()
    {
        throw new SyrupComponentException(404, "Route not found");
    }

    /**
     * Run Action
     *
     * Creates new job, saves it to Elasticsearch and add to SQS
     *
     * @param Request $request
     * @return Response
     * @throws UserException
     */
    public function encryptAction(Request $request)
    {
        /** @var ObjectEncryptor $encryptor */
        $encryptor = $this->container->get('syrup.object_encryptor_factory')->getEncryptor();
        $contentType = $request->headers->get('Content-type');
        $contentType = strtolower(trim(explode(';', $contentType)[0]));
        if ($contentType == "text/plain") {
            $encryptedValue = $encryptor->encrypt($request->getContent());
            return $this->createResponse($encryptedValue, 200, ["Content-Type" => "text/plain"]);
        } elseif ($contentType == "application/json") {
            $params = $this->getPostJson($request, false);
            $encryptedValue = $encryptor->encrypt($params);
            return $this->createJsonResponse($encryptedValue, 200, ["Content-Type" => "application/json"]);
        } else {
            throw new UserException("Incorrect Content-Type.");
        }
    }
}
