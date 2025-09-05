<?php

namespace gipfl\Log\PsrLogV3;

use gipfl\Log\Logger;
use gipfl\Log\PsrLogCommon\AdditionalContextLoggerCommonTrait;

class AdditionalContextLoggerV3 extends Logger
{
    use AdditionalContextLoggerCommonTrait;

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->wrappedLogger->log($level, $message, $context + $this->context);
    }
}
