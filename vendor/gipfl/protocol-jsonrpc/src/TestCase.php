<?php

namespace gipfl\Protocol\JsonRpc;

use PHPUnit\Framework\TestCase as BaseTestCase;
use React\EventLoop\LoopInterface;

class TestCase extends BaseTestCase
{
    protected $examples = [];

    protected function parseExample($key)
    {
        return Packet::decode($this->examples[$key]);
    }

    protected function failAfterSeconds($seconds, LoopInterface $loop)
    {
        $loop->addTimer($seconds, function () use ($seconds) {
            throw new \RuntimeException("Timed out after $seconds seconds");
        });
    }

    protected function collectErrorsForNotices(&$errors)
    {
        \set_error_handler(function ($errno, $errstr, $errfile, $errline) use (&$errors) {
            if (\error_reporting() === 0) { // @-operator in use
                return false;
            }
            $errors[] = new \ErrorException($errstr, 0, $errno, $errfile, $errline);

            return false; // Always continue with normal error processing
        }, E_ALL | E_STRICT);

        \error_reporting(E_ALL | E_STRICT);
    }

    protected function throwEventualErrors(array $errors)
    {
        foreach ($errors as $error) {
            throw $error;
        }
    }
}
