<?php

namespace gipfl\Log\IcingaWeb;

use Icinga\Application\Logger as IcingaApplicationLogger;
use Icinga\Exception\ConfigurationError;
use Psr\Log\LoggerInterface;

class IcingaLogger extends IcingaApplicationLogger
{
    public static function replace(LoggerInterface $logger)
    {
        static::replaceRunningInstance(new LoggerLogWriter($logger));
    }

    public static function replaceRunningInstance(LoggerLogWriter $writer, $level = null)
    {
        try {
            $instance = static::$instance;
            if ($level !== null) {
                $instance->setLevel($level);
            }

            $instance->writer = $writer;
        } catch (ConfigurationError $e) {
            static::$instance->error($e->getMessage());
        }
    }
}
