<?php

namespace gipfl\Log\Writer;

use gipfl\Log\LogLevel;
use gipfl\Log\LogWriter;
use React\EventLoop\LoopInterface;
use React\Stream\WritableResourceStream;
use React\Stream\WritableStreamInterface;
use function sprintf;

class SystemdStdoutWriter implements LogWriter
{
    /** @var WritableStreamInterface */
    protected $stdOut;

    /**
     * SystemdStdoutWriter constructor.
     * @param LoopInterface $loop
     * @param WritableStreamInterface|null $stdOut
     */
    public function __construct(LoopInterface $loop, WritableStreamInterface $stdOut = null)
    {
        if ($stdOut === null) {
            $this->stdOut = new WritableResourceStream(STDOUT, $loop);
        } else {
            $this->stdOut = $stdOut;
        }
    }

    public function write($level, $message)
    {
        $this->stdOut->write(sprintf(
            "<%d> %s\n",
            LogLevel::mapNameToNumeric($level),
            $message
        ));
    }
}
