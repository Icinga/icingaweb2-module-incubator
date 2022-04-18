<?php

namespace gipfl\Web\Form\Element;

use gipfl\Translation\TranslationHelper;
use ipl\Html\FormElement\SelectElement;

class Boolean extends SelectElement
{
    use TranslationHelper;

    public function __construct($name, $attributes = null)
    {
        parent::__construct($name, $attributes);
        $options = [
            'y'  => $this->translate('Yes'),
            'n'  => $this->translate('No'),
        ];
        if (! $this->isRequired()) {
            $options = [
                null => $this->translate('- please choose -'),
            ] + $options;
        }

        $this->setOptions($options);
    }

    public function setValue($value)
    {
        if ($value === 'y' || $value === true) {
            return parent::setValue('y');
        } elseif ($value === 'n' || $value === false) {
            return parent::setValue('n');
        }

        // Hint: this will fail
        return parent::setValue($value);
    }
}
