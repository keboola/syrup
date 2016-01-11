<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 06/12/13
 * Time: 16:29
 */

namespace Keboola\Syrup\Service\StorageApi;

use Keboola\StorageApi\ClientException;
use Keboola\StorageApi\Client;
use Keboola\Syrup\Exception\ApplicationException;
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

    protected $tokenData;

    public function __construct($storageApiUrl = 'https://connection.keboola.com', RequestStack $requestStack)
    {
        $this->storageApiUrl = $storageApiUrl;
        $this->requestStack = $requestStack;
    }

    protected function verifyClient(Client $client)
    {
        try {
            $this->tokenData = $client->verifyToken();
            return $client;
        } catch (ClientException $e) {
            if ($e->getCode() == 401) {
                throw new UserException("Invalid StorageApi Token");
            }
            throw $e;
        }
    }

    public function setClient(Client $client)
    {
        $this->client = $this->verifyClient($client);
    }

    public function getClient()
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($this->client == null) {
            if ($request == null) {
                throw new NoRequestException();
            }

            if (!$request->headers->has('X-StorageApi-Token')) {
                throw new UserException('Missing StorageAPI token');
            }

            $this->setClient(new Client([
                'token' => $request->headers->get('X-StorageApi-Token'),
                'url' => $this->storageApiUrl,
                'userAgent' => explode('/', $request->getPathInfo())[1],
            ]));

            if ($request->headers->has('X-KBC-RunId')) {
                $this->client->setRunId($request->headers->get('X-KBC-RunId'));
            }

        }

        return $this->client;
    }

    public function getTokenData()
    {
        if ($this->tokenData == null) {
            throw new ApplicationException('StorageApi Client was not initialized');
        }
        return $this->tokenData;
    }
}
