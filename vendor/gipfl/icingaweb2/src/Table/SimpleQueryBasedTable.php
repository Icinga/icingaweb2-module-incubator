<?php

namespace gipfl\IcingaWeb2\Table;

use Icinga\Data\SimpleQuery;
use gipfl\IcingaWeb2\Data\SimpleQueryPaginationAdapter;

abstract class SimpleQueryBasedTable extends QueryBasedTable
{
    /** @var SimpleQuery */
    private $query;

    protected function getPaginationAdapter()
    {
        return new SimpleQueryPaginationAdapter($this->getQuery());
    }

    protected function fetchQueryRows()
    {
        return $this->query->fetchAll();
    }

    /**
     * @return SimpleQuery
     */
    public function getQuery()
    {
        if ($this->query === null) {
            $this->query = $this->prepareQuery();
        }

        return $this->query;
    }
}
