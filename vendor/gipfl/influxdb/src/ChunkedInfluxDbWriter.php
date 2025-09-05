<?php

namespace gipfl\InfluxDb;

use gipfl\Curl\RequestError;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;

/**
 * Gives no result, enqueue and forget
 */
class ChunkedInfluxDbWriter implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    const DEFAULT_BUFFER_SIZE = 5000;

    const DEFAULT_FLUSH_INTERVAL = 0.2;

    const DEFAULT_PRECISION = 's';

    /** @var int */
    protected $bufferSize = self::DEFAULT_BUFFER_SIZE;

    /** @var float */
    protected $flushInterval = self::DEFAULT_FLUSH_INTERVAL;

    /** @var string */
    protected $precision = self::DEFAULT_PRECISION;

    /** @var DataPoint[] */
    protected $buffer = [];

    /** @var InfluxDbConnection */
    protected $connection;

    /** @var string */
    protected $dbName;

    /** @var LoopInterface */
    protected $loop;

    /** @var ?TimerInterface */
    protected $flushTimer;

    public function __construct(InfluxDbConnection $connection, $dbName, LoopInterface $loop)
    {
        $this->setLogger(new NullLogger());
        $this->connection = $connection;
        $this->dbName = $dbName;
        $this->loop = $loop;
    }

    /**
     * @param DataPoint $point
     */
    public function enqueue(DataPoint $point)
    {
        $this->buffer[] = $point;
        $count = count($this->buffer);
        if ($count >= $this->bufferSize) {
            $this->flush();
        } else {
            $this->startFlushTimer();
        }
    }

    /**
     * @param int $bufferSize
     * @return ChunkedInfluxDbWriter
     */
    public function setBufferSize($bufferSize)
    {
        $this->bufferSize = $bufferSize;
        return $this;
    }

    /**
     * @param float $flushInterval
     * @return ChunkedInfluxDbWriter
     */
    public function setFlushInterval($flushInterval)
    {
        $this->flushInterval = $flushInterval;
        return $this;
    }

    /**
     * @param string $precision ns,u,ms,s,m,h
     * @return ChunkedInfluxDbWriter
     */
    public function setPrecision($precision)
    {
        $this->precision = $precision;
        return $this;
    }

    public function flush()
    {
        $buffer = $this->buffer;
        $this->buffer = [];
        $this->stopFlushTimer();
        $this->logger->debug(sprintf('Flushing InfluxDB buffer, sending %d data points', count($buffer)));
        $start = microtime(true);
        $this->connection->writeDataPoints($this->dbName, $buffer, $this->precision)
            ->then(function (ResponseInterface $response) use ($start) {
                $code = $response->getStatusCode();
                $duration = (microtime(true) - $start) * 1000;
                if ($code > 199 && $code < 300) {
                    $this->logger->debug(sprintf('Got response from InfluxDB after %.2Fms', $duration));
                } else {
                    $this->logger->error(sprintf(
                        'Got unexpected %d from InfluxDB after %.2Fms: %s',
                        $code,
                        $duration,
                        $response->getReasonPhrase()
                    ));
                }
            }, function (RequestError $e) {
                $this->logger->error($e->getMessage());
            })->done();
    }

    public function stop()
    {
        $this->flush();
    }

    protected function startFlushTimer()
    {
        if ($this->flushTimer === null) {
            $this->flushTimer = $this->loop->addPeriodicTimer($this->flushInterval, function () {
                if (! empty($this->buffer)) {
                    $this->flush();
                }
            });
        }
    }

    protected function stopFlushTimer()
    {
        if ($this->flushTimer) {
            $this->loop->cancelTimer($this->flushTimer);
            $this->flushTimer = null;
        }
    }

    public function __destruct()
    {
        $this->stopFlushTimer();
        $this->loop = null;
        $this->connection = null;
    }
}
