<?php

namespace gipfl\DataType;

use gipfl\Json\JsonSerialization;
use gipfl\Json\JsonString;
use function ksort;

class SetOfSettings implements JsonSerialization
{
    /** @var Settings[] */
    protected $sections = [];

    /**
     * @param Settings[]|array|\stdClass $set
     */
    public function __construct(array $set = [])
    {
        foreach ((array) $set as $section => $settings) {
            $this->setSection($section, $settings);
        }
    }

    public static function fromSerialization($any)
    {
        return new static($any);
    }

    public function set($section, $setting, $value)
    {
        if (! isset($this->sections[$section])) {
            $this->sections[$section] = new Settings();
        }
        $this->sections[$section]->set($setting, $value);

        return $this;
    }

    public function get($section, $setting, $default = null)
    {
        if (isset($this->sections[$section])) {
            return $this->sections[$section]->get($setting, $default);
        }

        return $default;
    }

    public function setSection($section, $settings)
    {
        if ($settings instanceof Settings) {
            $this->sections[$section] = clone($settings);
        } else {
            $this->sections[$section] = new Settings($settings);
        }

        return $this;
    }

    public function cloneSection($section)
    {
        if (array_key_exists($section, $this->sections)) {
            return clone($this->sections[$section]);
        }

        return new Settings();
    }

    public function equals(SetOfSettings $settings)
    {
        return JsonString::encode($settings) === JsonString::encode($this);
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        ksort($this->sections);
        return (object) $this->sections;
    }
}
