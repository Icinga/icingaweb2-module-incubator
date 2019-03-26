<?php

namespace ipl\Html\FormElement;

class SubFormElement extends BaseFormElement
{
    use FormElementContainer;

    protected $tag = 'div';

    protected $defaultAttributes = [
        'class' => 'ipl-subform'
    ];

    public function getValue($name = null)
    {
        if ($name === null) {
            return $this->getValues();
        } else {
            return $this->getElement($name)->getValue();
        }
    }

    public function setValue($value)
    {
        $this->populate($value);

        return $this;
    }

    public function isValid()
    {
        foreach ($this->getElements() as $element) {
            if (! $element->isValid) {
                return false;
            }
        }

        return true;
    }

    public function hasSubmitButton()
    {
        return true;
    }

    protected function registerValueCallback()
    {
        $this->getAttributes()->registerAttributeCallback(
            'value',
            null,
            [$this, 'setValue']
        );
    }
}
