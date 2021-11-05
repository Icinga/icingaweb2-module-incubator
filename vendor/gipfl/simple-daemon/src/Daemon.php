<?php

namespace gipfl\SimpleDaemon;

use Exception;
use gipfl\SystemD\NotifySystemD;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use function React\Promise\resolve;
use function sprintf;

class Daemon
{
    /** @var LoopInterface */
    private $loop;

    /** @var NotifySystemD|boolean */
    protected $systemd;

    /** @var LoggerInterface */
    protected $logger;

    /** @var DaemonTask[] */
    protected $daemonTasks = [];

    protected $tasksStarted = false;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function run(LoopInterface $loop)
    {
        $this->loop = $loop;
        $this->registerSignalHandlers();
        $this->systemd = NotifySystemD::ifRequired($loop);
        $loop->futureTick(function () {
            $this->startTasks();
        });
    }

    public function attachTask(DaemonTask $task)
    {
        if ($task instanceof LoggerAwareInterface) {
            $task->setLogger($this->logger);
        }

        $this->daemonTasks[] = $task;
        if ($this->tasksStarted) {
            $task->start($this->loop);
        }
    }

    protected function startTasks()
    {
        $this->tasksStarted = true;
        foreach ($this->daemonTasks as $task) {
            $task->start($this->loop);
        }
    }

    protected function stopTasks()
    {
        if (empty($this->daemonTasks)) {
            return resolve();
        }

        $deferred = new Deferred();
        foreach ($this->daemonTasks as $id => $task) {
            $task->stop()->always(function () use ($id, $deferred) {
                unset($this->daemonTasks[$id]);
                if (empty($this->daemonTasks)) {
                    $this->tasksStarted = false;
                    $this->loop->futureTick(function () use ($deferred) {
                        $deferred->resolve();
                    });
                }
            });
        }

        return $deferred->promise();
    }

    protected function setDaemonStatus($status, $logLevel = null, $sendReady = false)
    {
        if ($this->logger && $logLevel !== null) {
            $this->logger->$logLevel($status);
        }
        if ($this->systemd) {
            if ($sendReady) {
                $this->systemd->setReady($status);
            } else {
                $this->systemd->setStatus($status);
            }
        }
    }

    protected function registerSignalHandlers()
    {
        $func = function ($signal) use (&$func) {
            $this->shutdownWithSignal($signal, $func);
        };
        $this->loop->addSignal(SIGINT, $func);
        $this->loop->addSignal(SIGTERM, $func);
    }

    protected function shutdownWithSignal($signal, &$func)
    {
        $this->loop->removeSignal($signal, $func);
        $this->shutdown();
    }

    protected function shutdown()
    {
        try {
            $this->setDaemonStatus('Shutting down', 'notice');
            $this->stopTasks();
        } catch (Exception $e) {
            if ($this->systemd) {
                $this->systemd->setError(sprintf(
                    'Failed to safely shutdown, stopping anyways: %s',
                    $e->getMessage()
                ));
            }
            $this->logger->error(sprintf(
                'Failed to safely shutdown, stopping anyways: %s',
                $e->getMessage()
            ));
        }
        $this->loop->stop();
    }
}
