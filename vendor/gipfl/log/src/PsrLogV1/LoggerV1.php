<?php

namespace gipfl\Log\PsrLogV1;

use gipfl\Log\PsrLogCommon\LoggerCommonTrait;
use Psr\Log\LoggerInterface;

abstract class LoggerV1 implements LoggerInterface
{
    use LoggerCommonTrait;

    public function log($level, $message, array $context = [])
    {
        if (! $this->wants($level, $message, $context)) {
            return;
        }

        foreach ($this->writers as $writer) {
            if ($writer instanceof LogWriterWithContext) {
                $writer->write($level, $message, $context);
            } else {
                $writer->write($level, $this->formatMessage(
                    $message,
                    $context
                ));
            }
        }
    }
}
