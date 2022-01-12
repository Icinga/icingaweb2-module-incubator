<?php

namespace gipfl\Log\Formatter;

use gipfl\Log\LogFormatter;
use function date;
use function microtime;

class StdOutFormatter implements LogFormatter
{
    protected $dateFormat = 'Y-m-d H:i:s';

    protected $showTimestamp = true;

    public function format($level, $message, $context = [])
    {
        // TODO: replace placeholders!
        return $this->renderDatePrefix() . sprintf($message, $context);
    }

    protected function renderDatePrefix()
    {
        if ($this->showTimestamp) {
            return date($this->dateFormat, microtime(true));
        }

        return '';
    }

    public function setShowTimestamp($show = true)
    {
        $this->showTimestamp = $show;
    }
}
