<?php

namespace ipl\Html\FormElement;

class TextareaElement extends BaseFormElement
{
    protected $tag = 'textarea';

    public function setValue($value)
    {
        parent::setValue($value);
        $this->setContent($value);

        return $this;
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
