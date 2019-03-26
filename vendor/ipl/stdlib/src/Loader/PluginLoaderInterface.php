<?php

namespace ipl\Stdlib\Loader;

use InvalidArgumentException;

interface PluginLoaderInterface
{
    /**
     *
     *
     * @param $name
     * @return string|null
     */
    public function eventuallyGetClassByName($name);

    /**
     * @param string $name
     * @return object|null
     */
    public function eventuallyLoad($name);

    /**
     * @param $name
     * @throws InvalidArgumentException
     * @return object
     */
    public function load($name);
}
