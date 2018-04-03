<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 20/01/14
 * Time: 13:34
 */

namespace Keboola\Syrup\Controller;

use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Keboola\Syrup\Exception\UserException;
use Keboola\Temp\Temp;

class BaseController extends Controller
{
    /** @var Logger */
    protected $logger;

    /** @var Temp */
    protected $temp;

    /** @var String */
    protected $componentName;


    public function preExecute(Request $request)
    {
        $this->componentName = $this->container->getParameter('app_name');

        $this->initLogger();
        $this->initTemp();

        $pathInfo = explode('/', $request->getPathInfo());
        $this->logger->debug(
            'Component ' . $this->componentName . ' started action ' . (count($pathInfo) > 2 ? $pathInfo[2] : "")
        );
    }

    protected function initTemp()
    {
        $this->temp = $this->get('syrup.temp');
    }

    protected function initLogger()
    {
        $this->logger = $this->container->get('logger');
    }

    public function createResponse($content = '', $status = '200', $headers = array())
    {
        return new Response($content, $status, $this->commonHeaders($headers));
    }

    public function createJsonResponse($data = null, $status = '200', $headers = array())
    {
        return new JsonResponse($data, $status, $this->commonHeaders($headers));
    }

    /**
     * Extracts POST data in JSON from request
     * @param Request $request
     * @param bool $assoc
     * @return array|mixed
     */
    protected function getPostJson(Request $request, $assoc = true)
    {
        $return = array();
        $body = $request->getContent();

        if (!empty($body) && !is_null($body) && $body != 'null') {
            $return = json_decode($body, $assoc);
            if (json_last_error() != JSON_ERROR_NONE) {
                throw new UserException("Bad JSON format of request body: " . json_last_error_msg());
            }
        }

        return $return;
    }

    protected function commonHeaders($headers)
    {
        $headers['Access-Control-Allow-Origin'] = '*';
        $headers['Access-Control-Allow-Methods'] = '*';
        $headers['Access-Control-Allow-Headers'] = '*';

        $headers['Cache-Control'] = 'private, no-cache, no-store, must-revalidate';

        return $headers;
    }
}
