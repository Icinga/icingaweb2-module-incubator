<?php

namespace gipfl\Calendar\Widget;

use gipfl\Calendar\Calendar;
use gipfl\Format\LocalTimeFormat;
use gipfl\IcingaWeb2\Link;
use gipfl\IcingaWeb2\Url;
use gipfl\Translation\TranslationHelper;
use ipl\Html\HtmlElement;
use ipl\Html\Table;

class CalendarMonthSummary extends Table
{
    use TranslationHelper;

    protected $defaultAttributes = [
        'data-base-target' => '_next',
        'class'            => 'calendar',
    ];

    protected $today;

    protected $year;

    protected $month;

    protected $strMonth;

    protected $strToday;

    protected $days = [];

    /** @var Calendar|null */
    protected $calendar;

    protected $showWeekNumbers = true;

    protected $showOtherMonth = false;

    protected $showGrayFuture = true;

    protected $title;

    protected $color = '255, 128, 0';

    protected $forcedMax;

    protected $timeFormat;

    public function __construct($year, $month)
    {
        $this->year = $year;
        $this->month = $month;
        $this->strMonth = sprintf('%d-%02d', $year, $month);
        $this->strToday = date('Y-m-d');
        $this->timeFormat = new LocalTimeFormat();
    }

    public function setBaseColorRgb($red, $green, $blue)
    {
        $this->color = sprintf('%d, %d, %d', $red, $green, $blue);

        return $this;
    }

    public function setCalendar(Calendar $calendar)
    {
        $this->calendar = $calendar;

        return $this;
    }

    public function getCalendar()
    {
        if ($this->calendar === null) {
            $this->calendar = new Calendar();
        }

        return $this->calendar;
    }

    public function addEvents($events, Url $baseUrl)
    {
        if (empty($events)) {
            return $this;
        }

        if ($this->forcedMax === null) {
            $max = max($events);
        } else {
            $max = $this->forcedMax;
        }

        if ($max === 0 || $max === null) {
            return $this;
        }

        foreach ($events as $day => $count) {
            if (! $this->hasDay($day)) {
                continue;
            }

            if (! $this->showOtherMonth && $this->dayIsInThisMonth($day)) {
                continue;
            }

            $text = (int) substr($day, -2);

            $link = Link::create($text, $baseUrl->with('day', $day));
            $alpha = $count / $max;

            if ($alpha > 0.4) {
                $link->addAttributes(['style' => 'color: white;']);
            }
            $link->addAttributes([
                'title' => sprintf('%d events', $count),
                'style' => sprintf(
                    'background-color: rgba(%s, %.2F);',
                    $this->color,
                    $alpha
                )
            ]);

            $this->getDay($day)->setContent($link);
        }

        return $this;
    }

    public function markNow($now = null)
    {
        if ($now === null) {
            $now = time();
        }
        $this->today = date('Y-m-d', $now);

        return $this;
    }

    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    protected function getTitle()
    {
        if ($this->title === null) {
            $this->title = $this->getMonthName() . ' ' . $this->year;
        }

        return $this->title;
    }

    public function forceMax($max)
    {
        $this->forcedMax = $max;

        return $this;
    }

    protected function getMonthAsTimestamp()
    {
        return strtotime($this->strMonth . '-01');
    }

    protected function assemble()
    {
        $this->setCaption($this->getTitle());
        $this->getHeader()->add($this->createWeekdayHeader());
        $calendar = $this->getCalendar();
        foreach ($calendar->getWeeksForMonth($this->getMonthAsTimestamp()) as $cw => $week) {
            $weekRow = $this->weekRow($cw);
            foreach ($week as $wDay => $day) {
                $dayElement = $this->createDay($day);
                $otherMonth = $this->dayIsInThisMonth($day);
                if ($wDay < 1 || $wDay > 5) {
                    $dayElement->addAttributes(['class' => 'weekend']);
                }
                $weekRow->add($dayElement);
            }
            $this->add($weekRow);
        }
    }

    /**
     * @param $day
     * @return HtmlElement
     */
    protected function getDay($day)
    {
        $this->ensureAssembled();

        return $this->days[$day];
    }

    protected function hasDay($day)
    {
        $this->ensureAssembled();

        return isset($this->days[$day]);
    }

    protected function dayIsInThisMonth($day)
    {
        return substr($day, 0, 7) !== $this->strMonth;
    }

    protected function createDay($day)
    {
        $otherMonth = $this->dayIsInThisMonth($day);
        $title = (int) substr($day, -2);
        if ($otherMonth && ! $this->showOtherMonth) {
            $title = '';
        }
        $td = Table::td($title);
        $this->days[$day] = $td;

        if ($otherMonth) {
            $td->addAttributes(['class' => 'other-month']);
        } elseif ($this->showGrayFuture && $day > $this->strToday) {
            $td->addAttributes(['class' => 'future-day']);
        }

        // TODO: today VS strToday?!
        if ($day === $this->today) {
            $td->addAttributes(['class' => 'today']);
        }

        return $td;
    }

    protected function weekRow($cw)
    {
        $row = Table::tr();

        if ($this->showWeekNumbers) {
            $row->add(Table::th(sprintf('%02d', $cw), [
                'title' => sprintf($this->translate('Calendar Week %d'), $cw)
            ]));
        }

        return $row;
    }

    protected function getMonthName()
    {
        return $this->timeFormat->getMonthName($this->getMonthAsTimestamp());

    }

    protected function createWeekdayHeader()
    {
        $calendar = $this->getCalendar();
        $cols = $calendar->listShortWeekDayNames();
        $row = Table::tr();
        if ($this->showWeekNumbers) {
            $row->add(Table::th(''));
        }
        if ($calendar->firstOfWeekIsMonday()) {
            $weekend = [6 => true, 7 => true];
        } else {
            $weekend = [1 => true, 7 => true];
        }
        $wDay = 0;
        foreach ($cols as $day) {
            $wDay++;
            $col = Table::th($day);
            if (isset($weekend[$wDay])) {
                $col->addAttributes(['class' => 'weekend']);
            }
            $row->add($col);
        }

        return $row;
    }
}
