<?php

namespace gipfl\IcingaWeb2\Widget;

use ipl\Html\BaseHtmlElement;

class ActionBar extends BaseHtmlElement
{
    protected $contentSeparator = ' ';

    /** @var string */
    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'action-bar'];

    /**
     * @param  string $target
     * @return $this
     */
    public function setBaseTarget($target)
    {
        $this->getAttributes()->set('data-base-target', $target);
        return $this;
    }
}
