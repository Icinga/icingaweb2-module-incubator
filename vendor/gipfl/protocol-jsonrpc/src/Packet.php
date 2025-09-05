<?php

namespace gipfl\Protocol\JsonRpc;

use gipfl\Json\JsonException;
use gipfl\Json\JsonSerialization;
use gipfl\Json\JsonString;
use gipfl\Protocol\Exception\ProtocolError;

use function property_exists;

abstract class Packet implements JsonSerialization
{
    /** @var \stdClass|null */
    protected $extraProperties;

    /**
     * @return string
     * @throws \gipfl\Json\JsonEncodeException
     */
    public function toString()
    {
        return JsonString::encode($this->jsonSerialize());
    }

    /**
     * @return string
     * @throws \gipfl\Json\JsonEncodeException
     */
    public function toPrettyString()
    {
        return JsonString::encode($this->jsonSerialize(), JSON_PRETTY_PRINT);
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
            if (property_exists($extraProperties, $key)) {
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
        try {
            return self::fromSerialization(JsonString::decode($string));
        } catch (JsonException $e) {
            throw new ProtocolError(sprintf(
                'JSON decode failed: %s (%s)',
                $e->getMessage(),
                self::shorten(self::safeString($string), 360)
            ), Error::PARSE_ERROR);
        }
    }

    protected static function shorten($string, $length)
    {
        if (strlen($string) > $length) {
            return substr($string, 0, $length) . '...';
        }

        return $string;
    }

    protected static function safeString($string)
    {
        if ($string !== null && function_exists('iconv')) {
            return iconv('UTF-8', 'UTF-8//IGNORE', $string);
        }

        return $string;
    }

    public static function fromSerialization($any)
    {
        $version = static::stripRequiredProperty($any, 'jsonrpc');
        if ($version !== '2.0') {
            throw new ProtocolError(
                "Only JSON-RPC 2.0 is supported, got $version",
                Error::INVALID_REQUEST
            );
        }

        // Hint: we MUST use property_exists here, as a NULL id is allowed
        // in error responsed in case it wasn't possible to determine a
        // request id
        $hasId = property_exists($any, 'id');
        $id = static::stripOptionalProperty($any, 'id');
        $error = static::stripOptionalProperty($any, 'error');
        if (property_exists($any, 'method')) {
            $method = static::stripRequiredProperty($any, 'method');
            $params = static::stripRequiredProperty($any, 'params');

            if ($id === null) {
                $packet = new Notification($method, $params);
            } else {
                $packet = new Request($method, $id, $params);
            }
        } elseif (! $hasId) {
            throw new ProtocolError(
                "Given string is not a valid JSON-RPC 2.0 response: id is missing",
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
                $result = static::stripRequiredProperty($any, 'result');
                $packet->setResult($result);
            }
        }
        if (count((array) $any) > 0) {
            $packet->setExtraProperties($any);
        }

        return $packet;
    }

    /**
     * @param object $object
     * @param string $property
     * @throws ProtocolError
     */
    protected static function assertPropertyExists($object, $property)
    {
        if (! property_exists($object, $property)) {
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
        if (property_exists($object, $property)) {
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
        if (! property_exists($object, $property)) {
            throw new ProtocolError(
                "Expected valid JSON-RPC, got no '$property' property",
                Error::INVALID_REQUEST
            );
        }

        $value = $object->$property;
        unset($object->$property);

        return $value;
    }

    /**
     * @deprecated please use jsonSerialize()
     * @return string
     */
    public function toPlainObject()
    {
        return $this->jsonSerialize();
    }
}
