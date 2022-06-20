<?php

namespace gipfl\Process;

use gipfl\Json\JsonSerialization;
use gipfl\LinuxHealth\Memory;
use React\ChildProcess\Process;

class ProcessInfo implements JsonSerialization
{
    /** @var ?int */
    protected $pid;

    /** @var string */
    protected $command;

    /** @var bool */
    protected $running;

    /** @var ?object */
    protected $memory;

    public static function forProcess(Process $process)
    {
        $self = new static();
        $self->pid = $process->getPid();
        $self->command = $process->getCommand();
        $self->running = $process->isRunning();
        if ($memory = Memory::getUsageForPid($self->pid)) {
            $self->memory = $memory;
        }

        return $self;
    }

    public static function fromSerialization($any)
    {
        $self = new static();
        $self->pid = $any->pid;
        $self->command = $any->command;
        $self->running = $any->running;
        $self->memory = $any->memory;

        return $self;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'pid'     => $this->pid,
            'command' => $this->command,
            'running' => $this->running,
            'memory'  => $this->memory,
        ];
    }
}
