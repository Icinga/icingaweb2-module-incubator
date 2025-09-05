<?php

namespace gipfl\Log;

interface LogFormatter
{
    public function format($level, $message, $context = []);
}
