<?php

namespace gipfl\DataType;

use gipfl\Json\JsonSerialization;
use gipfl\Json\JsonString;
use gipfl\Json\SerializationHelper;
use InvalidArgumentException;
use stdClass;
use function array_key_exists;
use function ksort;

class Settings implements JsonSerialization
{
    protected $settings = [];

    /**
     * @param object|array $settings
     */
    public function __construct($settings = [])
    {
        foreach ((array) $settings as $property => $value) {
            $this->set($property, $value);
        }
    }

    /**
     * @param stdClass|array $object
     * @return static
     */
    public static function fromSerialization($object)
    {
        return new static($object);
    }

    public function set($name, $value)
    {
        SerializationHelper::assertSerializableValue($value);
        $this->settings[$name] = $value;
    }

    public function get($name, $default = null)
    {
        if ($this->has($name)) {
            return $this->settings[$name];
        }

        return $default;
    }

    public function getArray($name, $default = [])
    {
        if ($this->has($name)) {
            return (array) $this->settings[$name];
        }

        return $default;
    }

    public function requireArray($name)
    {
        return (array) $this->getRequired(($name));
    }

    public function getAsSettings($name, ?Settings $default = null)
    {
        if ($this->has($name)) {
            return Settings::fromSerialization($this->settings[$name]);
        }

        if ($default === null) {
            return new Settings();
        }

        return $default;
    }

    public function getRequired($name)
    {
        if ($this->has($name)) {
            return $this->settings[$name];
        }

        throw new InvalidArgumentException("Setting '$name' is not available");
    }

    public function has($name)
    {
        return array_key_exists($name, $this->settings);
    }

    public function equals(Settings $settings)
    {
        return JsonString::encode($settings) === JsonString::encode($this);
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        ksort($this->settings);
        return (object) $this->settings;
    }
}
