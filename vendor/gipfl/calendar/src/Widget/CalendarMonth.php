<?php

namespace gipfl\Calendar\Widget;

use gipfl\Calendar\Calendar;
use gipfl\Format\LocalTimeFormat;
use gipfl\IcingaWeb2\Link;
use gipfl\IcingaWeb2\Url;
use gipfl\Translation\TranslationHelper;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;

/**
 * WARNING: API will change
 */
class CalendarMonth extends BaseHtmlElement
{
    use TranslationHelper;

    protected $tag = 'div';

    protected $defaultAttributes = [
        'id' => 'calendar-wrap'
    ];

    /** @var Calendar */
    protected $calendar;

    /** @var int */
    protected $now;

    /** @var Url */
    protected $url;

    /** @var HtmlElement */
    protected $days = [];

    protected $timeFormatter;

    public function __construct(Calendar $calendar, Url $url, $now)
    {
        $this->now = $now;
        $this->url = $url;
        $this->calendar = $calendar;
        $this->timeFormatter = new LocalTimeFormat();
    }

    protected function dayRow()
    {
        return Html::tag('ul', ['class' => 'days']);
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

    protected function createDay($day)
    {
        $title = (int) substr($day, -2);

        if ($title === 1) {
            $title = sprintf(
                '%d %s',
                $title,
                $this->timeFormatter->getShortMonthName(strtotime($day))
            );
        }
        $li = Html::tag(
            'li',
            ['class' => 'day'],
            Html::tag('div', ['class' => 'date'], $title)
        );

        $this->days[$day] = $li;

        return $li;
    }

    public function addEvent($time, $text)
    {
        $day = date('Y-m-d', $time);
        if (! $this->hasDay($day)) {
            return $this;
        }
        // $this->getDay($day)->add(Html::tag('div', ['class' => 'event'], [
        $this->getDay($day)->add(Html::tag('a', ['class' => 'event', 'href' => '#'], [
            Html::tag('div', [
                'class' => 'event-time',
                'title' => date('Y-m-d H:i:s')
            ], date('H:i', $time)),
            Html::tag('div', ['class' => 'event-desc'], $text)
        ]));

        return $this;
    }

    protected function getFormerMonth()
    {
        $first = date('Y-m-01', $this->now);

        return date('Y-m-d', strtotime("$first -1month"));
    }

    protected function getNextMonth()
    {
        $first = date('Y-m-01', $this->now);

        return date('Y-m-d', strtotime("$first +1month"));
    }

    protected function getNavigationLinks()
    {
        return Html::tag('div', ['class' => 'calendar-navigation'], [
            Link::create('<', $this->url->with('day', $this->getFormerMonth())),
            Link::create('>', $this->url->with('day', $this->getNextMonth())),
        ]);
    }

    protected function assemble()
    {
        $now = $this->now;
        $today = date('Y-m-d', $now);

        $this->add(
            Html::tag('header', [
                $this->getNavigationLinks(),
                Html::tag('h1', date('F Y', $now))
            ])
        );

        $calendar = Html::tag('div', ['class' => 'calendar']);
        $calendar->add($this->weekdaysHeader());
        $thisMonth = substr($today, 0, 7);

        foreach ($this->calendar->getWeeksForMonth($now) as $cw => $week) {
            $weekRow = $this->dayRow();
            $weekRow->add(
                Html::tag('li', [
                    'class' => 'weekName'
                ], Html::tag('span', sprintf($this->translate('Week %s'), $cw)))
            );
            foreach ($week as $day) {
                $weekRow->add($this->createDay($day));
                if (substr($day, 0, 7) !== $thisMonth) {
                    $this->getDay($day)->addAttributes(['class' => 'other-month']);
                }
            }
            $calendar->add($weekRow);
        }

        $this->add($calendar);
    }

    protected function weekdaysHeader()
    {
        $ul = Html::tag('ul', ['class' => 'weekdays']);
        foreach ($this->calendar->listWeekDayNames() as $weekday) {
            $ul->add(Html::tag('li', $this->translate($weekday)));
        }

        return $ul;
    }
}
