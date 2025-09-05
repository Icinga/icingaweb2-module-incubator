<?php

namespace gipfl\Log\PsrLogV1;

use gipfl\Log\Logger;
use gipfl\Log\PsrLogCommon\PrefixLoggerCommonTrait;

class PrefixLoggerV1 extends Logger
{
    use PrefixLoggerCommonTrait;

    public function log($level, $message, array $context = [])
    {
        $this->wrappedLogger->log($level, $this->prefix . $message, $context);
    }
}
