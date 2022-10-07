<?php

namespace gipfl\Log;

use Psr\Log\LoggerInterface;

class AdditionalContextLogger extends Logger
{
    /** @var array */
    protected $context;

    /** @var LoggerInterface */
    protected $wrappedLogger;

    public function __construct(array $context, LoggerInterface $logger)
    {
        $this->context = $context;
        $this->wrappedLogger = $logger;
    }

    public function log($level, $message, array $context = [])
    {
        $this->wrappedLogger->log($level, $message, $context + $this->context);
    }
}
