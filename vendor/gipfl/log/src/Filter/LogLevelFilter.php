<?php

namespace gipfl\Log\Filter;

use gipfl\Log\LogFilter;
use gipfl\Log\LogLevel;

class LogLevelFilter implements LogFilter
{
    /** @var int */
    protected $level;

    /**
     * @param string $level
     */
    public function __construct($level)
    {
        $this->level = LogLevel::mapNameToNumeric($level);
    }

    /**
     * @param string $level
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function wants($level, $message, $context = [])
    {
        return LogLevel::mapNameToNumeric($level) <= $this->level;
    }

    /**
     * @return string
     */
    public function getLevel()
    {
        return LogLevel::mapNumericToName($this->level);
    }
}
