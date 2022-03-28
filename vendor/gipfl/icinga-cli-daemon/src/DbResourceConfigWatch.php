<?php

namespace gipfl\IcingaCliDaemon;

use Icinga\Application\Config;
use InvalidArgumentException;
use React\EventLoop\LoopInterface;

/**
 * DbResourceConfigWatch
 *
 * Checks every $interval = 3 seconds for changed DB resource configuration.
 * Notifies registered callbacksin case this happens.
 */
class DbResourceConfigWatch
{
    /** @var string */
    protected $configFile;

    /** @var string */
    protected $resourceConfigFile;

    /** @var string|null */
    protected $dbResourceName;

    /** @var array|null|false It's false on initialization to trigger  */
    protected $resourceConfig = false;

    /** @var int|float */
    protected $interval = 3;

    /** @var callable[] */
    protected $callbacks = [];

    /**
     * @param string $dbResourceName
     * @return DbResourceConfigWatch
     */
    public static function name($dbResourceName)
    {
        $self = new static();
        $self->dbResourceName = $dbResourceName;

        return $self;
    }

    /**
     * @param string $moduleName
     * @return DbResourceConfigWatch
     */
    public static function module($moduleName)
    {
        $self = new static();
        $self->configFile = Config::module($moduleName)->getConfigFile();
        $self->resourceConfigFile = Config::app('resources')->getConfigFile();

        return $self;
    }

    /**
     * @param int|float $interval
     * @return $this
     */
    public function setInterval($interval)
    {
        if (! \is_int($interval) && ! \is_float($interval)) {
            throw new InvalidArgumentException(
                '$interval needs to be either int or float'
            );
        }
        $this->interval = $interval;

        return $this;
    }

    /**
     * @param callable $callable
     * @return $this
     */
    public function notify($callable)
    {
        if (! \is_callable($callable)) {
            throw new InvalidArgumentException('$callable needs to be callable');
        }
        $this->callbacks[] = $callable;

        return $this;
    }

    /**
     * @param LoopInterface $loop
     */
    public function run(LoopInterface $loop)
    {
        $check = function () {
            $this->checkForFreshConfig();
        };
        $loop->addPeriodicTimer($this->interval, $check);
        $loop->futureTick($check);
    }

    protected function checkForFreshConfig()
    {
        if ($this->configHasBeenChanged()) {
            $this->emitNewConfig($this->resourceConfig);
        }
    }

    protected function emitNewConfig($config)
    {
        foreach ($this->callbacks as $callback) {
            $callback($config);
        }
    }

    protected function getResourceName()
    {
        if ($this->dbResourceName) {
            return $this->dbResourceName;
        } else {
            return $this->loadDbResourceName();
        }
    }

    protected function loadDbResourceName()
    {
        $parsed = @\parse_ini_file($this->configFile, true);
        if (isset($parsed['db']['resource'])) {
            return $parsed['db']['resource'];
        } else {
            return null;
        }
    }

    protected function loadDbConfigFromDisk($name)
    {
        if ($name === null) {
            return null;
        }

        $parsed = @\parse_ini_file($this->resourceConfigFile, true);
        if (isset($parsed[$name])) {
            $section = $parsed[$name];
            \ksort($section);

            return $section;
        } else {
            return null;
        }
    }

    protected function configHasBeenChanged()
    {
        $resource = $this->loadDbConfigFromDisk($this->loadDbResourceName());
        if ($resource !== $this->resourceConfig) {
            $this->resourceConfig = $resource;

            return true;
        } else {
            return false;
        }
    }
}
