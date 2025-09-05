<?php

namespace gipfl\OpenRpc\Reflection;

class TypeDescriptionTag extends Tag
{
    public $dataType;

    public $description;

    protected function parseTagValue($value)
    {
        if (empty($value)) {
            return;
        }
        $parts = preg_split('/(\s+)/us', trim($value), 2, PREG_SPLIT_DELIM_CAPTURE);

        $this->dataType = array_shift($parts);
        array_shift($parts);

        if (empty($parts)) {
            return;
        }

        $this->description = implode($parts);
    }
}
