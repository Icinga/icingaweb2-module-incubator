<?php

namespace gipfl\Web\Form\Element;

use ipl\Html\Attributes;
use ipl\Html\FormElement\SelectElement;

class MultiSelect extends SelectElement
{
    protected $value = [];

    public function __construct($name, $attributes = null)
    {
        // Make sure we set  value last as it depends on options
        if (isset($attributes['value'])) {
            $value = $attributes['value'];
            unset($attributes['value']);
            $attributes['value'] = $value;
        }

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

    public function validate()
    {
        /**
         * @TODO(lippserd): {@link SelectElement::validate()} doesn't work here because isset checks fail with
         * illegal offset type errors since our value is an array. It would make sense to decouple the classes to
         * avoid having to copy code from the base class.
         * Also note that {@see setValue()} already performs most of the validation.
         */
        if ($this->isRequired() && empty($this->getValue())) {
            $this->valid = false;
        } else {
            /**
             * Copied from {@link \ipl\Html\BaseHtmlElement::validate()}.
             */
            $this->valid = $this->getValidators()->isValid($this->getValue());
            $this->addMessages($this->getValidators()->getMessages());
        }
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
