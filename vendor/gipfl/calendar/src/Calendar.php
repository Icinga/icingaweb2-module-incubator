<?php

namespace gipfl\Calendar;

use gipfl\Format\LocalTimeFormat;
use InvalidArgumentException;

class Calendar
{
    const FIRST_IS_MONDAY = 1;
    const FIRST_IS_SUNDAY = 0;

    protected $firstOfWeek;

    protected $weekDays = [];

    protected $shortWeekDays = [];

    protected $timeFormat;

    public function __construct($firstOfWeek = self::FIRST_IS_MONDAY)
    {
        $this->timeFormat = new LocalTimeFormat();
        $this->setFirstOfWeek($firstOfWeek);
    }

    public function firstOfWeekIsMonday()
    {
        return $this->firstOfWeek === self::FIRST_IS_MONDAY;
    }

    public function firstOfWeekIsSunday()
    {
        return $this->firstOfWeek === self::FIRST_IS_SUNDAY;
    }

    public function setFirstOfWeek($firstOfWeek)
    {
        if ($firstOfWeek === self::FIRST_IS_SUNDAY || $firstOfWeek === self::FIRST_IS_MONDAY) {
            if ($firstOfWeek !== $this->firstOfWeek) {
                $this->firstOfWeek = $firstOfWeek;
                $this->prepareWeekDays();
            }

            return $this;
        } else {
            throw new InvalidArgumentException(
                "First day of week has to be either 0 or 1, got '$firstOfWeek'"
            );
        }
    }

    protected function prepareWeekDays()
    {
        if ($this->firstOfWeekIsSunday()) {
            $start = '2019-02-03';
        } else {
            $start = '2019-02-04';
        }

        for ($i = 0; $i < 7; $i++) {
            $day = strtotime("$start +{$i}days");
            $this->weekDays[] = $this->timeFormat->getWeekdayName($day);
            $this->shortWeekDays[] = $this->timeFormat->getShortWeekdayName($day);
        }
    }

    public function listWeekDayNames()
    {
        return $this->weekDays;
    }

    public function listShortWeekDayNames()
    {
        return $this->shortWeekDays;
    }

    /**
     * Either 'N' or 'w', depending on the first day of week
     *
     * @return string
     */
    protected function getDowFormat()
    {
        if ($this->firstOfWeekIsMonday()) {
            // N -> 1-7 (Mo-Su)
            return 'N';
        } else {
            // w -> 0-6 (Su-Sa)
            return 'w';
        }
    }

    /**
     * @param $time
     * @return int
     */
    protected function getWeekDay($time)
    {
        return (int) date($this->getDowFormat(), $time);
    }

    /**
     * @param int $now
     * @return array
     */
    public function getDaysForWeek($now)
    {
        $formatDow = $this->getDowFormat();
        $today = date('Y-m-d', $now);
        $day = $this->getFirstDayOfWeek($today);
        $weekday = (int) date($formatDow, strtotime($day));
        $week = [$weekday => $day];
        for ($i = 1; $i < 7; $i++) {
            $day = date('Y-m-d', strtotime("$day +1day"));
            $weekday = (int) date($formatDow, strtotime($day));
            $week[$weekday] = $day;
        }

        return $week;
    }

    /**
     * @param int $now
     * @return array
     */
    public function getWorkingDaysForWeek($now)
    {
        $formatDow = $this->getDowFormat();
        $today = date('Y-m-d', $now);
        $day = $this->getFirstDayOfWeek($today, self::FIRST_IS_MONDAY);
        $weekday = (int) date($formatDow, strtotime($day));
        $week = [$weekday => $day];
        for ($i = 1; $i < 5; $i++) {
            $day = date('Y-m-d', strtotime("$day +1day"));
            $weekday = (int) date($formatDow, strtotime($day));
            $week[$weekday] = $day;
        }

        return $week;
    }

    /**
     * @param string $day
     * @param int $firstOfWeek
     * @return string
     */
    public function getFirstDayOfWeek($day, $firstOfWeek = null)
    {
        if ($firstOfWeek === null) {
            $firstOfWeek = $this->firstOfWeek;
        }
        $dow = $this->getWeekDay(strtotime($day));
        if ($dow > $firstOfWeek) {
            $sub = $dow - $firstOfWeek;
            return date('Y-m-d', strtotime("$day -{$sub}day"));
        } else {
            return $day;
        }
    }

    /**
     * @param string $day
     * @param int $firstOfWeek
     * @return string
     */
    protected function getLastDayOfWeek($day, $firstOfWeek = null)
    {
        if ($firstOfWeek === null) {
            $firstOfWeek = $this->firstOfWeek;
        }
        $dow = $this->getWeekDay(strtotime($day));
        $lastOfWeek = $firstOfWeek + 6;
        if ($dow < $lastOfWeek) {
            $add = $lastOfWeek - $dow;
            return static::expressionToDate(static::incDay($day, $add));
        } else {
            return $day;
        }
    }

    public function getWeekOfTheYear($day)
    {
        $time = strtotime($day);
        // 0 = Sunday
        if ($this->firstOfWeekIsSunday() && $this->getWeekDay($time) === 0) {
            if (substr($time, 4, 6) === '-12-31') {
                return (int) date('W', strtotime(static::decDay($day)));
            } else {
                return (int) date('W', strtotime(static::incDay($day)));
            }
        } else {
            return (int) date('W', $time);
        }
    }

    /**
     * @param int $now
     * @return array
     */
    public function getWeeksForMonth($now)
    {
        $first = date('Y-m-01', $now);
        $last = date('Y-m-d', strtotime("$first +1month -1day"));

        $formatDow = $this->getDowFormat();
        $end = $this->getLastDayOfWeek($last);
        $day = $this->getFirstDayOfWeek($first);
        $formerWeekOfTheYear = 0;
        $weeks = [];
        while ($day <= $end) {
            $weekOfTheYear = $this->getWeekOfTheYear($day);
            if ($weekOfTheYear !== $formerWeekOfTheYear) {
                $weeks[$weekOfTheYear] = [];
                $week = & $weeks[$weekOfTheYear];
            }

            $weekday = (int) date($formatDow, strtotime($day));
            $week[$weekday] = $day;
            $day = date('Y-m-d', strtotime("$day +1day"));
            $formerWeekOfTheYear = $weekOfTheYear;
        }

        return $weeks;
    }

    protected static function expressionToDate($expression)
    {
        return date('Y-m-d', strtotime($expression));
    }

    /**
     * @param string $day
     * @param int $increment days to add
     * @return string
     */
    protected static function incDay($day, $increment = 1)
    {
        return sprintf('%s +%dday', $day, $increment);
    }

    protected static function decDay($day, $decrement = 1)
    {
        return sprintf('%s -%dday', $day, $decrement);
    }
}
