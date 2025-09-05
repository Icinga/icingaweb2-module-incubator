<?php

namespace gipfl\ZfDbStore;

use RuntimeException;

/**
 * DbStorable
 *
 * This trait provides all you need to create an object implementing the
 * DbStorableInterface
 */
trait DbStorable
{
    use Storable;

    // protected $tableName;

    public function getTableName()
    {
        if (isset($this->tableName)) {
            return $this->tableName;
        } else {
            throw new RuntimeException('A DbStorable needs a tableName');
        }
    }

    public function hasAutoIncKey()
    {
        return $this->getAutoIncKeyName() !== null;
    }

    public function getAutoIncKeyName()
    {
        if (isset($this->autoIncKeyName)) {
            return $this->autoIncKeyName;
        } else {
            return null;
        }
    }

    protected function requireAutoIncKeyName()
    {
        $key = $this->getAutoIncKeyName();
        if ($key === null) {
            throw new RuntimeException('This DbStorable has no autoinc key');
        }

        return $key;
    }

    public function getAutoIncId()
    {
        $key = $this->requireAutoIncKeyName();
        if (isset($this->properties[$key])) {
            return (int) $this->properties[$key];
        }

        return null;
    }

    protected function forgetAutoIncId()
    {
        $key = $this->requireAutoIncKeyName();
        if (isset($this->properties[$key])) {
            $this->properties[$key] = null;
        }

        return $this;
    }

    public function __invoke($properties = [])
    {
        $storable = new static();
        $storable->setProperties($properties);

        return $storable;
    }
}
