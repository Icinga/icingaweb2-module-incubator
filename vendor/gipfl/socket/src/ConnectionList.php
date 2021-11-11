<?php

namespace gipfl\Socket;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use InvalidArgumentException;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Socket\ConnectionInterface;
use SplObjectStorage;
use function React\Promise\all;

/**
 * @method ConnectionInterface current
 */
class ConnectionList extends SplObjectStorage implements EventEmitterInterface
{
    use EventEmitterTrait;

    const ON_ATTACHED = 'attached';
    const ON_DETACHED = 'detached';

    /** @var LoopInterface */
    protected $loop;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    public function attach($object, $info = null)
    {
        if (! $object instanceof ConnectionInterface || $info !== null) {
            throw new InvalidArgumentException(sprintf(
                'Can attach only %s instances',
                ConnectionInterface::class
            ));
        }
        $object->on('close', function () use ($object) {
            $this->detach($object);
        });

        parent::attach($object, $info);
        $this->emit(self::ON_ATTACHED, [$object]);
    }

    public function detach($object)
    {
        parent::detach($object);
        $this->emit(self::ON_DETACHED, [$object]);
    }

    public function close()
    {
        $pending = [];
        foreach ($this as $connection) {
            $pending[] = $this->closeConnection($connection, $this->loop);
        }

        return all($pending);
    }

    public static function closeConnection(ConnectionInterface $connection, LoopInterface $loop, $timeout = 5)
    {
        $deferred = new Deferred();
        $connection->end();
        if ($connection->isWritable() || $connection->isReadable()) {
            $timer = $loop->addTimer($timeout, function () use ($connection, $deferred) {
                $connection->close();
            });

            $connection->on('close', function () use ($deferred, $timer) {
                $this->loop->cancelTimer($timer);
                $deferred->resolve();
            });
        } else {
            $loop->futureTick(function () use ($deferred) {
                $deferred->resolve();
            });
        }

        return $deferred->promise();
    }
}
