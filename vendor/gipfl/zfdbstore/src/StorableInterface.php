<?php

namespace gipfl\ZfDbStore;

interface StorableInterface
{
    public function isNew();

    public function getKey();

    public function getKeyProperty();

    public static function create(array $properties = []);

    public static function load(Store $store, $key);

    public function get($property);

    public function getProperties(array $properties = null);

    public function hasProperty($property);

    public function getModifiedProperties();

    public function getStoredProperties();

    public function set($property, $value);

    public function setStoredProperty($property, $value);

    public function setProperties(array $properties);

    public function setStoredProperties(array $properties);

    public function assertPropertyExists($property);

    public function isModified();

    public function getDefaultProperties();

    public function listModifiedProperties();

    public function setStored();
}
