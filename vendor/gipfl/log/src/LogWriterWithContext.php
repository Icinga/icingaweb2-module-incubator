<?php

namespace gipfl\Log;

interface LogWriterWithContext extends LogWriter
{
    public function write($level, $message, $context = []);
}
