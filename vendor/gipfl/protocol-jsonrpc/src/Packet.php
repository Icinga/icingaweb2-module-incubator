<?php

namespace gipfl\Protocol\JsonRpc;

use gipfl\Protocol\Exception\ProtocolError;

abstract class Packet
{
    /** @var \stdClass|null */
    protected $extraProperties;

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
     * @return bool
     */
    public function hasExtraProperties()
    {
        return $this->extraProperties !== null;
    }

    /**
     * @return \stdClass|null
     */
    public function getExtraProperties()
    {
        return $this->extraProperties;
    }

    /**
     * @param \stdClass|null $extraProperties
     * @return $this
     * @throws ProtocolError
     */
    public function setExtraProperties($extraProperties)
    {
        foreach (['id', 'error', 'result', 'jsonrpc', 'method', 'params'] as $key) {
            if (\property_exists($extraProperties, $key)) {
                throw new ProtocolError("Cannot accept '$key' as an extra property");
            }
        }
        $this->extraProperties = $extraProperties;

        return $this;
    }

    /**
     * @param string $name
     * @param mixed|null $default
     * @return mixed|null
     */
    public function getExtraProperty($name, $default = null)
    {
        if (isset($this->extraProperties->$name)) {
            return $this->extraProperties->$name;
        } else {
            return $default;
        }
    }


    /**
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function setExtraProperty($name, $value)
    {
        if ($this->extraProperties === null) {
            $this->extraProperties = (object) [$name => $value];
        } else {
            $this->extraProperties->$name = $value;
        }

        return $this;
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
        $version = static::stripRequiredProperty($raw, 'jsonrpc');
        if ($version !== '2.0') {
            throw new ProtocolError(
                "Only JSON-RPC 2.0 is supported, got $version",
                Error::INVALID_REQUEST
            );
        }

        // Hint: we MUST use property_exists here, as a NULL id is allowed
        // in error responsed in case it wasn't possible to determine a
        // request id
        $hasId = property_exists($raw, 'id');
        $id = static::stripOptionalProperty($raw, 'id');
        $error = static::stripOptionalProperty($raw, 'error');
        if (\property_exists($raw, 'method')) {
            $method = static::stripRequiredProperty($raw, 'method');
            $params = static::stripRequiredProperty($raw, 'params');

            if ($id === null) {
                $packet = new Notification($method, $params);
            } else {
                $packet = new Request($method, $id, $params);
            }
        } elseif (! $hasId) {
            throw new ProtocolError(
                "Given string is not a valid JSON-RPC 2.0 packet: $string",
                Error::INVALID_REQUEST
            );
        } else {
            $packet = new Response($id);
            if ($error) {
                $packet->setError(new Error(
                    static::stripOptionalProperty($error, 'code'),
                    static::stripOptionalProperty($error, 'message'),
                    static::stripOptionalProperty($error, 'data')
                ));
            } else {
                $result = static::stripRequiredProperty($raw, 'result');
                $packet->setResult($result);
            }
        }
        if (count((array) $raw) > 0) {
            $packet->setExtraProperties($raw);
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

    /**
     * @param \stdClass $object
     * @param string $property
     * @return mixed|null
     */
    protected static function stripOptionalProperty($object, $property)
    {
        if (\property_exists($object, $property)) {
            $value = $object->$property;
            unset($object->$property);

            return $value;
        }

        return null;
    }

    /**
     * @param \stdClass $object
     * @param string $property
     * @return mixed
     * @throws ProtocolError
     */
    protected static function stripRequiredProperty($object, $property)
    {
        if (! \property_exists($object, $property)) {
            throw new ProtocolError(
                "Expected valid JSON-RPC, got no '$property' property",
                Error::INVALID_REQUEST
            );
        }

        $value = $object->$property;
        unset($object->$property);

        return $value;
    }
}
