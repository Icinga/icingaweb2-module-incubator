<?php

namespace gipfl\ZfDbStore;

interface Store
{
    /**
     * Function used for saving changes and log the activity.
     * To be extended as needed (see BaseStore.php)
     *
     * @param StorableInterface $storable
     * @return mixed
     */
    public function store(StorableInterface $storable);

    /**
     * @param array|string $key
     * @param string $className
     * @return StorableInterface
     */
    public function load($key, $className);
    public function exists(StorableInterface $storable);
    public function delete(StorableInterface $storable);
}
