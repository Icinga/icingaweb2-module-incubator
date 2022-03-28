<?php

namespace gipfl\SimpleDaemon;

use React\EventLoop\LoopInterface;
use React\Promise\ExtendedPromiseInterface;

interface DaemonTask
{
    /**
     * @param LoopInterface $loop
     * @return ExtendedPromiseInterface
     */
    public function start(LoopInterface $loop);

    /**
     * @return ExtendedPromiseInterface
     */
    public function stop();
}
