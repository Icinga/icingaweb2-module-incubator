<?php

namespace gipfl\OpenRpc;

trait SimpleJsonSerializer
{
    public function jsonSerialize()
    {
        return (object) array_filter(get_object_vars($this), function ($value) {
            return $value !== null;
        });
    }
}
