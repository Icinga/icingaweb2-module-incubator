<?php

namespace gipfl\DbMigration;

use DirectoryIterator;
use Exception;
use gipfl\ZfDb\Adapter\Exception\AdapterException;
use gipfl\ZfDb\Adapter\Adapter as Db;
use gipfl\ZfDb\Adapter\Pdo\Mysql;
use gipfl\ZfDb\Adapter\Pdo\Pgsql;
use InvalidArgumentException;
use Zend_Db_Adapter_Pdo_Abstract as ZfDb;
use Zend_Db_Adapter_Pdo_Mysql as ZfMysql;
use Zend_Db_Adapter_Pdo_Pgsql as ZfPgsql;

class Migrations
{
    const DB_TYPE_MYSQL = 'mysql';

    const DB_TYPE_POSTGRESQL = 'pgsql';

    /** @var Db */
    protected $db;

    /** @var string mysql or pgsql */
    protected $dbType;

    /** @var string */
    protected $schemaDirectory;

    /** @var string */
    protected $tableName;

    /**
     * Migrations constructor.
     *
     * @param Mysql|Pgsql $db
     * @param string $schemaDirectory
     * @param string $tableName
     */
    public function __construct($db, $schemaDirectory, $tableName = 'schema_migration')
    {
        if (! ($db instanceof Db || $db instanceof ZfDb)) {
            throw new InvalidArgumentException('$db must be an valid Zend_Db PDO adapter');
        }
        $this->db = $db;
        if ($db instanceof Mysql || $db instanceof ZfMysql) {
            $this->dbType = self::DB_TYPE_MYSQL;
        } elseif ($db instanceof Pgsql || $db instanceof ZfPgsql) {
            $this->dbType = self::DB_TYPE_POSTGRESQL;
        } else {
            throw new InvalidArgumentException(sprintf(
                'Migrations are currently supported for MySQL and PostgreSQL only, got %s',
                get_class($db)
            ));
        }
        $this->tableName = (string) $tableName;
        $this->schemaDirectory = (string) $schemaDirectory;
    }

    /**
     * Still unused
     *
     * @throws AdapterException|\Zend_Db_Adapter_Exception
     */
    protected function createMigrationsTable()
    {
        if ($this->dbType === self::DB_TYPE_POSTGRESQL) {
            $create = /** @lang text */
                <<<SQL

CREATE TABLE {$this->tableName} (
  schema_version SMALLINT NOT NULL,
  migration_time TIMESTAMP WITH TIME ZONE NOT NULL,
  PRIMARY KEY (schema_version)
);

SQL;
        } else {
            $create = /** @lang text */
                <<<SQL
CREATE TABLE {$this->tableName} (
  schema_version SMALLINT UNSIGNED NOT NULL,
  migration_time DATETIME NOT NULL,
  PRIMARY KEY (schema_version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;
SQL;
        }
        $this->db->exec($create);
    }

    /**
     * @return int
     */
    public function getLastMigrationNumber()
    {
        try {
            $query = $this->db->select()->from(
                ['m' => $this->getTableName()],
                ['schema_version' => 'MAX(schema_version)']
            );

            return (int) $this->db->fetchOne($query);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * @return string
     */
    protected function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @return bool
     */
    public function hasAnyTable()
    {
        return count($this->db->listTables()) > 0;
    }

    /**
     * @return bool
     */
    public function hasTable($tableName)
    {
        return in_array($tableName, $this->db->listTables());
    }

    /**
     * @return bool
     */
    public function hasMigrationsTable()
    {
        return $this->hasTable($this->tableName);
    }

    /**
     * @return bool
     */
    public function hasSchema()
    {
        return $this->listPendingMigrations() !== [0];
    }

    /**
     * @return bool
     */
    public function hasPendingMigrations()
    {
        return $this->countPendingMigrations() > 0;
    }

    /**
     * @return int
     */
    public function countPendingMigrations()
    {
        return count($this->listPendingMigrations());
    }

    /**
     * @return Migration[]
     */
    public function getPendingMigrations()
    {
        $migrations = array();
        foreach ($this->listPendingMigrations() as $version) {
            $migrations[] = new Migration(
                $version,
                $this->loadMigrationFile($version)
            );
        }

        return $migrations;
    }

    /**
     * @return $this
     */
    public function applyPendingMigrations()
    {
        foreach ($this->getPendingMigrations() as $migration) {
            $migration->apply($this->db);
        }

        return $this;
    }

    /**
     * @return int[]
     */
    public function listPendingMigrations()
    {
        $lastMigration = $this->getLastMigrationNumber();
        if ($lastMigration === 0) {
            return [0];
        }

        return $this->listMigrationsAfter($this->getLastMigrationNumber());
    }

    /**
     * @return int[]
     */
    public function listAllMigrations()
    {
        $dir = $this->getMigrationsDirectory();
        $versions = [];

        if (! is_readable($dir)) {
            return $versions;
        }

        foreach (new DirectoryIterator($dir) as $file) {
            if ($file->isDot()) {
                continue;
            }

            $filename = $file->getFilename();
            if (preg_match('/^upgrade_(\d+)\.sql$/', $filename, $match)) {
                $versions[] = (int) $match[1];
            }
        }

        sort($versions);

        return $versions;
    }

    /**
     * @param $version
     * @return false|string
     */
    public function loadMigrationFile($version)
    {
        if ($version === 0) {
            $filename = $this->getFullSchemaFile();
        } else {
            $filename = sprintf(
                '%s/upgrade_%d.sql',
                $this->getMigrationsDirectory(),
                $version
            );
        }

        return file_get_contents($filename);
    }

    /**
     * @param $version
     * @return int[]
     */
    protected function listMigrationsAfter($version)
    {
        $filtered = [];
        foreach ($this->listAllMigrations() as $available) {
            if ($available > $version) {
                $filtered[] = $available;
            }
        }

        return $filtered;
    }

    /**
     * @param ?string $subDirectory
     * @return string
     */
    public function getSchemaDirectory($subDirectory = null)
    {
        if ($subDirectory === null) {
            return $this->schemaDirectory;
        } else {
            return $this->schemaDirectory . '/' . ltrim($subDirectory, '/');
        }
    }

    /**
     * @return string
     */
    public function getMigrationsDirectory()
    {
        return $this->getSchemaDirectory($this->dbType . '-migrations');
    }

    /**
     * @return string
     */
    protected function getFullSchemaFile()
    {
        return $this->getSchemaDirectory(
            $this->dbType. '.sql'
        );
    }
}
