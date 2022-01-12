<?php

namespace gipfl\Web\Table;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Table;

class NameValueTable extends Table
{
    protected $defaultAttributes = ['class' => 'gipfl-name-value-table'];

    public static function create($pairs = [])
    {
        $self = new static;
        $self->addNameValuePairs($pairs);

        return $self;
    }

    public function createNameValueRow($name, $value)
    {
        return $this::tr([$this::th($name), $this::wantTd($value)]);
    }

    public function addNameValueRow($name, $value)
    {
        return $this->add($this->createNameValueRow($name, $value));
    }

    public function addNameValuePairs($pairs)
    {
        foreach ($pairs as $name => $value) {
            $this->addNameValueRow($name, $value);
        }

        return $this;
    }

    protected function wantTd($value)
    {
        if ($value instanceof BaseHtmlElement && $value->getTag() === 'td') {
            return $value;
        } else {
            return $this::td($value);
        }
    }
}
