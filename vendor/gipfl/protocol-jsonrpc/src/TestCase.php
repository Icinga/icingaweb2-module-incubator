<?php

namespace gipfl\Protocol\JsonRpc;

use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected $examples = [];

    protected function parseExample($key)
    {
        return Packet::decode($this->examples[$key]);
    }
}
