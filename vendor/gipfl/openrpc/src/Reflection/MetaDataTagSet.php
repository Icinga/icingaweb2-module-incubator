<?php

namespace gipfl\OpenRpc\Reflection;

class MetaDataTagSet
{
    /** @var Tag[] */
    protected $tags;

    public function __construct()
    {
        $this->tags = [];
    }

    public function add(Tag $tag)
    {
        $this->tags[] = $tag;
    }

    /**
     * @param string $type
     * @return static
     */
    public function byType($type)
    {
        $set = new static();
        foreach ($this->tags as $tag) {
            if ($tag->tagType === $type) {
                $set->add($tag);
            }
        }

        return $set;
    }

    /**
     * @return MetaDataParameter[]
     */
    public function getParams()
    {
        $result = [];
        foreach ($this->byType('param')->getTags() as $tag) {
            assert($tag instanceof ParamTag);
            $result[] = new MetaDataParameter($tag->name, $tag->dataType, $tag->description);
            // TODO: variadic!
        }

        return $result;
    }

    /**
     * @return string|null
     */
    public function getReturnType()
    {
        foreach ($this->byType('return')->getTags() as $tag) {
            assert($tag instanceof ReturnTag);
            // TODO: return a class, we need the description
            return $tag->dataType;
        }

        return null;
    }

    public function getTags()
    {
        return $this->tags;
    }
}
