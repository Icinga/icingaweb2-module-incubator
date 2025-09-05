<?php

namespace gipfl\Log\PsrLogCommon;

use gipfl\Log\LogFilter;
use gipfl\Log\LogLevel;
use gipfl\Log\LogWriter;
use Psr\Log\LoggerTrait;

use function array_values;
use function spl_object_hash;

trait LoggerCommonTrait
{
    use LoggerTrait;

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

    public function wants($level, $message, array $context = [])
    {
        foreach ($this->filters as $filter) {
            if (! $filter->wants($level, $message, $context)) {
                return false;
            }
        }

        return true;
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
