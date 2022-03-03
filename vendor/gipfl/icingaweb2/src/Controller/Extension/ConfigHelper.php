<?php

namespace gipfl\IcingaWeb2\Controller\Extension;

use Icinga\Application\Config;

trait ConfigHelper
{
    private $config;

    private $configs = [];

    /**
     * @param null $file
     * @return Config
     * @codingStandardsIgnoreStart
     */
    public function Config($file = null)
    {
        // @codingStandardsIgnoreEnd
        if ($this->moduleName === null) {
            if ($file === null) {
                return Config::app();
            } else {
                return Config::app($file);
            }
        } else {
            return $this->getModuleConfig($file);
        }
    }

    public function getModuleConfig($file = null)
    {
        if ($file === null) {
            if ($this->config === null) {
                $this->config = Config::module($this->getModuleName());
            }
            return $this->config;
        } else {
            if (! array_key_exists($file, $this->configs)) {
                $this->configs[$file] = Config::module($this->getModuleName(), $file);
            }
            return $this->configs[$file];
        }
    }
}
