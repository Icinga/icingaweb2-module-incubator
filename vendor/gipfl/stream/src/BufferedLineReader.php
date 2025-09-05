<?php

namespace gipfl\Stream;

use Evenement\EventEmitterTrait;
use React\EventLoop\LoopInterface;
use React\Stream\WritableStreamInterface;
use function strlen;
use function strpos;
use function substr;

class BufferedLineReader implements WritableStreamInterface
{
    use EventEmitterTrait;

    /** @var LoopInterface */
    protected $loop;

    protected $buffer = '';

    protected $writable = true;

    /** @var string */
    protected $separator;

    /** @var int */
    protected $separatorLength;

    protected $process;

    // protected $maxBufferSize; // Not yet. Do we need this?

    /**
     * @param string $separator
     * @param LoopInterface $loop
     */
    public function __construct($separator, LoopInterface $loop)
    {
        $this->loop = $loop;
        $this->separator = $separator;
        $this->separatorLength = strlen($separator);
        $this->process = function () {
            $this->processBuffer();
        };
    }

    protected function processBuffer()
    {
        $lastPos = 0;
        while (false !== ($pos = strpos($this->buffer, $this->separator, $lastPos))) {
            $this->emit('line', [substr($this->buffer, $lastPos, $pos - $lastPos)]);
            $lastPos = $pos + $this->separatorLength;
        }
        if ($lastPos !== 0) {
            $this->buffer = substr($this->buffer, $lastPos);
        }
    }

    public function isWritable()
    {
        return $this->writable;
    }

    public function write($data)
    {
        if (! $this->writable) {
            return false;
        }
        $this->buffer .= $data;
        if (strpos($data, $this->separator) !== false) {
            $this->loop->futureTick($this->process);
        }

        return true;
    }

    public function end($data = null)
    {
        if ($data !== null) {
            $this->buffer .= $data;
        }
        $this->close();
    }

    public function close()
    {
        $this->writable = false;
        $this->processBuffer();
        $remainingBuffer = $this->buffer;
        $this->buffer = '';
        if ($length = strlen($remainingBuffer)) {
            $this->emit('error', [new \Exception(sprintf(
                'There are %d unprocessed bytes in our buffer: %s',
                $length,
                substr($remainingBuffer, 0, 64)
            ))]);
        }
        $this->emit('close');
    }
}
