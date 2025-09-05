<?php

namespace gipfl\IcingaWeb2\Widget;

use ipl\Html\BaseHtmlElement;

class Content extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $contentSeparator = "\n";

    protected $defaultAttributes = ['class' => 'content'];
}
