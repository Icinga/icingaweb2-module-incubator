<?php

namespace gipfl\Format;

use IntlDateFormatter;
use RuntimeException;

class LocalTimeFormat
{
    use LocaleAwareness;

    /** @var IntlDateFormatter */
    protected $formatter;

    /**
     * For available symbols please see:
     * https://unicode-org.github.io/icu/userguide/format_parse/datetime/#date-field-symbol-table
     *
     * @param int|float $time Hint: also supports DateTime, DateTimeInterface since 7.1.5
     * @return string
     */
    public function format($time, $pattern)
    {
        $result = $this->formatter($pattern)->format($time);
        if ($result === false) {
            throw new RuntimeException(sprintf(
                'Failed to format %s as "%s": %s (%d)',
                $time,
                $pattern,
                $this->formatter->getErrorMessage(),
                $this->formatter->getErrorCode()
            ));
        }

        return $result;
    }

    /**
     * @param $time
     * @return string
     */
    public function getWeekOfYear($time)
    {
        return $this->format($time, 'ww');
    }

    /**
     * @param $time
     * @return int
     */
    public function getNumericWeekOfYear($time)
    {
        return (int) $this->format($time, 'w');
    }

    /**
     * @param $time
     * @return string
     */
    public function getDayInMonth($time)
    {
        return $this->format($time, 'dd');
    }

    /**
     * @param $time
     * @return int
     */
    public function getNumericDayInMonth($time)
    {
        return (int) $this->format($time, 'd');
    }

    /**
     * @param $time
     * @return string
     */
    public function getWeekdayName($time)
    {
        return $this->format($time, 'cccc');
    }

    /**
     * @param $time
     * @return string
     */
    public function getShortWeekdayName($time)
    {
        return $this->format($time, 'ccc');
    }

    /**
     * e.g. September
     *
     * @param $time
     * @return string
     */
    public function getMonthName($time)
    {
        return $this->format($time, 'LLLL');
    }

    /**
     * e.g. Sep
     *
     * @param $time
     * @return string
     */
    public function getShortMonthName($time)
    {
        return $this->format($time, 'LLL');
    }

    /**
     * e.g. 2021
     * @param $time
     * @return string
     */
    public function getYear($time)
    {
        return $this->format($time, 'y');
    }

    /**
     * e.g. 21
     *
     * @param $time
     * @return string
     */
    public function getShortYear($time)
    {
        return $this->format($time, 'yy');
    }

    /**
     * e.g. 21:50:12
     *
     * @param $time
     * @return string
     */
    public function getTime($time)
    {
        if ($this->wantsAmPm()) {
            return $this->format($time, 'h:mm:ss a');
        }

        return $this->format($time, 'H:mm:ss');
    }

    /**
     * e.g. 21:50
     *
     * @param $time
     * @return string
     */
    public function getShortTime($time)
    {
        if ($this->wantsAmPm()) {
            return $this->format($time, 'K:mm a');
        }

        return $this->format($time, 'H:mm');
    }

    protected function formatter($pattern)
    {
        if ($this->formatter === null) {
            $this->formatter = new IntlDateFormatter(
                $this->getLocale(),
                IntlDateFormatter::GREGORIAN,
                IntlDateFormatter::GREGORIAN
            );
            $this->formatter->setTimeZone($this->getTimezone());
        }
        $this->formatter->setPattern($pattern);

        return $this->formatter;
    }

    protected function reset()
    {
        $this->formatter = null;
    }
}
