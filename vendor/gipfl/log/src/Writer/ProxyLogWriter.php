<?php

namespace gipfl\Log\Writer;

use gipfl\Log\Logger;
use gipfl\Log\LogWriterWithContext;

class ProxyLogWriter extends Logger implements LogWriterWithContext
{
    public function write($level, $message, $context = [])
    {
        $this->log($level, $message, $context);
    }
}
