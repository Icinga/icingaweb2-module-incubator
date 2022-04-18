<?php

namespace gipfl\ZfDbStore;

use InvalidArgumentException;
use RuntimeException;

/**
 * Trait Storable
 *
 * This trait implements a generic trait used for storing
 * information about users activity (i.e. creation of new elements,
 * update/delete existing records)
 *
 * Each storable is characterized by:
 * - $defaultProperties (array of properties set by default)
 * - $modifiedProperties (array of properties modified by the user)
 * - $storedProperties (array of properties loaded from the DB)
 * - key property represents the primary key in the DB
 */
trait Storable
{
    /** @var null|array */
    protected $storedProperties;

    ///** @var array  */
    //protected $defaultProperties = [];

    /** @var array  */
    protected $modifiedProperties = [];

    /** @var array */
    protected $properties = [];

    ///** @var string|array */
    //protected $keyProperty;

    /**
     * If a $storable has no stored properties it means that
     * it is a new element -> the user is creating it right now
     *
     * @return bool
     */
    public function isNew()
    {
        return null === $this->storedProperties;
    }

    /**
     * This function returns the key property (it can be an array of properties) of the $storable
     * i.e. it returns the primary key in the case of DB object
     *
     * @return array|mixed
     */
    public function getKey()
    {
        $property = $this->getKeyProperty();
        if (is_string($property)) {
            return $this->get($property);
        } else {
            return $this->getProperties($property);
        }
    }

    /**
     * @return string|array
     */
    public function getKeyProperty()
    {
        if (isset($this->keyProperty)) {
            return $this->keyProperty;
        } else {
            throw new RuntimeException('A storable needs a key property.');
        }
    }

    /**
     * Create a $storable setting its properties
     *
     * @param array $properties
     * @return static
     */
    public static function create(array $properties = [])
    {
        $storable = new static();
        $storable->properties = $storable->getDefaultProperties();
        $storable->setProperties($properties);

        return $storable;
    }

    /**
     * Loads an already existing $storable
     *
     * @param Store $store
     * @param $key
     * @return mixed
     */
    public static function load(Store $store, $key)
    {
        return $store->load($key, get_called_class());
        return $store->load(get_called_class(), $key);
    }

    /**
     * Returns the value of $property (if this property exists)
     *
     * @param $property
     * @return mixed
     */
    public function get($property, $default = null)
    {
        $this->assertPropertyExists($property);

        if (array_key_exists($property, $this->properties)) {
            if ($this->properties[$property] === null) {
                return $default;
            } else {
                return $this->properties[$property];
            }
        } else {
            return $default;
        }
    }

    /**
     * Returns the array of values corresponding to the requested array of properties
     *
     * @param array|null $properties
     * @return array
     */
    public function getProperties(array $properties = null)
    {
        if ($properties === null) {
            $properties = array_keys($this->properties);
        }

        $result = [];
        foreach ($properties as $property) {
            $result[$property] = $this->get($property);
        }

        return $result;
    }

    /**
     * Returns the array of properties modified by the user
     *
     * @return array
     */
    public function getModifiedProperties()
    {
        return $this->getProperties($this->listModifiedProperties());
    }

    /**
     * Returns the array of stored properties
     * It can be used only in case of already existing $storable
     */
    public function getStoredProperties()
    {
        if ($this->isNew()) {
            throw new RuntimeException(
                'Trying to access stored properties of an unstored Storable'
            );
        }

        return $this->storedProperties;
    }

    /**
     * Set the value of a given property
     *
     * @param $property
     * @param $value
     * @return bool
     */
    public function set($property, $value)
    {
        $this->assertPropertyExists($property);

        if ($value === $this->get($property)) {
            return false;
        }

        $this->properties[$property] = $value;

        if ($this->storedProperties !== null && $this->storedProperties[$property] === $value) {
            $this->resetModifiedProperty($property);
        } else {
            $this->setModifiedProperty($property);
        }

        return true;
    }

    /**
     * Initialize the stored property at the first loading of the $storable element
     *
     * @param $property
     * @param $value
     */
    public function setStoredProperty($property, $value)
    {
        $this->assertPropertyExists($property);

        $this->storedProperties[$property] = $value;
        $this->properties[$property] = $value;
        unset($this->modifiedProperties[$property]);
    }

    /**
     * Set array of values for the given array of properties
     *
     * @param array $properties
     * @return $this
     */
    public function setProperties(array $properties)
    {
        foreach ($properties as $property => $value) {
            $this->set($property, $value);
        }

        return $this;
    }

    /**
     * Initialize the stored property array
     *
     * @param array $properties
     * @return $this
     */
    public function setStoredProperties(array $properties)
    {
        foreach ($properties as $property => $value) {
            $this->setStoredProperty($property, $value);
        }

        return $this;
    }

    /**
     * @param $property
     */
    public function assertPropertyExists($property)
    {
        if (! $this->hasProperty($property)) {
            throw new InvalidArgumentException(sprintf(
                "Trying to access invalid property '%s'",
                $property
            ));
        }
    }

    /**
     * @param $property
     * @return bool
     */
    public function hasProperty($property)
    {
        return array_key_exists($property, $this->defaultProperties);
    }

    /**
     * @param $property
     */
    private function setModifiedProperty($property)
    {
        $this->modifiedProperties[$property] = true;
    }

    /**
     * @param $property
     */
    private function resetModifiedProperty($property)
    {
        unset($this->modifiedProperties[$property]);
    }

    /**
     * Check if $storable has changed,
     * if not the $modifiedProperties array is empty
     *
     * @return bool
     */
    public function isModified()
    {
        return !empty($this->modifiedProperties);
    }

    /**
     * @return mixed
     */
    public function getDefaultProperties()
    {
        if (isset($this->defaultProperties)) {
            return $this->defaultProperties;
        } else {
            throw new RuntimeException('A storable needs default properties.');
        }
    }

    /**
     * Get the array key of the modifies properties
     *
     * @return array
     */
    public function listModifiedProperties()
    {
        return array_keys($this->modifiedProperties);
    }

    /**
     * @return $this
     */
    public function setStored()
    {
        $this->storedProperties = $this->properties;
        $this->modifiedProperties = [];

        return $this;
    }
}
