<?php

namespace gipfl\Web\Widget;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use InvalidArgumentException;
use LogicException;
use function count;

class CollapsibleList extends BaseHtmlElement
{
    protected $tag = 'ul';

    protected $defaultAttributes = [
        'class' => 'gipfl-collapsible'
    ];

    protected $defaultListAttributes;

    protected $defaultSectionAttributes;

    protected $items = [];

    public function __construct($items = [], $listAttributes = null)
    {
        if ($listAttributes !== null) {
            $this->defaultListAttributes = $listAttributes;
        }
        foreach ($items as $title => $item) {
            $this->addItem($title, $item);
        }
    }

    public function addItem($title, $content)
    {
        if ($this->hasItem($title)) {
            throw new LogicException("Cannot add item with title '$title' twice");
        }
        $item = Html::tag('li', [
            Html::tag('a', ['href' => '#', 'class' => 'gipfl-collapsible-control'], $title),
            $content
        ]);

        if (count($this->items) > 0) {
            $item->getAttributes()->add('class', 'collapsed');
        }
        $this->items[$title] = $item;
    }

    public function hasItem($title)
    {
        return isset($this->items[$title]);
    }

    public function getItem($name)
    {
        if (isset($this->items[$name])) {
            return $this->items[$name];
        }

        throw new InvalidArgumentException("There is no '$name' item in this list");
    }

    protected function assemble()
    {
        if ($this->defaultListAttributes) {
            $this->addAttributes($this->defaultListAttributes);
        }
        foreach ($this->items as $item) {
            $this->add($item);
        }
    }
}
