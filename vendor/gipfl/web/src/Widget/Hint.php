<?php

namespace gipfl\Web\Widget;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;

class Hint extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes = [
        'class' => 'gipfl-widget-hint'
    ];

    public function __construct($message, $class = 'ok', ...$params)
    {
        $this->addAttributes(['class' => $class]);
        if (empty($params)) {
            $this->setContent($message);
        } else {
            $this->setContent(Html::sprintf($message, ...$params));
        }
    }

    public static function ok($message, ...$params)
    {
        return new static($message, 'ok', ...$params);
    }

    public static function info($message, ...$params)
    {
        return new static($message, 'info', ...$params);
    }

    public static function warning($message, ...$params)
    {
        return new static($message, 'warning', ...$params);
    }

    public static function error($message, ...$params)
    {
        return new static($message, 'error', ...$params);
    }
}
