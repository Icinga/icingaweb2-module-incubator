<?php

namespace gipfl\Log\PsrLogCommon;

use Psr\Log\LoggerInterface;

trait AdditionalContextLoggerCommonTrait
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
}
