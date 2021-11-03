<?php

namespace gipfl\Log;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use InvalidArgumentException;
use function array_key_exists;

class Logger implements LoggerInterface
{
    const LEVEL_EMERGENCY = 0;
    const LEVEL_ALERT = 1;
    const LEVEL_CRITICAL = 2;
    const LEVEL_ERROR = 3;
    const LEVEL_WARNING = 4;
    const LEVEL_NOTICE = 5;
    const LEVEL_INFO = 6;
    const LEVEL_DEBUG = 7;

    const MAP_NAME_TO_LEVEL = [
        LogLevel::EMERGENCY => self::LEVEL_EMERGENCY,
        LogLevel::ALERT     => self::LEVEL_ALERT,
        LogLevel::CRITICAL  => self::LEVEL_CRITICAL,
        LogLevel::ERROR     => self::LEVEL_ERROR,
        LogLevel::WARNING   => self::LEVEL_WARNING,
        LogLevel::NOTICE    => self::LEVEL_NOTICE,
        LogLevel::INFO      => self::LEVEL_INFO,
        LogLevel::DEBUG     => self::LEVEL_DEBUG,
    ];

    /** @var LogWriter[] */
    protected $writers = [];

    /** @var LogFilter[] */
    protected $filters = [];

    /**
     * @param LogWriter $writer
     */
    public function addWriter(LogWriter $writer)
    {
        $this->writers[] = $writer;
    }

    /**
     * @param LogFilter $filter
     */
    public function addFilter(LogFilter $filter)
    {
        $this->filters[] = $filter;
    }

    /**
     * @param string $name
     * @return int
     */
    public static function mapLogLevel($name)
    {
        if (array_key_exists($name, static::MAP_NAME_TO_LEVEL)) {
            return static::MAP_NAME_TO_LEVEL[$name];
        }

        throw new InvalidArgumentException();
    }

    public function emergency($message, array $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function alert($message, array $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function critical($message, array $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function error($message, array $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function warning($message, array $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function notice($message, array $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function info($message, array $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function debug($message, array $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function wants($level, $message, array $context = [])
    {
        foreach ($this->filters as $filter) {
            if (! $filter->wants($level, $message, $context)) {
                return false;
            }
        }

        return true;
    }

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

    protected function formatMessage($message, $context = [])
    {
        if (empty($context)) {
            return $message;
        } else {
            return \sprintf($message, $context);
        }
    }
}
