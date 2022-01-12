<?php

namespace gipfl\Log;

use Psr\Log\LoggerInterface;

class PrefixLogger extends Logger
{
    /** @var string */
    protected $prefix;

    /** @var LoggerInterface */
    protected $wrappedLogger;

    public function __construct($prefix, LoggerInterface $logger)
    {
        $this->prefix = $prefix;
        $this->wrappedLogger = $logger;
    }

    public function log($level, $message, array $context = [])
    {
        $this->wrappedLogger->log($level, $this->prefix . $message, $context);
    }
}
