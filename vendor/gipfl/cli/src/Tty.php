<?php

namespace gipfl\Cli;

use InvalidArgumentException;
use React\EventLoop\LoopInterface;
use React\Stream\ReadableResourceStream;
use React\Stream\WritableResourceStream;
use RuntimeException;
use function defined;
use function fstat;
use function function_exists;
use function is_bool;
use function is_resource;
use function is_string;
use function posix_isatty;
use function register_shutdown_function;
use function stream_isatty;
use function stream_set_blocking;
use function strlen;
use function var_export;

class Tty
{
    protected $stdin;

    protected $stdout;

    protected $loop;

    protected $echo = true;

    /** @var TtyMode */
    protected $ttyMode;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
        register_shutdown_function([$this, 'restore']);
        $loop->futureTick(function () {
            $this->initialize();
        });
    }

    public function setEcho($echo)
    {
        if (! is_bool($echo) && ! is_string($echo) && strlen($echo) !== 1) {
            throw new InvalidArgumentException(
                "\$echo must be boolean or a single character, got " . var_export($echo, 1)
            );
        }
        $this->echo = $echo;
        if ($this->ttyMode) {
            if ($echo) {
                $this->ttyMode->enableFeature('echo');
            } else {
                $this->ttyMode->disableFeature('echo');
            }
        }

        return $this;
    }

    public function stdin()
    {
        if ($this->stdin === null) {
            $this->assertValidStdin();
            $this->stdin = new ReadableResourceStream(STDIN, $this->loop);
        }

        return $this->stdin;
    }

    protected function hasStdin()
    {
        return defined('STDIN') && is_resource(STDIN) && fstat(STDIN) !== false;
    }

    protected function assertValidStdin()
    {
        if (! $this->hasStdin()) {
            throw new RuntimeException('I have no STDIN');
        }
    }

    public function stdout()
    {
        if ($this->stdout === null) {
            $this->assertValidStdout();
            $this->stdout = new WritableResourceStream(STDOUT, $this->loop);
        }

        return $this->stdout;
    }

    protected function hasStdout()
    {
        return defined('STDOUT') && is_resource(STDOUT) && fstat(STDOUT) !== false;
    }

    protected function assertValidStdout()
    {
        if (! $this->hasStdout()) {
            throw new RuntimeException('I have no STDOUT');
        }
    }

    protected function initialize()
    {
        $this->ttyMode = new TtyMode();
        $this->ttyMode->setPreferredMode($this->echo);
    }

    public static function isSupported()
    {
        if (PHP_VERSION_ID >= 70200) {
            return stream_isatty(STDIN);
        } elseif (function_exists('posix_isatty')) {
            return posix_isatty(STDIN);
        } else {
            return false;
        }
    }

    public function restore()
    {
        if ($this->hasStdin()) {
            // ReadableResourceStream sets blocking to false, let's restore this
            stream_set_blocking(STDIN, true);
        }
    }
}
