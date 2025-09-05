<?php

namespace gipfl\DbMigration;

use Exception;
use gipfl\ZfDb\Adapter\Pdo\PdoAdapter as Db;
use InvalidArgumentException;
use RuntimeException;
use Zend_Db_Adapter_Pdo_Abstract as ZfDb;

class Migration
{
    /**
     * @var string
     */
    protected $sql;

    /**
     * @var int
     */
    protected $version;

    public function __construct($version, $sql)
    {
        $this->version = $version;
        $this->sql     = $sql;
    }

    /**
     * @param Db|ZfDb $db
     * @return $this
     */
    public function apply($db)
    {
        if (! ($db instanceof Db || $db instanceof ZfDb)) {
            throw new InvalidArgumentException('$db must be an valid Zend_Db PDO adapter');
        }
        // TODO: this is fragile and depends on accordingly written schema files:
        $sql = preg_replace('/-- .*$/m', '', $this->sql);
        $queries = preg_split(
            '/[\n\s\t]*;[\n\s\t]+/s',
            $sql,
            -1,
            PREG_SPLIT_NO_EMPTY
        );

        if (empty($queries)) {
            throw new RuntimeException(sprintf(
                'Migration %d has no queries',
                $this->version
            ));
        }

        try {
            foreach ($queries as $query) {
                if (preg_match('/^(?:OPTIMIZE|EXECUTE) /i', $query)) {
                    $db->query($query);
                } else {
                    $db->exec($query);
                }
            }
        } catch (Exception $e) {
            throw new RuntimeException(sprintf(
                'Migration %d failed (%s) while running %s',
                $this->version,
                $e->getMessage(),
                $query
            ));
        }

        return $this;
    }
}
