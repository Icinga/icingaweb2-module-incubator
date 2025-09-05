<?php

namespace gipfl\OpenRpc\Reflection;

class MetaDataTagParser
{
    const DEFAULT_TAG_TYPE = Tag::class;

    const SPECIAL_TAGS = [
        'param'  => ParamTag::class,
        'throws' => ThrowsTag::class,
        'return' => ReturnTag::class,
    ];

    protected $tagType;

    protected $string;

    public function __construct($tagType, $string)
    {
        $this->tagType = $tagType;
        $this->string = $string;
    }

    public function getTag()
    {
        $type = $this->getTagType();
        $tags = static::SPECIAL_TAGS;
        if (isset($tags[$type])) {
            $class = self::SPECIAL_TAGS[$type];
        } else {
            $class = self::DEFAULT_TAG_TYPE;
        }

        return new $class($type, $this->getString());
    }

    /**
     * @return string
     */
    public function getTagType()
    {
        return $this->tagType;
    }

    /**
     * @return string
     */
    public function getString()
    {
        return $this->string;
    }

    public function appendValueString($string)
    {
        $this->string .= $string;

        return $this;
    }
}
