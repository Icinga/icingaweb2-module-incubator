<?php

namespace gipfl\Log;

use Psr\Log\LoggerInterface;
use function array_values;
use function spl_object_hash;

class Logger implements LoggerInterface
{
    /** @deprecated please use LogLevel::LEVEL_EMERGENCY */
    const LEVEL_EMERGENCY = LogLevel::LEVEL_EMERGENCY;
    /** @deprecated please use LogLevel::LEVEL_ALERT */
    const LEVEL_ALERT = LogLevel::LEVEL_ALERT;
    /** @deprecated please use LogLevel::LEVEL_CRITICAL */
    const LEVEL_CRITICAL = LogLevel::LEVEL_CRITICAL;
    /** @deprecated please use LogLevel::LEVEL_ERROR */
    const LEVEL_ERROR = LogLevel::LEVEL_ERROR;
    /** @deprecated please use LogLevel::LEVEL_WARNING */
    const LEVEL_WARNING = LogLevel::LEVEL_WARNING;
    /** @deprecated please use LogLevel::LEVEL_NOTICE */
    const LEVEL_NOTICE = LogLevel::LEVEL_NOTICE;
    /** @deprecated please use LogLevel::LEVEL_INFO */
    const LEVEL_INFO = LogLevel::LEVEL_INFO;
    /** @deprecated please use LogLevel::LEVEL_DEBUG */
    const LEVEL_DEBUG = LogLevel::LEVEL_DEBUG;
    /** @deprecated Please use LogLevel::MAP_NAME_TO_LEVEL */
    const MAP_NAME_TO_LEVEL = LogLevel::MAP_NAME_TO_LEVEL;

    /** @var LogWriter[] */
    protected $writers = [];

    /** @var LogFilter[] */
    protected $filters = [];

    /**
     * @param LogWriter $writer
     */
    public function addWriter(LogWriter $writer)
    {
        $this->writers[spl_object_hash($writer)] = $writer;
    }

    /**
     * @param LogFilter $filter
     */
    public function addFilter(LogFilter $filter)
    {
        $this->filters[spl_object_hash($filter)] = $filter;
    }

    /**
     * @return LogWriter[]
     */
    public function getWriters()
    {
        return array_values($this->writers);
    }

    /**
     * @return LogFilter[]
     */
    public function getFilters()
    {
        return array_values($this->filters);
    }

    /**
     * @param LogWriter $writer
     */
    public function removeWriter(LogWriter $writer)
    {
        unset($this->filters[spl_object_hash($writer)]);
    }

    /**
     * @param LogFilter $filter
     */
    public function removeFilter(LogFilter $filter)
    {
        unset($this->filters[spl_object_hash($filter)]);
    }

    /**
     * @deprecated Please use LogLevel::mapNameToNumeric()
     */
    public static function mapLogLevel($name)
    {
        return LogLevel::mapNameToNumeric($name);
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
