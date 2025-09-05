<?php

namespace gipfl\OpenRpc\Reflection;

class ParamTag extends Tag
{
    public $name;

    public $dataType;

    public $description;

    public $isVariadic = false;

    protected function parseTagValue($value)
    {
        $parts = preg_split('/(\s+)/us', $value, 3, PREG_SPLIT_DELIM_CAPTURE);
        if (substr($parts[0], 0, 1) !== '$' && substr($parts[0], 0, 4) !== '...$') {
            $this->dataType = array_shift($parts);
            array_shift($parts);
        }
        if (empty($parts)) {
            return;
        }

        if (substr($parts[0], 0, 1) === '$') {
            $this->name = substr($parts[0], 1);
            array_shift($parts);
            array_shift($parts);
        } elseif (substr($parts[0], 0, 4) !== '...$') {
            $this->name = substr($parts[0], 4);
            $this->isVariadic = true;
            array_shift($parts);
            array_shift($parts);
        }

        if (! empty($parts)) {
            $this->description = implode($parts);
        }
    }
}
