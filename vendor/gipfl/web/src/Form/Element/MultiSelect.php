<?php

namespace gipfl\Web\Form\Element;

use ipl\Html\Attributes;
use ipl\Html\FormElement\SelectElement;

class MultiSelect extends SelectElement
{
    protected $value = [];

    public function __construct($name, $attributes = null)
    {
        parent::__construct($name, $attributes);
        $this->getAttributes()->add('multiple', true);
    }

    protected function registerValueCallback(Attributes $attributes)
    {
        $attributes->registerAttributeCallback(
            'value',
            null,
            [$this, 'setValue']
        );
    }

    public function getNameAttribute()
    {
        return $this->getName() . '[]';
    }

    public function setValue($value)
    {
        if (empty($value)) { // null, '', []
            $values = [];
        } else {
            $values = (array) $value;
        }
        $invalid = [];
        foreach ($values as $val) {
            if ($option = $this->getOption($val)) {
                if ($option->getAttributes()->has('disabled')) {
                    $invalid[] = $val;
                }
            } else {
                $invalid[] = $val;
            }
        }
        if (count($invalid) > 0) {
            $this->failForValues($invalid);
            return $this;
        }

        $this->value = $values;
        $this->valid = null;
        $this->updateSelection();

        return $this;
    }

    protected function failForValues($values)
    {
        $this->valid = false;
        if (count($values) === 1) {
            $value = array_shift($values);
            $this->addMessage("'$value' is not allowed here");
        } else {
            $valueString = implode("', '", $values);
            $this->addMessage("'$valueString' are not allowed here");
        }
    }

    public function isValid()
    {
        if ($this->valid === null) {
            if ($this->isRequired() && empty($this->getValue())) {
                return false;
            }

            $this->validate();
        }

        return $this->valid;
    }

    public function updateSelection()
    {
        foreach ($this->options as $value => $option) {
            if (in_array($value, $this->value)) {
                $option->getAttributes()->add('selected', true);
            } else {
                $option->getAttributes()->remove('selected');
            }
        }

        return $this;
    }

    protected function assemble()
    {
        foreach ($this->options as $option) {
            $this->add($option);
        }
    }
}
