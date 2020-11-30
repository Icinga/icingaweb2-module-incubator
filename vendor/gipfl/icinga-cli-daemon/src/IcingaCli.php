<?php

namespace gipfl\IcingaCliDaemon;

use Evenement\EventEmitterTrait;
use gipfl\Protocol\JsonRpc\Connection;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\Stream;

class IcingaCli
{
    use EventEmitterTrait;

    /** @var IcingaCliRunner */
    protected $runner;

    /** @var Connection|null */
    protected $rpc;

    protected $arguments = [];

    /** @var  \React\Stream\WritableStreamInterface|null */
    protected $stdin;

    /** @var Deferred|null */
    protected $deferredStdin;

    /** @var \React\Stream\ReadableStreamInterface|null */
    protected $stdout;

    /** @var Deferred|null */
    protected $deferredStdout;

    /** @var \React\Stream\ReadableStreamInterface|null */
    protected $stderr;

    /** @var Deferred|null */
    protected $deferredStderr;

    public function __construct(IcingaCliRunner $runner = null)
    {
        if ($runner === null) {
            $runner = new IcingaCliRunner();
        }
        $this->runner = $runner;
        $this->init();
    }

    protected function init()
    {
        // Override this if you want.
    }

    public function setArguments($arguments)
    {
        $this->arguments = $arguments;

        return $this;
    }

    public function getArguments()
    {
        return $this->arguments;
    }

    public function run(LoopInterface $loop)
    {
        $process = $this->runner->command($this->getArguments());
        $canceller = function () use ($process) {
            // TODO: first soft, then hard
            $process->terminate();
        };
        $deferred = new Deferred($canceller);
        $process->on('exit', function ($exitCode, $termSignal) use ($deferred) {
            $state = new FinishedProcessState($exitCode, $termSignal);
            if ($state->succeeded()) {
                $deferred->resolve();
            } else {
                $deferred->reject($state);
            }
        });

        $process->start($loop);
        if ($this->deferredStdin instanceof Deferred) {
            $this->deferredStdin->resolve($process->stdin);
        } else {
            $this->stdin = $process->stdin;
        }
        if ($this->deferredStdout instanceof Deferred) {
            $this->deferredStdout->resolve($process->stdout);
        } else {
            $this->stdout = $process->stdout;
        }
        if ($this->deferredStderr instanceof Deferred) {
            $this->deferredStderr->resolve($process->stderr);
        } else {
            $this->stderr = $process->stderr;
        }
        $this->emit('start', [$process]);

        return $deferred->promise();
    }

    /**
     * @return \React\Stream\WritableStreamInterface
     */
    public function stdin()
    {
        if ($this->stdin === null) {
            $this->deferredStdin = new Deferred();
            $this->stdin = Stream\unwrapWritable($this->deferredStdin->promise());
        }

        return $this->stdin;
    }

    /**
     * @return \React\Stream\ReadableStreamInterface
     */
    public function stdout()
    {
        if ($this->stdout === null) {
            $this->deferredStdout = new Deferred();
            $this->stdout = Stream\unwrapReadable($this->deferredStdout->promise());
        }

        return $this->stdout;
    }

    /**
     * @return \React\Stream\ReadableStreamInterface
     */
    public function stderr()
    {
        if ($this->stderr === null) {
            $this->deferredStderr = new Deferred();
            $this->stderr = Stream\unwrapReadable($this->deferredStderr->promise());
        }

        return $this->stderr;
    }
}
