<?php

namespace gipfl\Log\Writer;

use gipfl\Log\LogWriter;
use React\Stream\WritableStreamInterface;

class WritableStreamWriter implements LogWriter
{
    const DEFAULT_SEPARATOR = PHP_EOL;

    /** @var WritableStreamInterface */
    protected $stream;

    /** @var string */
    protected $separator = self::DEFAULT_SEPARATOR;

    /**
     * WritableStreamWriter constructor.
     * @param WritableStreamInterface $stream
     */
    public function __construct(WritableStreamInterface $stream)
    {
        $this->setStream($stream);
    }

    /**
     * @param string $separator
     */
    public function setSeparator($separator)
    {
        $this->separator = $separator;
    }

    public function setStream(WritableStreamInterface $stream)
    {
        $this->stream = $stream;
    }

    public function write($level, $message)
    {
        $this->stream->write("$level: $message" . $this->separator);
    }
}
