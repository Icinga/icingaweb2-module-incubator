<?php

namespace gipfl\Curl;

use Exception;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class RequestError extends Exception // implements Psr\Http\Client\RequestExceptionInterface
{
    /** @var RequestInterface */
    protected $request;

    /** @var ResponseInterface */
    protected $response;

    public function __construct(
        $message,
        RequestInterface $request,
        ?ResponseInterface $response = null,
        $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * @return RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return ?ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }
}
