<?php

namespace gipfl\SimpleDaemon;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use Exception;
use gipfl\SystemD\NotifySystemD;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use function React\Promise\resolve;
use function sprintf;

class Daemon implements LoggerAwareInterface, EventEmitterInterface
{
    use EventEmitterTrait;
    use LoggerAwareTrait;

    /** @var LoopInterface */
    private $loop;

    /** @var NotifySystemD|boolean */
    protected $systemd;

    /** @var DaemonTask[] */
    protected $daemonTasks = [];

    protected $tasksStarted = false;

    public function run(LoopInterface $loop)
    {
        $this->loop = $loop;
        $this->registerSignalHandlers();
        $this->systemd = NotifySystemD::ifRequired($loop);
        $loop->futureTick(function () {
            if ($this->systemd) {
                $this->systemd->setReady();
            }

            $this->startTasks();
        });
    }

    public function attachTask(DaemonTask $task)
    {
        if ($task instanceof LoggerAwareInterface) {
            $task->setLogger($this->logger ?: new NullLogger());
        }
        if ($this->systemd && $task instanceof SystemdAwareTask) {
            $task->setSystemd($this->systemd);
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
            $this->stopTasks();
        } catch (Exception $e) {
            $this->emit('error', [sprintf(
                'Failed to safely shutdown, stopping anyways: %s',
                $e->getMessage()
            )]);
        }
        $this->loop->stop();
    }
}
