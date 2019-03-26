<?php

namespace ipl\Stdlib\Loader;

use InvalidArgumentException;

class AutoLoadingPluginLoader implements PluginLoaderInterface
{
    protected $namespace;

    protected $postfix;

    protected $uppercaseFirst = true;

    /**
     * AutoloadingPluginLoader constructor.
     * @param $namespace
     * @param string $postfix
     */
    public function __construct($namespace, $postfix = '')
    {
        $this->namespace = $namespace;
        $this->postfix = $postfix;
    }

    public function load($name)
    {
        $instance = $this->eventuallyLoad($name);

        if ($instance === null) {
            throw new InvalidArgumentException(sprintf(
                'Unable to load %s (%s)',
                $name,
                $this->getFullClassByName($name)
            ));
        }

        return $instance;
    }

    public function eventuallyLoad($name)
    {
        $class = $this->eventuallyGetClassByName($name);
        if ($class === null) {
            return null;
        } else {
            return new $class;
        }
    }

    public function eventuallyGetClassByName($name)
    {
        $class = $this->getFullClassByName($name);
        if (class_exists($class)) {
            return $class;
        } else {
            return null;
        }
    }

    /**
     * @param bool $uppercaseFirst
     * @return $this
     */
    public function setUppercaseFirst($uppercaseFirst = true)
    {
        $this->uppercaseFirst = $uppercaseFirst;

        return $this;
    }

    /**
     * @param $name
     * @return string
     */
    public function getFullClassByName($name)
    {
        if ($this->uppercaseFirst) {
            $name = ucfirst($name);
        }

        return $this->namespace . '\\' . $name . $this->postfix;
    }
}
