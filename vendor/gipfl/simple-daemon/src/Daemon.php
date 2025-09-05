<?php

namespace gipfl\SimpleDaemon;

use Exception;
use gipfl\Cli\Process;
use gipfl\SystemD\NotifySystemD;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use function React\Promise\resolve;
use function React\Promise\Timer\timeout;
use function sprintf;

class Daemon implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var LoopInterface */
    private $loop;

    /** @var NotifySystemD|boolean */
    protected $systemd;

    /** @var DaemonTask[] */
    protected $daemonTasks = [];

    /** @var bool */
    protected $tasksStarted = false;

    /** @var bool */
    protected $reloading = false;

    public function run(LoopInterface $loop)
    {
        if ($this->logger === null) {
            $this->setLogger(new NullLogger());
        }
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

        $this->daemonTasks[] = $task;
        if ($this->tasksStarted) {
            $this->startTask($task);
        }
    }

    protected function startTasks()
    {
        $this->tasksStarted = true;
        foreach ($this->daemonTasks as $task) {
            $this->startTask($task);
        }
    }

    protected function startTask(DaemonTask $task)
    {
        if ($this->systemd && $task instanceof SystemdAwareTask) {
            $task->setSystemd($this->systemd);
        }

        $task->start($this->loop);
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
        $funcReload = function () {
            $this->reload();
        };
        $this->loop->addSignal(SIGHUP, $funcReload);
        $this->loop->addSignal(SIGINT, $func);
        $this->loop->addSignal(SIGTERM, $func);
    }

    protected function shutdownWithSignal($signal, &$func)
    {
        $this->loop->removeSignal($signal, $func);
        $this->shutdown();
    }

    public function reload()
    {
        if ($this->reloading) {
            $this->logger->error('Ignoring reload request, reload is already in progress');
            return;
        }
        $this->reloading = true;
        $this->logger->notice('Stopping tasks, going gown for reload now');
        if ($this->systemd) {
            $this->systemd->setReloading('Reloading the main process');
        }
        $this->stopTasks()->then(function () {
            $this->logger->notice('Everything stopped, restarting');
            Process::restart();
        });
    }

    public function shutdown()
    {
        timeout($this->stopTasks(), 5, $this->loop)->then(function () {
            $this->loop->stop();
        }, function (Exception $e) {
            if ($this->logger) {
                $this->logger->error(sprintf(
                    'Failed to safely shutdown, stopping anyways: %s',
                    $e->getMessage()
                ));
            }
            $this->loop->addTimer(0.1, function () {
                $this->loop->stop();
            });
        });
    }
}
