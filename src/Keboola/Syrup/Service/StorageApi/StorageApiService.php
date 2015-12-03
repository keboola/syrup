<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 06/12/13
 * Time: 16:29
 */

namespace Keboola\Syrup\Service\StorageApi;

use Keboola\StorageApi\Client;
use Symfony\Component\HttpFoundation\Request;
use Keboola\Syrup\Exception\NoRequestException;
use Keboola\Syrup\Exception\UserException;
use Symfony\Component\HttpFoundation\RequestStack;

class StorageApiService
{
    /** @var RequestStack */
    protected $requestStack;

    /** @var Request */
    protected $request;

    /** @var Client */
    protected $client;

    protected $storageApiUrl;

    public function __construct($storageApiUrl = 'https://connection.keboola.com', RequestStack $requestStack = null)
    {
        $this->storageApiUrl = $storageApiUrl;
        if ($requestStack == null) {
            $requestStack = new RequestStack();
        }
        $this->requestStack = $requestStack;
    }

    /**
     * @deprecated request should be injected via requestStack in constructor
     * @param $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    public function setClient(Client $client)
    {
        $this->client = $client;
    }

    public function getClient()
    {
        if ($this->request != null) {
            $request = $this->request;
        } else {
            $request = $this->requestStack->getCurrentRequest();
        }

        if ($this->client == null) {
            if ($request == null) {
                throw new NoRequestException();
            }

            if (!$request->headers->has('X-StorageApi-Token')) {
                throw new UserException('Missing StorageAPI token');
            }

            if ($request->headers->has('X-StorageApi-Url')) {
                $this->storageApiUrl = $request->headers->get('X-StorageApi-Url');
            }

            $this->client = new Client([
                'token' => $request->headers->get('X-StorageApi-Token'),
                'url' => $this->storageApiUrl,
                'userAgent' => explode('/', $request->getPathInfo())[1],
            ]);

            if ($request->headers->has('X-KBC-RunId')) {
                $kbcRunId = $this->client->generateRunId($request->headers->get('X-KBC-RunId'));
            } else {
                $kbcRunId = $this->client->generateRunId();
            }

            $this->client->setRunId($kbcRunId);
        }

        return $this->client;
    }
}
