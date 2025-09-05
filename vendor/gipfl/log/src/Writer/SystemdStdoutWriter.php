<?php

namespace gipfl\Log\Writer;

use gipfl\Log\LogLevel;
use gipfl\Log\LogWriter;
use InvalidArgumentException;
use React\EventLoop\LoopInterface;
use React\Stream\WritableResourceStream;
use React\Stream\WritableStreamInterface;
use function is_int;
use function sprintf;

class SystemdStdoutWriter implements LogWriter
{
    // local0
    const DEFAULT_FACILITY = 10;

    /** @var WritableStreamInterface */
    protected $stdOut;

    /** @var int */
    protected $facility = self::DEFAULT_FACILITY;

    /**
     * SystemdStdoutWriter constructor.
     * @param LoopInterface $loop
     * @param WritableStreamInterface|null $stdOut
     */
    public function __construct(LoopInterface $loop, ?WritableStreamInterface $stdOut = null)
    {
        if ($stdOut === null) {
            $this->stdOut = new WritableResourceStream(STDOUT, $loop);
        } else {
            $this->stdOut = $stdOut;
        }
    }

    /**
     * @param int $facility
     */
    public function setFacility($facility)
    {
        if (! is_int($facility)) {
            throw new InvalidArgumentException('Facility needs to be an integer');
        }
        if ($facility < 0 || $facility > 23) {
            throw new InvalidArgumentException("Facility needs to be between 0 and 23, got $facility");
        }
        $this->facility = $facility;
    }

    public function write($level, $message)
    {
        $this->stdOut->write(sprintf(
            "<%d>%s\n",
            LogLevel::mapNameToNumeric($level),
            $message
        ));
    }
}
