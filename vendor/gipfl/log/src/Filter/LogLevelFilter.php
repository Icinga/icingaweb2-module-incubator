<?php

namespace gipfl\Log\Filter;

use gipfl\Log\LogFilter;
use gipfl\Log\Logger;

class LogLevelFilter implements LogFilter
{
    protected $level;

    public function __construct($level)
    {
        $this->level = Logger::mapLogLevel($level);
    }

    public function wants($level, $message, $context = [])
    {
        return Logger::mapLogLevel($level) <= $this->level;
    }
}
