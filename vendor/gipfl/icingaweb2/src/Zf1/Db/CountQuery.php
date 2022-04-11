<?php

namespace gipfl\IcingaWeb2\Zf1\Db;

use gipfl\ZfDb\Select;
use RuntimeException;
use Zend_Db_Select as ZfSelect;

class CountQuery
{
    /** @var Select|ZfSelect */
    private $query;

    private $maxRows;

    /**
     * ZfCountQuery constructor.
     * @param Select|ZfSelect $query
     */
    public function __construct($query)
    {
        if ($query instanceof Select || $query instanceof ZfSelect) {
            $this->query = $query;
        } else {
            throw new RuntimeException('Got no supported ZF1 Select object');
        }
    }

    public function setMaxRows($max)
    {
        $this->maxRows = $max;
        return $this;
    }

    public function getQuery()
    {
        if ($this->needsSubQuery()) {
            return $this->buildSubQuery();
        } else {
            return $this->buildSimpleQuery();
        }
    }

    protected function hasOneOf($parts)
    {
        foreach ($parts as $part) {
            if ($this->hasPart($part)) {
                return true;
            }
        }

        return false;
    }

    protected function hasPart($part)
    {
        $values = $this->query->getPart($part);
        return ! empty($values);
    }

    protected function needsSubQuery()
    {
        return null !== $this->maxRows || $this->hasOneOf([
            Select::GROUP,
            Select::UNION
        ]);
    }

    protected function buildSubQuery()
    {
        $sub = clone($this->query);
        $sub->limit(null, null);
        $class = $this->query;
        $query = new $class($this->query->getAdapter());
        $query->from($sub, ['cnt' => 'COUNT(*)']);
        if (null !== $this->maxRows) {
            $sub->limit($this->maxRows + 1);
        }

        return $query;
    }

    protected function buildSimpleQuery()
    {
        $query = clone($this->query);
        $query->reset(Select::COLUMNS);
        $query->reset(Select::ORDER);
        $query->reset(Select::LIMIT_COUNT);
        $query->reset(Select::LIMIT_OFFSET);
        $query->columns(['cnt' => 'COUNT(*)']);
        return $query;
    }
}
