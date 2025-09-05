<?php

namespace gipfl\Format;

use DateTimeZone;
use IntlTimeZone;
use Locale;
use RuntimeException;

trait LocaleAwareness
{
    /** @var string */
    protected $locale;

    /** @var DateTimeZone|IntlTimeZone */
    protected $timezone;

    /**
     * @param string $locale
     * @return void
     */
    public function setLocale($locale)
    {
        if ($this->locale !== $locale) {
            $this->locale = (string) $locale;
            $this->reset();
        }
    }

    /**
     * @param DateTimeZone|IntlTimeZone $timezone
     * @return void
     */
    public function setTimezone($timezone)
    {
        // Hint: type checking is delegated to timeZonesAreEqual
        if (self::timeZonesAreEqual($this->timezone, $timezone)) {
            return;
        }

        $this->timezone = $timezone;
        $this->reset();
    }

    protected function wantsAmPm()
    {
        // TODO: complete this list
        return in_array($this->getLocale(), ['en_US', 'en_US.UTF-8']);
    }

    protected function isUsEnglish()
    {
        return in_array($this->getLocale(), ['en_US', 'en_US.UTF-8']);
    }

    protected function getLocale()
    {
        if ($this->locale === null) {
            $this->locale = Locale::getDefault();
        }

        return $this->locale;
    }

    protected function getTimezone()
    {
        if ($this->timezone === null) {
            $this->timezone = new DateTimeZone(date_default_timezone_get());
        }

        return $this->timezone;
    }

    protected static function timeZonesAreEqual($left, $right)
    {
        if ($left === null && $right !== null) {
            return false;
        }
        if ($left !== null && $right === null) {
            return false;
        }
        if ($left instanceof DateTimeZone) {
            return $right instanceof DateTimeZone && $left->getName() === $right->getName();
        }
        if ($left instanceof IntlTimeZone) {
            return $right instanceof IntlTimeZone && $left->getID() === $right->getID();
        }

        throw new RuntimeException(sprintf(
            'Valid timezone expected, got left=%s, right=%s',
            is_object($left) ? get_class($left) : gettype($left),
            is_object($right) ? get_class($right) : gettype($right)
        ));
    }
}
