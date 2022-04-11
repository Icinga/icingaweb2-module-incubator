<?php

namespace gipfl\Format;

use IntlDateFormatter;

class LocalDateFormat
{
    use LocaleAwareness;

    /** @var IntlDateFormatter */
    protected $formatter;

    /**
     * @param $time
     * @return string
     */
    public function getFullDay($time)
    {
        return $this->formatter()->format($time);
    }

    protected function formatter()
    {
        if ($this->formatter === null) {
            $this->formatter = new IntlDateFormatter(
                $this->getLocale(),
                IntlDateFormatter::FULL,
                IntlDateFormatter::NONE
            );
            $this->formatter->setTimeZone($this->getTimezone());
        }

        return $this->formatter;
    }

    protected function reset()
    {
        $this->formatter = null;
    }
}
