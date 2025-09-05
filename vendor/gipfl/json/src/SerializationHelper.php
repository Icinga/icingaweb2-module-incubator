<?php

namespace gipfl\Json;

use InvalidArgumentException;
use JsonSerializable;
use stdClass;

class SerializationHelper
{
    /**
     * TODO: Check whether json_encode() is faster
     *
     * @param mixed $value
     * @return bool
     */
    public static function assertSerializableValue($value)
    {
        if ($value === null || is_scalar($value)) {
            return true;
        }
        if (is_object($value)) {
            if ($value instanceof JsonSerializable) {
                return true;
            }

            if ($value instanceof stdClass) {
                foreach ((array) $value as $val) {
                    static::assertSerializableValue($val);
                }

                return true;
            }
        }

        if (is_array($value)) {
            foreach ($value as $val) {
                static::assertSerializableValue($val);
            }

            return true;
        }

        throw new InvalidArgumentException('Serializable value expected, got ' . static::getPhpType($value));
    }

    public static function getPhpType($var)
    {
        if (is_object($var)) {
            return get_class($var);
        }

        return gettype($var);
    }
}
