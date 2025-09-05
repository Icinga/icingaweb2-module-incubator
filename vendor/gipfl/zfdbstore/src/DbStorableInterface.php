<?php

namespace gipfl\ZfDbStore;

interface DbStorableInterface extends StorableInterface
{
    /**
     * The table where this Storable will be loaded from and stored to
     *
     * @return string
     */
    public function getTableName();

    /**
     * Whether this Storable has an auto-incrementing key column
     * @return bool
     */
    public function hasAutoIncKey();

    /**
     * Returns the name of the auto-incrementing key column
     *
     * @return string
     */
    public function getAutoIncKeyName();

    /**
     * Get the AutoInc value if set
     *
     * Should throw and Exception in case no such key has been defined. This
     * will return null for unstored DbStorables
     *
     * @return int|null
     */
    public function getAutoIncId();
}
