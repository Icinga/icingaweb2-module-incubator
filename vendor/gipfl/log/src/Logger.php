<?php

namespace gipfl\Log;

use gipfl\Log\PsrLogV1\LoggerV1;
use gipfl\Log\PsrLogV3\LoggerV3;

// @codingStandardsIgnoreStart
if (PsrVersion::isV3()) {
    class Logger extends LoggerV3
    {
    }
} else {
    class Logger extends LoggerV1
    {
    }
}
