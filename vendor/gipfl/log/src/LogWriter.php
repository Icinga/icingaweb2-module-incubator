<?php

namespace gipfl\Log;

interface LogWriter
{
    public function write($level, $message);
}
