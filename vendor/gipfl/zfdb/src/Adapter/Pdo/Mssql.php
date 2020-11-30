<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */
namespace gipfl\ZfDb\Adapter\Pdo;

use gipfl\ZfDb\Adapter\Exception\AdapterException;
use gipfl\ZfDb\Db;
use PDOException;

/**
 * Class for connecting to Microsoft SQL Server databases and performing common operations.
 *
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Mssql extends PdoAdapter
{
    /**
     * PDO type.
     *
     * @var string
     */
    protected $_pdoType = 'mssql';

    /**
     * Keys are UPPERCASE SQL datatypes or the constants
     * Zend_Db::INT_TYPE, Zend_Db::BIGINT_TYPE, or Zend_Db::FLOAT_TYPE.
     *
     * Values are:
     * 0 = 32-bit integer
     * 1 = 64-bit integer
     * 2 = float or decimal
     *
     * @var array Associative array of datatypes to values 0, 1, or 2.
     */
    protected $_numericDataTypes = array(
        Db::INT_TYPE    => Db::INT_TYPE,
        Db::BIGINT_TYPE => Db::BIGINT_TYPE,
        Db::FLOAT_TYPE  => Db::FLOAT_TYPE,
        'INT'           => Db::INT_TYPE,
        'SMALLINT'      => Db::INT_TYPE,
        'TINYINT'       => Db::INT_TYPE,
        'BIGINT'        => Db::BIGINT_TYPE,
        'DECIMAL'       => Db::FLOAT_TYPE,
        'FLOAT'         => Db::FLOAT_TYPE,
        'MONEY'         => Db::FLOAT_TYPE,
        'NUMERIC'       => Db::FLOAT_TYPE,
        'REAL'          => Db::FLOAT_TYPE,
        'SMALLMONEY'    => Db::FLOAT_TYPE
    );

    /**
     * Creates a PDO DSN for the adapter from $this->_config settings.
     *
     * @return string
     */
    protected function _dsn()
    {
        // baseline of DSN parts
        $dsn = $this->_config;

        // don't pass the username and password in the DSN
        unset($dsn['username']);
        unset($dsn['password']);
        unset($dsn['options']);
        unset($dsn['persistent']);
        unset($dsn['driver_options']);

        if (isset($dsn['port'])) {
            $seperator = ':';
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $seperator = ',';
            }
            $dsn['host'] .= $seperator . $dsn['port'];
            unset($dsn['port']);
        }

        // this driver supports multiple DSN prefixes
        // @see http://www.php.net/manual/en/ref.pdo-dblib.connection.php
        if (isset($dsn['pdoType'])) {
            switch (strtolower($dsn['pdoType'])) {
                case 'freetds':
                case 'sybase':
                    $this->_pdoType = 'sybase';
                    break;
                case 'mssql':
                    $this->_pdoType = 'mssql';
                    break;
                case 'dblib':
                default:
                    $this->_pdoType = 'dblib';
                    break;
            }
            unset($dsn['pdoType']);
        }

        // use all remaining parts in the DSN
        foreach ($dsn as $key => $val) {
            $dsn[$key] = "$key=$val";
        }

        $dsn = $this->_pdoType . ':' . implode(';', $dsn);
        return $dsn;
    }

    /**
     * @return void
     */
    protected function _connect()
    {
        if ($this->_connection) {
            return;
        }
        parent::_connect();
        $this->_connection->exec('SET QUOTED_IDENTIFIER ON');
    }

    /**
     * Begin a transaction.
     *
     * It is necessary to override the abstract PDO transaction functions here, as
     * the PDO driver for MSSQL does not support transactions.
     */
    protected function _beginTransaction()
    {
        $this->_connect();
        $this->_connection->exec('BEGIN TRANSACTION');
        return true;
    }

    /**
     * Commit a transaction.
     *
     * It is necessary to override the abstract PDO transaction functions here, as
     * the PDO driver for MSSQL does not support transactions.
     */
    protected function _commit()
    {
        $this->_connect();
        $this->_connection->exec('COMMIT TRANSACTION');
        return true;
    }

    /**
     * Roll-back a transaction.
     *
     * It is necessary to override the abstract PDO transaction functions here, as
     * the PDO driver for MSSQL does not support transactions.
     */
    protected function _rollBack()
    {
        $this->_connect();
        $this->_connection->exec('ROLLBACK TRANSACTION');
        return true;
    }

    /**
     * Returns a list of the tables in the database.
     *
     * @return array
     */
    public function listTables()
    {
        $sql = "SELECT name FROM sysobjects WHERE type = 'U' ORDER BY name";
        return $this->fetchCol($sql);
    }

    /**
     * Returns the column descriptions for a table.
     *
     * The return value is an associative array keyed by the column name,
     * as returned by the RDBMS.
     *
     * The value of each array element is an associative array
     * with the following keys:
     *
     * SCHEMA_NAME      => string; name of database or schema
     * TABLE_NAME       => string;
     * COLUMN_NAME      => string; column name
     * COLUMN_POSITION  => number; ordinal position of column in table
     * DATA_TYPE        => string; SQL datatype name of column
     * DEFAULT          => string; default expression of column, null if none
     * NULLABLE         => boolean; true if column can have nulls
     * LENGTH           => number; length of CHAR/VARCHAR
     * SCALE            => number; scale of NUMERIC/DECIMAL
     * PRECISION        => number; precision of NUMERIC/DECIMAL
     * UNSIGNED         => boolean; unsigned property of an integer type
     * PRIMARY          => boolean; true if column is part of the primary key
     * PRIMARY_POSITION => integer; position of column in primary key
     * PRIMARY_AUTO     => integer; position of auto-generated column in primary key
     *
     * @todo Discover column primary key position.
     * @todo Discover integer unsigned property.
     *
     * @param string $tableName
     * @param string $schemaName OPTIONAL
     * @return array
     */
    public function describeTable($tableName, $schemaName = null)
    {
        if ($schemaName != null) {
            if (strpos($schemaName, '.') !== false) {
                $result = explode('.', $schemaName);
                $schemaName = $result[1];
            }
        }
        /**
         * Discover metadata information about this table.
         */
        $sql = "exec sp_columns @table_name = " . $this->quoteIdentifier($tableName, true);
        if ($schemaName != null) {
            $sql .= ", @table_owner = " . $this->quoteIdentifier($schemaName, true);
        }

        $stmt = $this->query($sql);
        $result = $stmt->fetchAll(Db::FETCH_NUM);

        $table_name  = 2;
        $column_name = 3;
        $type_name   = 5;
        $precision   = 6;
        $length      = 7;
        $scale       = 8;
        $nullable    = 10;
        $column_def  = 12;
        $column_position = 16;

        /**
         * Discover primary key column(s) for this table.
         */
        $sql = "exec sp_pkeys @table_name = " . $this->quoteIdentifier($tableName, true);
        if ($schemaName != null) {
            $sql .= ", @table_owner = " . $this->quoteIdentifier($schemaName, true);
        }

        $stmt = $this->query($sql);
        $primaryKeysResult = $stmt->fetchAll(Db::FETCH_NUM);
        $primaryKeyColumn = array();
        $pkey_column_name = 3;
        $pkey_key_seq = 4;
        foreach ($primaryKeysResult as $pkeysRow) {
            $primaryKeyColumn[$pkeysRow[$pkey_column_name]] = $pkeysRow[$pkey_key_seq];
        }

        $desc = array();
        $p = 1;
        foreach ($result as $key => $row) {
            $identity = false;
            $words = explode(' ', $row[$type_name], 2);
            if (isset($words[0])) {
                $type = $words[0];
                if (isset($words[1])) {
                    $identity = (bool) preg_match('/identity/', $words[1]);
                }
            }

            $isPrimary = array_key_exists($row[$column_name], $primaryKeyColumn);
            if ($isPrimary) {
                $primaryPosition = $primaryKeyColumn[$row[$column_name]];
            } else {
                $primaryPosition = null;
            }

            $desc[$this->foldCase($row[$column_name])] = array(
                'SCHEMA_NAME'      => null, // @todo
                'TABLE_NAME'       => $this->foldCase($row[$table_name]),
                'COLUMN_NAME'      => $this->foldCase($row[$column_name]),
                'COLUMN_POSITION'  => (int) $row[$column_position],
                'DATA_TYPE'        => $type,
                'DEFAULT'          => $row[$column_def],
                'NULLABLE'         => (bool) $row[$nullable],
                'LENGTH'           => $row[$length],
                'SCALE'            => $row[$scale],
                'PRECISION'        => $row[$precision],
                'UNSIGNED'         => null, // @todo
                'PRIMARY'          => $isPrimary,
                'PRIMARY_POSITION' => $primaryPosition,
                'IDENTITY'         => $identity
            );
        }
        return $desc;
    }

    /**
     * Adds an adapter-specific LIMIT clause to the SELECT statement.
     *
     * @link http://lists.bestpractical.com/pipermail/rt-devel/2005-June/007339.html
     *
     * @param string $sql
     * @param integer $count
     * @param integer $offset OPTIONAL
     * @throws AdapterException
     * @return string
     */
    public function limit($sql, $count, $offset = 0)
    {
        $count = intval($count);
        if ($count <= 0) {
            throw new AdapterException("LIMIT argument count=$count is not valid");
        }

        $offset = intval($offset);
        if ($offset < 0) {
            throw new AdapterException("LIMIT argument offset=$offset is not valid");
        }

        $sql .= "OFFSET $offset ROWS FETCH NEXT $count ROWS ONLY";

        return $sql;
    }

    /**
     * Gets the last ID generated automatically by an IDENTITY/AUTOINCREMENT column.
     *
     * As a convention, on RDBMS brands that support sequences
     * (e.g. Oracle, PostgreSQL, DB2), this method forms the name of a sequence
     * from the arguments and returns the last id generated by that sequence.
     * On RDBMS brands that support IDENTITY/AUTOINCREMENT columns, this method
     * returns the last value generated for such a column, and the table name
     * argument is disregarded.
     *
     * Microsoft SQL Server does not support sequences, so the arguments to
     * this method are ignored.
     *
     * @param string $tableName   OPTIONAL Name of table.
     * @param string $primaryKey  OPTIONAL Name of primary key column.
     * @return string
     * @throws AdapterException
     */
    public function lastInsertId($tableName = null, $primaryKey = null)
    {
        $sql = 'SELECT SCOPE_IDENTITY()';
        return (int)$this->fetchOne($sql);
    }

    /**
     * Retrieve server version in PHP style
     * Pdo_Mssql doesn't support getAttribute(PDO::ATTR_SERVER_VERSION)
     * @return string
     */
    public function getServerVersion()
    {
        try {
            $stmt = $this->query("SELECT CAST(SERVERPROPERTY('productversion') AS VARCHAR)");
            $result = $stmt->fetchAll(Db::FETCH_NUM);
            if (count($result)) {
                return $result[0][0];
            }
            return null;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Quote a raw string.
     *
     * @param string $value     Raw string
     * @return string           Quoted string
     */
    protected function _quote($value)
    {
        if (!is_int($value) && !is_float($value)) {
            // Fix for null-byte injection
            $value = addcslashes($value, "\000\032");
        }
        return parent::_quote($value);
    }
}
