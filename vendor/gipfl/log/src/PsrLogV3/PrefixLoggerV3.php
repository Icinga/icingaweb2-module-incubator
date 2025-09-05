<?php

namespace gipfl\Log\PsrLogV3;

use gipfl\Log\Logger;
use gipfl\Log\PsrLogCommon\PrefixLoggerCommonTrait;

class PrefixLoggerV3 extends Logger
{
    use PrefixLoggerCommonTrait;

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->wrappedLogger->log($level, $this->prefix . $message, $context);
    }
}
