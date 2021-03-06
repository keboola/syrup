<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 06/12/13
 * Time: 16:29
 */

namespace Keboola\Syrup\Service\StorageApi;

use Keboola\StorageApi\ClientException;
use Keboola\StorageApi\Client;
use function Keboola\StorageApi\createSimpleJobPollDelay;
use Keboola\Syrup\Exception\ApplicationException;
use Keboola\Syrup\Exception\SimpleException;
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

    public function __construct(RequestStack $requestStack, $storageApiUrl = 'https://connection.keboola.com')
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
                throw new SimpleException($e->getCode(), "Invalid StorageApi Token", $e);
            } elseif ($e->getCode() < 500) {
                throw new SimpleException($e->getCode(), $e->getMessage(), $e);
            }
            throw $e;
        }
    }

    public function getBackoffTries($hostname)
    {
        if (getenv('STORAGE_API_CLIENT_BACKOFF_MAX_TRIES')) {
            return getenv('STORAGE_API_CLIENT_BACKOFF_MAX_TRIES');
        }

        // keep the backoff settings minimal for API servers
        if (false === strstr($hostname, 'worker')) {
            return 3;
        }

        return 11;
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

            $this->setClient(
                new Client(
                    [
                        'token' => $request->headers->get('X-StorageApi-Token'),
                        'url' => $this->storageApiUrl,
                        'userAgent' => explode('/', $request->getPathInfo())[1],
                        'backoffMaxTries' => $this->getBackoffTries(gethostname())
                    ]
                )
            );

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
