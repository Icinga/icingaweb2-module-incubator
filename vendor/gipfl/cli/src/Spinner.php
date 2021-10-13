<?php

namespace gipfl\Cli;

use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\ExtendedPromiseInterface;

class Spinner
{
    const ASCII_SLASH = ['/', '-', '\\', '|'];
    const ASCII_BOUNCING_CIRCLE = ['.', 'o', 'O', '°', 'O', 'o'];
    const ROTATING_HALF_CIRCLE = ['◑', '◒', '◐', '◓'];
    const ROTATING_EARTH = ['🌎', '🌏', '🌍'];
    const ROTATING_MOON = ['🌑', '🌒', '🌓', '🌔', '🌕', '🌖', '🌗', '🌘'];
    const UP_DOWN_BAR = [' ', '_', '▁', '▃', '▄', '▅', '▆', '▇', '▆', '▅', '▄', '▃', '▁'];
    const CLOCK = ['🕐', '🕑', '🕒', '🕓', '🕔', '🕕', '🕖', '🕗', '🕘', '🕙', '🕚', '🕛'];
    const WAVING_DOTS = ['⢄', '⢂', '⢁', '⡁', '⡈', '⡐', '⡠', '⡐', '⡈', '⡁', '⢁', '⢂'];
    const ROTATING_DOTS = ['⣷', '⣯', '⣟', '⡿', '⢿', '⣻', '⣽', '⣾'];

    /** @var LoopInterface */
    protected $loop;

    protected $frames;

    protected $frame = -1;

    protected $count;

    protected $delay;

    public function __construct(LoopInterface $loop, array $frames = self::ASCII_SLASH)
    {
        $this->loop = $loop;
        $this->frames = $frames;
        $this->count = \count($frames);
        $this->delay = ((int) (2 * 100 / $this->count)) / 100;
    }

    protected function getNextFrame()
    {
        $first = $this->frame === -1;
        $this->frame++;
        if ($this->frame >= $this->count) {
            $this->frame = 0;
        }

        return $this->frames[$this->frame];
    }

    public function spinWhile(ExtendedPromiseInterface $promise, callable $renderer)
    {
        $next = function () use ($renderer) {
            $renderer($this->getNextFrame());
        };
        $spinTimer = $this->loop->addPeriodicTimer($this->delay, $next);
        $deferred = new Deferred(function () use ($spinTimer) {
            $this->loop->cancelTimer($spinTimer);
        });
        $this->loop->futureTick($next);
        $wait = $deferred->promise();
        $cancel = function () use ($wait) {
            $wait->cancel();
        };
        $promise->otherwise($cancel)->then($cancel);

        return $promise;
    }
}
