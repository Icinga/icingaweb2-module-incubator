<?php

namespace gipfl\Log\PsrLogV1;

use gipfl\Log\Logger;
use gipfl\Log\PsrLogCommon\AdditionalContextLoggerCommonTrait;

class AdditionalContextLoggerV1 extends Logger
{
    use AdditionalContextLoggerCommonTrait;

    public function log($level, $message, array $context = [])
    {
        $this->wrappedLogger->log($level, $message, $context + $this->context);
    }
}
