<?php

namespace gipfl\Cli;

/**
 * Base class providing minimal CLI Screen functionality. While classes
 * extending this one (read: AnsiScreen) should implement all the fancy cool
 * things, this base class makes sure that your code will still run in
 * environments with no ANSI or similar support
 *
 * ```php
 * $screen = Screen::instance();
 * echo $screen->center($screen->underline('Hello world'));
 * ```
 */
class Screen
{
    protected $isUtf8;

    /**
     * Get a new Screen instance.
     *
     * For now this is limited to either a very basic Screen implementation as
     * a fall-back or an AnsiScreen implementation with more functionality
     *
     * @return AnsiScreen|Screen
     */
    public static function factory()
    {
        if (! defined('STDOUT')) {
            return new Screen();
        }
        if (\function_exists('posix_isatty') && \posix_isatty(STDOUT)) {
            return new AnsiScreen();
        } else {
            return new Screen();
        }
    }

    /**
     * Center the given string horizontally on the current screen
     *
     * @param $string
     * @return string
     */
    public function center($string)
    {
        $len = $this->strlen($string);
        $width = (int) \floor(($this->getColumns() + $len) / 2) - $len;

        return \str_repeat(' ', $width) . $string;
    }

    /**
     * Clear the screen
     *
     * Impossible for non-ANSI screens, so let's output a newline for now
     *
     * @return string
     */
    public function clear()
    {
        return "\n";
    }

    /**
     * Colorize the given text. Has no effect on a basic Screen, all colors
     * will be accepted. It's prefectly legal to provide background or foreground
     * only
     *
     * Returns the very same string, eventually enriched with related ANSI codes
     *
     * @param $text
     * @param null $fgColor
     * @param null $bgColor
     *
     * @return mixed
     */
    public function colorize($text, $fgColor = null, $bgColor = null)
    {
        return $text;
    }

    /**
     * Generate $count newline characters
     *
     * @param int $count
     * @return string
     */
    public function newlines($count = 1)
    {
        return \str_repeat(PHP_EOL, $count);
    }

    /**
     * Calculate the visible length of a given string. While this is simple on
     * a non-ANSI-screen, such implementation will be required to strip control
     * characters to get the correct result
     *
     * @param $string
     * @return int
     */
    public function strlen($string)
    {
        if ($this->isUtf8()) {
            return \mb_strlen($string, 'UTF-8');
        } else {
            return \strlen($string);
        }
    }

    /**
     * Underline the given text - if possible
     *
     * @return string
     */
    public function underline($text)
    {
        return $text;
    }

    /**
     * Get the number of currently available columns. Please note that this
     * might chance at any time while your program is running
     *
     * @return int
     */
    public function getColumns()
    {
        $cols = (int) \getenv('COLUMNS');
        if (! $cols) {
            // stty -a ?
            $cols = (int) \exec('tput cols');
        }
        if (! $cols) {
            $cols = 80;
        }

        return $cols;
    }

    /**
     * Get the number of currently available rows. Please note that this
     * might chance at any time while your program is running
     *
     * @return int
     */
    public function getRows()
    {
        $rows = (int) \getenv('ROWS');
        if (! $rows) {
            // stty -a ?
            $rows = (int) \exec('tput lines');
        }
        if (! $rows) {
            $rows = 25;
        }

        return $rows;
    }

    /**
     * Whether we're on a UTF-8 screen. We assume latin1 otherwise, there is no
     * support for additional encodings
     *
     * @return bool
     */
    public function isUtf8()
    {
        if ($this->isUtf8 === null) {
            // null should equal 0 here, however seems to equal '' on some systems:
            $current = \setlocale(LC_ALL, 0);

            $parts = explode(';', $current);
            $lc_parts = [];
            foreach ($parts as $part) {
                if (\strpos($part, '=') === false) {
                    continue;
                }
                list($key, $val) = explode('=', $part, 2);
                $lc_parts[$key] = $val;
            }

            $this->isUtf8 = \array_key_exists('LC_CTYPE', $lc_parts)
                && \preg_match('~\.UTF-8$~i', $lc_parts['LC_CTYPE']);
        }

        return $this->isUtf8;
    }
}
