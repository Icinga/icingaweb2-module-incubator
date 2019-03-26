<?php

namespace ipl\Html\FormElement;

use ipl\Html\BaseHtmlElement;

class SelectOption extends BaseHtmlElement
{
    protected $tag = 'option';

    /** @var mixed */
    protected $value;

    /**
     * SelectOption constructor.
     * @param string|null $value
     * @param string|null $label
     */
    public function __construct($value = null, $label = null)
    {
        $this->add($label);
        $this->getAttributes()->add('value', $value);
    }

    /**
     * @param $label
     * @return $this
     */
    public function setLabel($label)
    {
        $this->setContent($label);

        return $this;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
}
