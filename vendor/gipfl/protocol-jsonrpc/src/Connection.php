<?php

namespace gipfl\Protocol\JsonRpc;

use Evenement\EventEmitterTrait;
use Exception;
use gipfl\Json\JsonEncodeException;
use InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use React\Promise\Deferred;
use React\Promise\Promise;
use React\Stream\DuplexStreamInterface;
use React\Stream\Util;
use RuntimeException;
use function call_user_func_array;
use function is_object;
use function mt_rand;
use function preg_quote;
use function preg_split;
use function React\Promise\reject;
use function sprintf;

/**
 * @deprecated Please use JsonRpcConection
 */
class Connection implements LoggerAwareInterface
{
    use EventEmitterTrait;
    use LoggerAwareTrait;

    /** @var DuplexStreamInterface */
    protected $connection;

    /** @var array */
    protected $handlers = [];

    /** @var Deferred[] */
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
        $connection->on('close', function () {
            $this->rejectAllPendingRequests('Connection closed');
        });
        // TODO: figure out whether and how to deal with the pipe event
        Util::forwardEvents($connection, $this, ['end', 'error', 'close', 'drain']);
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
                $this->connection->write($response->toString());
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
            list($namespace, $method) = preg_split($this->nsRegex, $method, 2);
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
     * @return \React\Promise\PromiseInterface
     */
    public function sendRequest(Request $request)
    {
        $id = $request->getId();
        if ($id === null) {
            $id = $this->getNextRequestId();
            $request->setId($id);
        }
        if (isset($this->pending[$id])) {
            throw new InvalidArgumentException(
                "A request with id '$id' is already pending"
            );
        }
        if (!$this->connection->isWritable()) {
            return reject(new Exception('Cannot write to socket'));
        }
        try {
            $this->connection->write($request->toString());
        } catch (JsonEncodeException $e) {
            return reject($e->getMessage());
        }
        $deferred = new Deferred();
        $this->pending[$id] = $deferred;

        return $deferred->promise()->then(function (Response $response) use ($deferred) {
            if ($response->isError()) {
                $deferred->reject(new RuntimeException($response->getError()->getMessage()));
            } else {
                $deferred->resolve($response->getResult());
            }
        }, function (Exception $e) use ($deferred) {
            $deferred->reject($e);
        });
    }

    public function request($method, $params = null)
    {
        return $this->sendRequest(new Request($method, $this->getNextRequestId(), $params));
    }

    protected function getNextRequestId()
    {
        for ($i = 0; $i < 100; $i++) {
            $id = mt_rand(1, 1000000000);
            if (!isset($this->pending[$id])) {
                return $id;
            }
        }

        throw new RuntimeException('Unable to generate a free random request ID, gave up after 100 attempts');
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
     * @param string $method
     * @param null $params
     */
    public function notification($method, $params = null)
    {
        $notification = new Notification($method, $params);
        $this->sendNotification($notification);
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
        if (isset($this->handlers[$namespace])) {
            $handler = $this->handlers[$namespace];
            if ($handler instanceof PacketHandler) {
                return $handler->handle($packet);
            }

            // Legacy handlers, deprecated:
            $params = $packet->getParams();
            if (is_object($params)) {
                return $handler->$method($params);
            }

            return call_user_func_array([$handler, $method], $params);
        }

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

    protected function rejectAllPendingRequests($message)
    {
        foreach ($this->pending as $pending) {
            $pending->reject(new Exception());
        }
        $this->pending = [];
    }

    public function close()
    {
        if ($this->connection) {
            $this->connection->close();
            $this->handlers = [];
            $this->connection = null;
        }
    }
}
