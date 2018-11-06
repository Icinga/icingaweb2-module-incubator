<?php

namespace gipfl\Protocol\JsonRpc;

use gipfl\Protocol\Exception\ProtocolError;

abstract class Packet
{
    abstract public function toPlainObject();

    /**
     * @return string
     */
    public function toString()
    {
        return \json_encode($this->toPlainObject());
    }

    /**
     * @return string
     */
    public function toPrettyString()
    {
        return \json_encode($this->toPlainObject(), JSON_PRETTY_PRINT);
    }

    /**
     * @param $string
     * @return Notification|Request|Response
     * @throws ProtocolError
     */
    public static function decode($string)
    {
        $raw = \json_decode($string);
        if (null === $raw && json_last_error() > 0) {
            throw new ProtocolError(sprintf(
                'JSON decode failed: %s',
                \json_last_error_msg()
            ), Error::PARSE_ERROR);
        }
        static::assertPropertyExists($raw, 'jsonrpc');

        if ($raw->jsonrpc !== '2.0') {
            throw new ProtocolError(sprintf(
                'Only JSON-RPC 2.0 is supported, got %s',
                $raw->jsonrpc
            ), Error::INVALID_REQUEST);
        }

        if (\property_exists($raw, 'method')) {
            static::assertPropertyExists($raw, 'params');
            if (\property_exists($raw, 'id')) {
                return new Request($raw->method, $raw->id, $raw->params);
            } else {
                return new Notification($raw->method, $raw->params);
            }
        } elseif (\property_exists($raw, 'id')) {
            $packet = new Response($raw->id);
            static::assertPropertyExists($raw, 'result');
            $packet->setResult($raw->result);
        } else {
            throw new ProtocolError(
                "Given string is not a valid JSON-RPC 2.0 packet: $string",
                Error::INVALID_REQUEST
            );
        }

        return $packet;
    }

    /**
     * @param $object
     * @param $property
     * @throws ProtocolError
     */
    protected static function assertPropertyExists($object, $property)
    {
        if (! \property_exists($object, $property)) {
            throw new ProtocolError(
                "Expected valid JSON-RPC, got no '$property' property",
                Error::INVALID_REQUEST
            );
        }
    }
}
