<?php

namespace gipfl\Cli;

class Process
{
    /** @var string|null */
    protected static $initialCwd;

    /**
     * Set the command/process title for this process
     *
     * @param $title
     */
    public static function setTitle($title)
    {
        if (function_exists('cli_set_process_title')) {
            \cli_set_process_title($title);
        }
    }

    /**
     * Replace this process with a new instance of itself by executing the
     * very same binary with the very same parameters
     */
    public static function restart()
    {
        // _ is only available when executed via shell
        $binary = static::getEnv('_');
        $argv = $_SERVER['argv'];
        if (\strlen($binary) === 0) {
            // Problem: this doesn't work if we changed working directory and
            // called the binary with a relative path. Something that doesn't
            // happen when started as a daemon, and when started manually we
            // should have $_ from our shell.
            $binary = static::absoluteFilename(\array_shift($argv));
        } else {
            \array_shift($argv);
        }
        \pcntl_exec($binary, $argv, static::getEnv());
    }

    /**
     * Get the given ENV variable, null if not available
     *
     * Returns an array with all ENV variables if no $key is given
     *
     * @param string|null $key
     * @return array|string|null
     */
    public static function getEnv($key = null)
    {
        if ($key !== null) {
            return \getenv($key);
        }

        if (PHP_VERSION_ID > 70100) {
            return \getenv();
        } else {
            $env = $_SERVER;
            unset($env['argv'], $env['argc']);

            return $env;
        }
    }

    /**
     * Get the path to the executed binary when starting this command
     *
     * This fails if we changed working directory and called the binary with a
     * relative path. Something that doesn't happen when started as a daemon.
     * When started manually we should have $_ from our shell.
     *
     * To be always on the safe side please call Process::getInitialCwd() once
     * after starting your process and before switching directory. That way we
     * preserve our initial working directory.
     *
     * @return mixed|string
     */
    public static function getBinaryPath()
    {
        if (isset($_SERVER['_'])) {
            return $_SERVER['_'];
        } else {
            global $argv;

            return static::absoluteFilename($argv[0]);
        }
    }

    /**
     * The working directory as given by getcwd() the very first time we
     * called this method
     *
     * @return string
     */
    public static function getInitialCwd()
    {
        if (self::$initialCwd === null) {
            self::$initialCwd = \getcwd();
        }

        return self::$initialCwd;
    }

    /**
     * Returns the absolute filename for the given file
     *
     * If relative, it's calculated in relation to the given working directory.
     * The current working directory is being used if null is given.
     *
     * @param $filename
     * @param null $cwd
     * @return string
     */
    public static function absoluteFilename($filename, $cwd = null)
    {
        $filename = \str_replace(
            DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR,
            DIRECTORY_SEPARATOR,
            $filename
        );
        if ($filename[0] === '.') {
            $filename = ($cwd ?: \getcwd()) . DIRECTORY_SEPARATOR . $filename;
        }
        $parts = \explode(DIRECTORY_SEPARATOR, $filename);
        $result = [];
        foreach ($parts as $part) {
            if ($part === '.') {
                continue;
            }
            if ($part === '..') {
                \array_pop($result);
                continue;
            }
            $result[] = $part;
        }

        return \implode(DIRECTORY_SEPARATOR, $result);
    }
}
