<?php

namespace gipfl\IcingaWeb2\Zf1\Db;

use gipfl\IcingaWeb2\Data\Paginatable;
use Icinga\Application\Benchmark;
use RuntimeException;
use Zend_Db_Select as ZfSelect;
use Zend_Db_Select_Exception as ZfDbSelectException;

class SelectPaginationAdapter implements Paginatable
{
    private $query;

    private $countQuery;

    private $cachedCount;

    private $cachedCountQuery;

    public function __construct(ZfSelect $query)
    {
        $this->query = $query;
    }

    public function getCountQuery()
    {
        if ($this->countQuery === null) {
            $this->countQuery = (new CountQuery($this->query))->getQuery();
        }

        return $this->countQuery;
    }

    public function count()
    {
        $queryString = (string) $this->getCountQuery();
        if ($this->cachedCountQuery !== $queryString) {
            Benchmark::measure('Running count() for pagination');
            $this->cachedCountQuery = $queryString;
            $count = $this->query->getAdapter()->fetchOne(
                $queryString
            );
            $this->cachedCount = $count;
            Benchmark::measure("Counted $count rows");
        }

        return $this->cachedCount;
    }

    public function limit($count = null, $offset = null)
    {
        $this->query->limit($count, $offset);
    }

    public function hasLimit()
    {
        return $this->getLimit() !== null;
    }

    public function getLimit()
    {
        return $this->getQueryPart(ZfSelect::LIMIT_COUNT);
    }

    public function setLimit($limit)
    {
        $this->query->limit(
            $limit,
            $this->getOffset()
        );
    }

    public function hasOffset()
    {
        return $this->getOffset() !== null;
    }

    public function getOffset()
    {
        return $this->getQueryPart(ZfSelect::LIMIT_OFFSET);
    }

    protected function getQueryPart($part)
    {
        try {
            return $this->query->getPart($part);
        } catch (ZfDbSelectException $e) {
            // Will not happen if $part is correct.
            throw new RuntimeException($e);
        }
    }

    public function setOffset($offset)
    {
        $this->query->limit(
            $this->getLimit(),
            $offset
        );
    }
}
