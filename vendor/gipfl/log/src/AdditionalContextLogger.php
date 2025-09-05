<?php

namespace gipfl\Log;

use gipfl\Log\PsrLogV1\AdditionalContextLoggerV1;
use gipfl\Log\PsrLogV3\AdditionalContextLoggerV3;

// @codingStandardsIgnoreStart
if (PsrVersion::isV3()) {
    class AdditionalContextLogger extends AdditionalContextLoggerV3
    {
    }
} else {
    class AdditionalContextLogger extends AdditionalContextLoggerV1
    {
    }
}
