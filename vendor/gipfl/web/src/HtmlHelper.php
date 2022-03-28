<?php

namespace gipfl\Web;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;

abstract class HtmlHelper
{
    public static function elementHasClass(BaseHtmlElement $element, $class)
    {
        return static::classIsSet($element->getAttributes(), $class);
    }

    public static function addClassOnce(Attributes $attributes, $class)
    {
        if (! HtmlHelper::classIsSet($attributes, $class)) {
            $attributes->add('class', $class);
        }
    }

    public static function classIsSet(Attributes $attributes, $class)
    {
        $classes = $attributes->get('class');

        return \is_array($classes) && in_array($class, $classes)
            || \is_string($classes) && $classes === $class;
    }
}
