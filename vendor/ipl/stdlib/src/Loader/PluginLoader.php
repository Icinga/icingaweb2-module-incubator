<?php

namespace ipl\Stdlib\Loader;

use InvalidArgumentException;

trait PluginLoader
{
    /** @var array  */
    protected $pluginLoaders = [];

    /**
     * @param $type
     * @param $name
     * @return object
     */
    public function loadPlugin($type, $name)
    {
        $plugin = $this->eventuallyLoadPlugin($type, $name);

        if ($plugin === null) {
            throw new InvalidArgumentException(sprintf(
                'Could not load %s "%s"',
                $type,
                $name
            ));
        }

        return $plugin;
    }

    /**
     * @param $type
     * @param $name
     * @return null|object
     */
    public function eventuallyLoadPlugin($type, $name)
    {
        $class = $this->eventuallyGetPluginClass($type, $name);

        if ($class !== null) {
            return new $class;
        }

        return null;
    }

    public function eventuallyGetPluginClass($type, $name)
    {
        if ($this->hasPluginLoadersFor($type)) {
            /** @var PluginLoader $loader */
            foreach ($this->pluginLoaders[$type] as $loader) {
                $class = $loader->eventuallyGetClassByName($name);
                if ($class !== null) {
                    return $class;
                }
            }
        }

        return null;
    }

    /**
     * @param $type
     * @param PluginLoader|string $loaderOrNamespace
     * @param string $postfix
     * @return $this
     */
    public function addPluginLoader($type, $loaderOrNamespace, $postfix = '')
    {
        if ($loaderOrNamespace instanceof PluginLoader) {
            $loader = $loaderOrNamespace;
        } else {
            $loader = new AutoLoadingPluginLoader($loaderOrNamespace, $postfix);
        }

        if (! isset($this->pluginLoaders[$type])) {
            $this->pluginLoaders[$type] = [];
        }

        array_unshift($this->pluginLoaders[$type], $loader);

        return $this;
    }

    /**
     * @param $type
     * @return bool
     */
    public function hasPluginLoadersFor($type)
    {
        return isset($this->pluginLoaders[$type]);
    }
}
