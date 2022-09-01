<?php

namespace gipfl\ReactUtils;

use Exception;
use gipfl\Log\DummyLogger;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use RuntimeException;

class RetryUnless implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var LoopInterface */
    protected $loop;

    /** @var Deferred */
    protected $deferred;

    /** @var TimerInterface */
    protected $timer;

    /** @var callable */
    protected $callback;

    /** @var bool */
    protected $expectsSuccess;

    /** @var int Regular interval */
    protected $interval = 1;

    /** @var int|null Optional, interval will be changed after $burst attempts */
    protected $burst = null;

    /** @var int|null Interval after $burst attempts */
    protected $finalInterval = null;

    /** @var int Current attempt count */
    protected $attempts = 0;

    /** @var bool No attempts will be made while paused */
    protected $paused = false;

    protected $lastError;

    protected function __construct($callback, $expectsSuccess = true)
    {
        $this->setLogger(new DummyLogger());
        $this->callback = $callback;
        $this->expectsSuccess = $expectsSuccess;
    }

    public static function succeeding($callback)
    {
        return new static($callback);
    }

    public static function failing($callback)
    {
        return new static($callback, false);
    }

    public function run(LoopInterface $loop)
    {
        $this->assertNotRunning();
        $this->deferred = $deferred = new Deferred();
        $this->loop = $loop;
        $loop->futureTick(function () {
            $this->nextAttempt();
        });

        return $deferred->promise();
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    public function setInterval($interval)
    {
        $this->interval = $interval;

        return $this;
    }

    public function slowDownAfter($burst, $interval)
    {
        $this->burst = $burst;
        $this->finalInterval = $interval;

        return $this;
    }

    public function pause()
    {
        $this->removeEventualTimer();
        $this->paused = true;

        return $this;
    }

    public function resume()
    {
        if ($this->paused) {
            $this->paused = false;
            if ($this->timer === null) {
                $this->nextAttempt();
            }
        }
    }

    public function reset()
    {
        $this->attempts = 0;
        $this->paused = false;
        $this->removeEventualTimer();
        $this->rejectEventualDeferred('RetryUnless has been reset');

        return $this;
    }

    public function getAttempts()
    {
        return $this->attempts;
    }

    protected function nextAttempt()
    {
        if ($this->paused) {
            return;
        }

        $this->removeEventualTimer();
        $this->attempts++;
        try {
            $callback = $this->callback;
            $this->handleResult($callback());
        } catch (Exception $e) {
            $this->handleResult($e);
        }
    }

    protected function logError(Exception $e)
    {
        if ($this->lastError !== $e->getMessage()) {
            $this->lastError = $e->getMessage();
            // TODO: Support exceptions in our logger?
            $this->logger->error($e->getMessage());
        }
    }

    protected function handleResult($result)
    {
        if ($this->expectsSuccess) {
            if ($result instanceof Exception) {
                $this->logError($result);
                $this->scheduleNextAttempt();
            } elseif ($result instanceof PromiseInterface) {
                $later = function ($result) {
                    $this->handleResult($result);
                };
                $result->then($later, $later);
            } else {
                $this->succeed($result);
            }
        } else {
            if ($result instanceof Exception) {
                $this->succeed($result);
            } else {
                $this->scheduleNextAttempt();
            }
        }
    }

    protected function scheduleNextAttempt()
    {
        if ($this->timer !== null) {
            throw new RuntimeException(
                'RetryUnless schedules next attempt while already scheduled'
            );
        }
        $this->timer = $this->loop->addTimer($this->getNextInterval(), function () {
            $this->nextAttempt();
        });
    }

    protected function succeed($result)
    {
        $this->removeEventualTimer();
        if ($this->deferred === null) {
            $this->logger->warning('RetryUnless tries to resolve twice');

            return;
        }
        $this->deferred->resolve($result);
        $this->deferred = null;
        $this->reset();
    }

    protected function getNextInterval()
    {
        if ($this->burst === null) {
            return $this->interval;
        }

        return $this->attempts >= $this->burst
            ? $this->finalInterval
            : $this->interval;
    }

    protected function assertNotRunning()
    {
        if ($this->deferred) {
            throw new RuntimeException(
                'Cannot re-run RetryUnless while already running'
            );
        }
    }

    protected function removeEventualTimer()
    {
        if ($this->timer) {
            $this->loop->cancelTimer($this->timer);
            $this->timer = null;
        }
    }

    protected function rejectEventualDeferred($reason)
    {
        if ($this->deferred !== null) {
            $deferred = $this->deferred;
            $this->deferred = null;
            $deferred->reject($reason);
        }
    }

    public function cancel($reason = null)
    {
        $this->removeEventualTimer();
        $this->rejectEventualDeferred($reason ?: 'cancelled');
    }

    public function __destruct()
    {
        $this->removeEventualTimer();
        $this->rejectEventualDeferred('RetryUnless has been destructed');

        $this->loop = null;
    }
}
