<?php

namespace gipfl\ZfDbStore;

use InvalidArgumentException;
use RuntimeException;
use gipfl\ZfDb\Adapter\Adapter;
use Zend_Db_Adapter_Abstract as ZfDb;
use function array_key_exists;
use function assert;
use function implode;
use function is_array;
use function is_string;
use function method_exists;

/**
 * Class DbStore
 *
 * Extends BaseStore for DB object
 */
class ZfDbStore extends BaseStore
{
    /** @var Adapter|ZfDb */
    protected $db;

    /**
     * ZfDbStore constructor.
     * @param Adapter|ZfDb $db
     */
    public function __construct($db)
    {
        if ($db instanceof Adapter || $db instanceof ZfDb) {
            $this->db = $db;
        } else {
            throw new InvalidArgumentException('ZfDb Adapter is required');
        }
    }

    /**
     * @return Adapter|ZfDb
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * Checks whether the passed $storable already exists in the DB
     *
     * @param DbStorableInterface $storable
     * @return bool
     */
    public function exists(StorableInterface $storable)
    {
        return (int) $this->db->fetchOne(
            $this->db
                ->select()
                ->from($this->getTableName($storable), '(1)')
                ->where($this->createWhere($storable))
        ) === 1;
    }

    /**
     * @param DbStorableInterface $storable
     * @param string|null $keyColumn
     * @param string|null $labelColumn
     * @return array
     */
    public function enum(StorableInterface $storable, $keyColumn = null, $labelColumn = null)
    {
        assert($storable instanceof DbStorableInterface);
        if ($keyColumn === null) {
            $key = $storable->getKeyProperty();
            if (is_array($key)) {
                if ($storable->hasAutoIncKey()) {
                    $key = $storable->getAutoIncKeyName();
                } else {
                    throw new InvalidArgumentException(
                        'Cannot provide an enum for a multi-key column'
                    );
                }
            }
        } else {
            $key = $keyColumn;
        }

        if ($labelColumn === null) {
            if (method_exists($storable, 'getDisplayColumn')) {
                $label = $storable->getDisplayColumn();
            } else {
                $label = $storable->getKeyProperty();
                if (is_array($label)) {
                    $label = $key;
                }
            }
        } else {
            $label = $labelColumn;
        }

        $columns = [
            'key_col'   => $key,
            'label_col' => $label
        ];

        $query = $this->db->select()->from(
            $this->getTableName($storable),
            $columns
        );

        return $this->db->fetchPairs($query);
    }

    protected function insertIntoStore(StorableInterface $storable)
    {
        assert($storable instanceof DbStorableInterface);
        $result = $this->db->insert(
            $this->getTableName($storable),
            $storable->getProperties()
        );
        /** @var DbStorable $storable */
        if ($storable->hasAutoIncKey()) {
            $storable->set(
                $storable->getAutoIncKeyName(),
                $this->db->lastInsertId($this->getTableName($storable))
            );
        }

        return $result > 0;
    }

    protected function updateStore(StorableInterface $storable)
    {
        assert($storable instanceof DbStorableInterface);
        $this->db->update(
            $this->getTableName($storable),
            $storable->getProperties(),
            $this->createWhere($storable)
        );

        return true;
    }

    protected function deleteFromStore(StorableInterface $storable)
    {
        assert($storable instanceof DbStorableInterface);
        return $this->db->delete(
            $this->getTableName($storable),
            $this->createWhere($storable)
        );
    }

    protected function loadFromStore(StorableInterface $storable, $key)
    {
        assert($storable instanceof DbStorableInterface);
        $keyColumn = $storable->getKeyProperty();
        $select = $this->db->select()->from($this->getTableName($storable));

        if (is_string($keyColumn)) {
            $select->where("$keyColumn = ?", $key);
        } else {
            foreach ($keyColumn as $column) {
                if (array_key_exists($column, $key)) {
                    $select->where("$column = ?", $key[$column]);
                } else {
                    throw new RuntimeException('Multicolumn key required, got no %s', $column);
                }
            }
        }

        // TODO: fetchAll, fail when no or more than one row
        $result = $this->db->fetchRow($select);
        // TODO: properties should be changed in storeProperties
        // when you load the element from db before changing it.
        if ($result === false) {
            // TODO: NotFoundException, key infos
            throw new RuntimeException('Key not found' . $select);
        }

        $storable->setProperties((array) $result);
        $storable->setStoredProperties((array) $result);

        return $storable;
    }

    /**
     * Returns $storable table name
     *
     * @param DbStorableInterface $storable
     * @return string
     */
    protected function getTableName(DbStorableInterface $storable)
    {
        return $storable->getTableName();
    }

    /**
     * @param DbStorableInterface $storable
     * @return string
     */
    protected function createWhere($storable)
    {
        $where = [];
        foreach ((array) $storable->getKeyProperty() as $key) {
            $value = $storable->get($key);
            // TODO, eventually:
            // $key = $this->db->quoteIdentifier($key);
            if ($value === null) {
                $where[] = "$key IS NULL";
            } else {
                $where[] = $this->db->quoteInto("$key = ?", $value);
            }
        }

        return implode(' AND ', $where);
    }
}
