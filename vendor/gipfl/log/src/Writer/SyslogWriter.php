<?php

namespace gipfl\Log\Writer;

use gipfl\Log\Logger;
use gipfl\Log\LogWriter;
use function openlog;
use function syslog;

class SyslogWriter implements LogWriter
{
    /** @var string */
    protected $ident;

    /** @var string */
    protected $facility;

    /**
     * SyslogWriter constructor.
     * @param string $ident
     * @param string $facility
     */
    public function __construct($ident, $facility)
    {
        $this->ident = $ident;
        $this->facility = $facility;
    }

    public function write($level, $message)
    {
        openlog($this->ident, LOG_PID, $this->facility);
        syslog(Logger::mapLogLevel($level), str_replace("\n", '    ', $message));
    }
}
