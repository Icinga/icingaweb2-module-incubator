<?php

namespace gipfl\Protocol\JsonRpc;

use Exception;
use InvalidArgumentException;
use React\Promise\Deferred;
use React\Promise\Promise;
use React\Stream\DuplexStreamInterface;
use RuntimeException;

class Connection
{
    /** @var DuplexStreamInterface */
    protected $connection;

    /** @var array */
    protected $handlers = [];

    /** @var array */
    protected $pending = [];

    protected $nsSeparator = '.';

    protected $nsRegex = '/\./';

    public function handle(DuplexStreamInterface $connection)
    {
        $this->connection = $connection;
        $this->connection->on('data', function ($data) {
            try {
                $this->handlePacket(Packet::decode($data));
            } catch (Exception $error) {
                echo $error->getMessage() . "\n";
                $response = new Response();
                $response->setError(Error::forException($error));
                $this->connection->write($response->toString());
            }
        });
    }

    public function setNamespaceSeparator($separator)
    {
        $this->nsSeparator = $separator;
        $this->nsRegex = '/' . preg_quote($separator, '/') . '/';

        return $this;
    }

    /**
     * @param Packet $packet
     */
    protected function handlePacket(Packet $packet)
    {
        if ($packet instanceof Response) {
            $this->handleResponse($packet);
        } elseif ($packet instanceof Request) {
            $this->handleRequest($packet);
        } elseif ($packet instanceof Notification) {
            $this->handleNotification($packet);
        } else {
            // Will not happen as long as there is no bug in Packet
            throw new RuntimeException('Packet was neither Request/Notification nor Response');
        }
    }

    protected function handleResponse(Response $response)
    {
        $id = $response->getId();
        if (isset($this->pending[$id])) {
            $promise = $this->pending[$id];
            unset($this->pending[$id]);
            $promise->resolve($response);
        } else {
            $this->handleUnmatchedResponse($response);
        }
    }

    protected function handleUnmatchedResponse(Response $response)
    {
        // Ignore. Log?
    }

    protected function handleRequest(Request $request)
    {
        $result = $this->handleNotification($request);
        $this->sendResultForRequest($request, $result);
    }

    protected function sendResultForRequest(Request $request, $result)
    {
        if ($result instanceof Error) {
            $response = Response::forRequest($request);
            $response->setError(new Error(Error::METHOD_NOT_FOUND));

            $this->connection->write($response->toString());
        } elseif ($result instanceof Promise) {
            $result->then(function ($result) use ($request) {
                $this->sendResultForRequest($request, $result);
            })->otherwise(function ($error) use ($request) {
                $response = Response::forRequest($request);
                if ($error instanceof Exception) {
                    $response->setError(Error::forException($error));
                } else {
                    $response->setError(new Error(Error::INTERNAL_ERROR, $error));
                }
                // TODO: Double-check, this used to loop
                // $this->connection->write($response->toString());
            });
        } else {
            $response = Response::forRequest($request);
            $response->setResult($result);
            $this->connection->write($response->toString());
        }
    }

    protected function handleNotification(Notification $notification)
    {
        $method = $notification->getMethod();
        if (\strpos($method, $this->nsSeparator) === false) {
            $namespace = null;
        } else {
            list($namespace, $method) = \preg_split($this->nsRegex, $method, 2);
        }

        try {
            return $this->call($namespace, $method, $notification);
        } catch (Exception $exception) {
            return Error::forException($exception);
        }
    }

    /**
     * @param Request $request
     * @return \React\Promise\Promise
     */
    public function sendRequest(Request $request)
    {
        $id = $request->getId();
        if (isset($this->pending[$id])) {
            throw new InvalidArgumentException(
                "A request with id '$id' is already pending"
            );
        }
        $this->connection->write($request->toString());
        $deferred = new Deferred();
        $this->pending[$id] = $deferred;

        return $deferred->promise();
    }

    /**
     * @param Request|mixed $request
     */
    public function forgetRequest($request)
    {
        if ($request instanceof Request) {
            unset($this->pending[$request->getId()]);
        } else {
            unset($this->pending[$request]);
        }
    }

    /**
     * @param Notification $packet
     */
    public function sendNotification(Notification $packet)
    {
        $this->connection->write($packet->toString());
    }

    /**
     * @param $namespace
     * @param $handler
     * @return Connection
     */
    public function setHandler($handler, $namespace = null)
    {
        $this->handlers[$namespace] = $handler;

        return $this;
    }

    protected function sendUnknownMethodError(Request $request)
    {
        $response = Response::forRequest($request);
        $response->setError(new Error(Error::METHOD_NOT_FOUND));

        $this->connection->write($response->toString());
    }

    protected function call($namespace, $method, Notification $packet)
    {
        if (isset($this->handlers[$namespace])) {
            $params = $packet->getParams();
            if (\is_object($params)) {
                return $this->handlers[$namespace]->$method($params);
            } else {
                return \call_user_func_array([$this->handlers[$namespace], $method], $params);
            }
        } else {
            return new Error(Error::METHOD_NOT_FOUND);
        }
    }
}
