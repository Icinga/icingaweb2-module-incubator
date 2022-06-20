<?php

namespace gipfl\OpenRpc\Reflection;

class Tag
{
    /** @var string */
    public $tagType;

    /** @var string */
    public $tagValue;

    public function __construct($tagType, $tagValue)
    {
        $this->tagType = $tagType;
        $this->setTagValue($tagValue);
        $this->parseTagValue(trim($tagValue));
    }

    public function setTagValue($value)
    {
        $this->tagValue = $value;

        return $this;
    }

    /**
     * Parse Tag value into Tag-specific properties
     *
     * Override this method for specific tag types
     *
     * @param $tagValue
     */
    protected function parseTagValue($tagValue)
    {
    }
}
