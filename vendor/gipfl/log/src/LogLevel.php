<?php

namespace gipfl\Log;

use InvalidArgumentException;
use Psr\Log\LogLevel as PsrLogLevel;

class LogLevel extends PsrLogLevel
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
        self::EMERGENCY => self::LEVEL_EMERGENCY,
        self::ALERT     => self::LEVEL_ALERT,
        self::CRITICAL  => self::LEVEL_CRITICAL,
        self::ERROR     => self::LEVEL_ERROR,
        self::WARNING   => self::LEVEL_WARNING,
        self::NOTICE    => self::LEVEL_NOTICE,
        self::INFO      => self::LEVEL_INFO,
        self::DEBUG     => self::LEVEL_DEBUG,
    ];

    const MAP_LEVEL_TO_NAME = [
        self::LEVEL_EMERGENCY => self::EMERGENCY,
        self::LEVEL_ALERT     => self::ALERT,
        self::LEVEL_CRITICAL  => self::CRITICAL,
        self::LEVEL_ERROR     => self::ERROR,
        self::LEVEL_WARNING   => self::WARNING,
        self::LEVEL_NOTICE    => self::NOTICE,
        self::LEVEL_INFO      => self::INFO,
        self::LEVEL_DEBUG     => self::DEBUG,
    ];

    /**
     * @param string $name
     * @return int
     */
    public static function mapNameToNumeric($name)
    {
        if (array_key_exists($name, static::MAP_NAME_TO_LEVEL)) {
            return static::MAP_NAME_TO_LEVEL[$name];
        }

        throw new InvalidArgumentException("$name is not a valid log level name");
    }

    /**
     * @param int $number
     * @return string
     */
    public static function mapNumericToName($number)
    {
        if (array_key_exists($number, static::MAP_LEVEL_TO_NAME)) {
            return static::MAP_LEVEL_TO_NAME[$number];
        }

        throw new InvalidArgumentException("$number is not a valid numeric log level");
    }
}
