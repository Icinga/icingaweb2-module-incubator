<?php

namespace ipl\Html\FormElement;

use ipl\Html\Attribute;

class InputElement extends BaseFormElement
{
    protected $tag = 'input';

    /** @var string */
    protected $type;

    protected function registerCallbacks()
    {
        parent::registerCallbacks();
        $this->getAttributes()->registerAttributeCallback(
            'type',
            [$this, 'getTypeAttribute'],
            [$this, 'setType']
        );
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = (string) $type;

        return $this;
    }

    /**
     * @return Attribute
     */
    public function getTypeAttribute()
    {
        return new Attribute('type', $this->getType());
    }
}
