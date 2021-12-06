<?php

namespace gipfl\ZfDbStore;

use Evenement\EventEmitterTrait;
use RuntimeException;

/**
 * Class BaseStore
 *
 * This class handles creation/update/delete of $storable
 * and save records them into a log table in the DB
 */
abstract class BaseStore implements Store
{
    use EventEmitterTrait;

    /**
     * If a new element is created, it stores it into the DB
     * and emits the "insert" event, in order to create the corresponding activity log
     *
     * If an element is updated, it updates the DB record and
     * emits the "update" event
     *
     * @param StorableInterface $storable
     * @return bool Whether the store() applied any change to the stored object
     */
    public function store(StorableInterface $storable)
    {
        $affected = false;

        if ($storable->isNew()) {
            $this->insertIntoStore($storable);
            $this->emit('insert', [$storable]);
            $affected = true;
        } else {
            if ($this->updateStore($storable)) {
                $this->emit('update', [$storable]);
                $affected = true;
            }
        }
        $storable->setStored();

        return $affected;
    }

    /**
     * If a new element is deleted, it deletes the record from the DB
     * and emits the "delete" event, in order to create the corresponding activity log
     *
     * @param StorableInterface $storable
     * @return bool
     */
    public function delete(StorableInterface $storable)
    {
        if ($this->deleteFromStore($storable)) {
            $this->emit('delete', [$storable]);
            return true;
        }

        return false;
    }

    /**
     * Loads $storable by it's key property/properties
     *
     * @param StorableInterface $storable
     * @param $key
     * @return StorableInterface
     */
    abstract protected function loadFromStore(StorableInterface $storable, $key);

    /**
     * Deletes this record from the store
     *
     * @param StorableInterface $storable
     */
    abstract protected function deleteFromStore(StorableInterface $storable);

    /**
     * Inserts the $storable, refreshes the object in case storing implies
     * changes (like auto-incremental IDs)
     *
     * @param StorableInterface $storable
     * @return bool
     * @throws \gipfl\ZfDb\Adapter\Exception\AdapterException
     * @throws \gipfl\ZfDb\Statement\Exception\StatementException
     * @throws \Zend_Db_Adapter_Exception
     */
    abstract protected function insertIntoStore(StorableInterface $storable);
    abstract protected function updateStore(StorableInterface $storable);

    /**
     * Load $storable from DB
     *
     * @param array|string $key
     * @param string $className
     * @return StorableInterface
     */
    public function load($key, $className)
    {
        $storable = new $className();
        if ($storable instanceof StorableInterface) {
            $storable = $storable::create();
            return $this->loadFromStore($storable, $key);
        } else {
            throw new RuntimeException(
                get_class($this) . "::load: '$className' is not a StorableInterface implementation"
            );
        }
    }
}
