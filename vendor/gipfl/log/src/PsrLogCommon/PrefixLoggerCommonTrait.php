<?php

namespace gipfl\Log\PsrLogCommon;

use Psr\Log\LoggerInterface;

trait PrefixLoggerCommonTrait
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
}
