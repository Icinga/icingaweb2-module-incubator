<?php

namespace gipfl\Log;

use gipfl\Log\PsrLogV1\PrefixLoggerV1;
use gipfl\Log\PsrLogV3\PrefixLoggerV3;

// @codingStandardsIgnoreStart
if (PsrVersion::isV3()) {
    class PrefixLogger extends PrefixLoggerV3
    {
    }
} else {
    class PrefixLogger extends PrefixLoggerV1
    {
    }
}
