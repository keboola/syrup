<?php

namespace Keboola\Syrup\Debug\Exception;

use Keboola\Syrup\Exception\SyrupComponentException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class FlattenException extends \Symfony\Component\Debug\Exception\FlattenException
{
    private $data;

    public static function create(\Exception $exception, $statusCode = null, array $headers = [])
    {
        $e = new static();
        $e->setMessage($exception->getMessage());
        $e->setCode($exception->getCode());

        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $headers = array_merge($headers, $exception->getHeaders());
        }

        if (null === $statusCode) {
            $statusCode = 500;
        }

        $e->setStatusCode($statusCode);
        $e->setHeaders($headers);
        $e->setTraceFromException($exception);
        $e->setClass(get_class($exception));
        $e->setFile($exception->getFile());
        $e->setLine($exception->getLine());
        if ($exception->getPrevious()) {
            $e->setPrevious(static::create($exception->getPrevious()));
        }

        if ($exception instanceof SyrupComponentException) {
            /** @var SyrupComponentException $exception */
            $e->setData($exception->getData());
        }

        return $e;
    }

    public function toArray()
    {
        $exceptions = [];
        foreach (array_merge([$this], $this->getAllPrevious()) as $exception) {
            /** @var FlattenException $exception */
            $exceptions[] = [
                'message' => $exception->getMessage(),
                'class' => $exception->getClass(),
                'trace' => $exception->getTrace(),
                'code' => $exception->getCode(),
                'data' => $exception->getData()
            ];
        }

        return $exceptions;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;
    }
}
