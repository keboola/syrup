<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 06/12/13
 * Time: 16:29
 */

namespace Keboola\Syrup\Service\StorageApi;

use Keboola\StorageApi\Client;
use Keboola\StorageApi\ClientException;
use Keboola\Syrup\Exception\SyrupComponentException;
use Symfony\Component\HttpFoundation\Request;
use Keboola\Syrup\Exception\NoRequestException;
use Keboola\Syrup\Exception\UserException;

class StorageApiService
{
    /** @var Request */
    protected $request;

    /** @var Client */
    protected $client;

    protected $storageApiUrl;

    public function __construct($storageApiUrl = 'https://connection.keboola.com')
    {
        $this->storageApiUrl = $storageApiUrl;
    }

    public function setRequest($request = null)
    {
        $this->request = $request;
    }

    public function setClient(Client $client)
    {
        $this->client = $client;
    }

    public function getClient()
    {
        if ($this->client == null) {
            if ($this->request == null) {
                throw new NoRequestException();
            }

            if (!$this->request->headers->has('X-StorageApi-Token')) {
                throw new UserException('Missing StorageAPI token');
            }

            if ($this->request->headers->has('X-StorageApi-Url')) {
                $this->storageApiUrl = $this->request->headers->get('X-StorageApi-Url');
            }

            $this->client = new Client([
                'token' => $this->request->headers->get('X-StorageApi-Token'),
                'url' => $this->storageApiUrl,
                'userAgent' => explode('/', $this->request->getPathInfo())[1],
            ]);

            try {
                $this->client->verifyToken();
            } catch (ClientException $e) {
                throw new SyrupComponentException(401, "Invalid Access Token", $e);
            }

            if ($this->request->headers->has('X-KBC-RunId')) {
                $kbcRunId = $this->client->generateRunId($this->request->headers->get('X-KBC-RunId'));
            } else {
                $kbcRunId = $this->client->generateRunId();
            }

            $this->client->setRunId($kbcRunId);
        }

        return $this->client;
    }
}
