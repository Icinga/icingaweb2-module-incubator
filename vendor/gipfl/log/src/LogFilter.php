<?php

namespace gipfl\Log;

interface LogFilter
{
    /**
     * @param string $level
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function wants($level, $message, $context = []);
}
