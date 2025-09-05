<?php

namespace gipfl\Protocol\JsonRpc;

use Evenement\EventEmitterTrait;
use Exception;
use gipfl\Json\JsonEncodeException;
use gipfl\Protocol\JsonRpc\Handler\JsonRpcHandler;
use InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use React\EventLoop\Loop;
use React\Promise\Deferred;
use React\Promise\Promise;
use React\Stream\DuplexStreamInterface;
use React\Stream\Util;
use RuntimeException;
use Throwable;

use function mt_rand;
use function React\Promise\reject;
use function React\Promise\resolve;

class JsonRpcConnection implements LoggerAwareInterface
{
    use EventEmitterTrait;
    use LoggerAwareTrait;

    /** @var DuplexStreamInterface */
    protected $connection;

    /** @var ?JsonRpcHandler */
    protected $handler;

    /** @var Deferred[] */
    protected $pending = [];

    protected $unknownErrorCount = 0;

    public function __construct(DuplexStreamInterface $connection, ?JsonRpcHandler $handler = null)
    {
        $this->setLogger(new NullLogger());
        $this->connection = $connection;
        $this->setHandler($handler);
        $this->connection->on('data', function ($data) {
            try {
                $this->handlePacket(Packet::decode($data));
            } catch (Throwable $error) {
                $this->logger->error($error->getMessage());
                $this->unknownErrorCount++;
                if ($this->unknownErrorCount === 3) {
                    // e.g.: decoding errors
                    // TODO: should we really close? Or just send error responses for every Exception?
                    Loop::futureTick(function () {
                        $this->close();
                    });
                }
                $response = new Response();
                $response->setError(Error::forException($error));
                $this->connection->write($response->toString());
            }
        });
        $connection->on('close', function () {
            $this->rejectAllPendingRequests('Connection closed');
        });
        // Hint: Util::pipe takes care of the pipe event
        Util::forwardEvents($connection, $this, ['end', 'error', 'close', 'drain']);
    }

    /**
     * @param Packet $packet
     */
    protected function handlePacket(Packet $packet)
    {
        if ($packet instanceof Response) {
            $this->handleResponse($packet);
        } elseif ($packet instanceof Request) {
            if ($this->handler) {
                $result = $this->handler->processRequest($packet);
            } else {
                $result = new Error(Error::METHOD_NOT_FOUND);
                $result->setMessage($result->getMessage() . ': ' . $packet->getMethod());
            }
            $this->sendResultForRequest($packet, $result);
        } elseif ($packet instanceof Notification) {
            if ($this->handler) {
                $this->handler->processNotification($packet);
            }
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
        $this->logger->error('Unmatched Response: ' . $response->toString());
    }

    protected function sendResultForRequest(Request $request, $result)
    {
        if ($result instanceof Error) {
            $response = Response::forRequest($request);
            $response->setError($result);
            if ($this->connection && $this->connection->isWritable()) {
                $this->connection->write($response->toString());
            } else {
                $this->logger->error('Failed to send response, have no writable connection');
            }
        } elseif ($result instanceof Promise) {
            $result->then(function ($result) use ($request) {
                $this->sendResultForRequest($request, $result);
            }, function ($error) use ($request) {
                $response = Response::forRequest($request);
                if ($error instanceof Throwable) {
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
            if ($this->connection && $this->connection->isWritable()) {
                $this->connection->write($response->toString());
            } else {
                $this->logger->error('Failed to send response, have no writable connection');
            }
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
        if (!$this->connection || !$this->connection->isWritable()) {
            return reject(new Exception('Cannot write to JSON-RPC socket'));
        }
        try {
            $this->connection->write($request->toString());
        } catch (JsonEncodeException $e) {
            return reject($e);
        } catch (Throwable $e) {
            return reject(new RuntimeException($e->getMessage()));
        }
        $deferred = new Deferred(function () use ($id) {
            unset($this->pending[$id]);
        });
        $this->pending[$id] = $deferred;

        return $deferred->promise()->then(function (Response $response) {
            if ($response->isError()) {
                return reject(new RuntimeException($response->getError()->getMessage()));
            }

            return resolve($response->getResult());
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
     * @param PacketHandler $handler
     * @return $this
     */
    public function setHandler(?JsonRpcHandler $handler = null)
    {
        $this->handler = $handler;
        return $this;
    }

    /**
     * @return ?JsonRpcHandler
     */
    public function getHandler()
    {
        return $this->handler;
    }

    protected function rejectAllPendingRequests($message)
    {
        foreach ($this->pending as $pending) {
            $pending->reject(new Exception($message));
        }
        $this->pending = [];
    }

    public function close()
    {
        if ($this->connection) {
            $this->connection->close();
            $this->handler = null;
            $this->connection = null;
        }
    }
}
