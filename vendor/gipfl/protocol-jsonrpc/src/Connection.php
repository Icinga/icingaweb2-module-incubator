<?php

namespace gipfl\Protocol\JsonRpc;

use Evenement\EventEmitterTrait;
use Exception;
use InvalidArgumentException;
use React\Promise\Deferred;
use React\Promise\Promise;
use React\Stream\DuplexStreamInterface;
use RuntimeException;

class Connection
{
    use EventEmitterTrait;

    /** @var DuplexStreamInterface */
    protected $connection;

    /** @var array */
    protected $handlers = [];

    /** @var array */
    protected $pending = [];

    protected $nsSeparator = '.';

    protected $nsRegex = '/\./';

    protected $unknownErrorCount = 0;

    public function handle(DuplexStreamInterface $connection)
    {
        $this->connection = $connection;
        $this->connection->on('data', function ($data) {
            try {
                $this->handlePacket(Packet::decode($data));
            } catch (Exception $error) {
                echo $error->getMessage() . "\n";
                $this->unknownErrorCount++;
                if ($this->unknownErrorCount === 3) {
                    $this->close();
                }
                $response = new Response();
                $response->setError(Error::forException($error));
                $this->connection->write($response->toString());
            }
        });
        $this->connection->on('end', function () {
            $this->emit('end');
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
            $response->setError($result);

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

    /**
     * @param Notification $notification
     * @return Error|mixed
     */
    protected function handleNotification(Notification $notification)
    {
        $method = $notification->getMethod();
        if (\strpos($method, $this->nsSeparator) === false) {
            $namespace = null;
        } else {
            list($namespace, $method) = \preg_split($this->nsRegex, $method, 2);
        }

        try {
            $response = $this->call($namespace, $method, $notification);

            return $response;
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
        if ($id === null) {
            $id = $this->getRandomId();
            $request->setId($id);
        }
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

    public function request($method, $params = null)
    {
        $request = new Request($method, $this->getRandomId(), $params);
        $deferred = new Deferred();
        $this->sendRequest($request)->then(function (Response $response) use ($deferred) {
            if ($response->isError()) {
                $deferred->reject(new RuntimeException($response->getError()->getMessage()));
            } else {
                $deferred->resolve($response->getResult());
            }
        });

        return $deferred->promise();
    }

    protected function getRandomId()
    {
        $id = rand(1, 1000000000);
        if (isset($this->pending[$id])) {
            $id = $this->getRandomId();
        }

        return $id;
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

    protected function call($namespace, $method, Notification $packet)
    {
        if ($handler = $this->handlers[$namespace]) {
            if ($handler instanceof PacketHandler) {
                return $handler->handle($packet);
            }

            // Legacy handlers, deprecated:
            $params = $packet->getParams();
            if (\is_object($params)) {
                return $this->handlers[$namespace]->$method($params);
            } else {
                return \call_user_func_array([$this->handlers[$namespace], $method], $params);
            }
        } else {
            $error = new Error(Error::METHOD_NOT_FOUND);
            $error->setMessage(sprintf(
                '%s: %s%s%s',
                $error->getMessage(),
                $namespace,
                $this->nsSeparator,
                $method
            ));

            return $error;
        }
    }

    public function close()
    {
        $this->connection->close();
    }
}
