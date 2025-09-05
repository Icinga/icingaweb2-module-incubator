<?php

namespace gipfl\Process;

use React\ChildProcess\Process as ChildProcess;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use function React\Promise\resolve;

class ProcessKiller
{
    /**
     * @param ChildProcess $process
     * @param LoopInterface $loop
     * @param int $timeout
     * @return \React\Promise\PromiseInterface
     */
    public static function terminateProcess(ChildProcess $process, LoopInterface $loop, $timeout = 0)
    {
        $processes = new ProcessList();
        $processes->attach($process);
        return static::terminateProcesses($processes, $loop, $timeout);
    }

    /**
     * @param ProcessList $processes
     * @param LoopInterface $loop
     * @param int $timeout
     * @return \React\Promise\PromiseInterface
     */
    public static function terminateProcesses(ProcessList $processes, LoopInterface $loop, $timeout = 5)
    {
        if ($processes->count() === 0) {
            return resolve(null);
        }
        $deferred = new Deferred();
        $killTimer = $loop->addTimer($timeout, function () use ($deferred, $processes, $loop) {
            /** @var ChildProcess $process */
            foreach ($processes as $process) {
                $pid = $process->getPid();
                // Logger::error("Process $pid is still running, sending SIGKILL");
                $process->terminate(SIGKILL);
            }

            // Let's add a bit of a delay after KILLing
            $loop->addTimer(0.1, function () use ($deferred) {
                $deferred->resolve(null);
            });
        });

        $timer = $loop->addPeriodicTimer($timeout / 20, function () use (
            $deferred,
            $processes,
            $loop,
            &$timer,
            $killTimer
        ) {
            $stopped = [];
            /** @var ChildProcess $process */
            foreach ($processes as $process) {
                if (! $process->isRunning()) {
                    $stopped[] = $process;
                }
            }
            foreach ($stopped as $process) {
                $processes->detach($process);
            }
            if ($processes->count() === 0) {
                $loop->cancelTimer($timer);
                $loop->cancelTimer($killTimer);
                $deferred->resolve(null);
            }
        });
        /** @var ChildProcess $process */
        foreach ($processes as $process) {
            $process->terminate(SIGTERM);
        }

        return $deferred->promise();
    }
}
