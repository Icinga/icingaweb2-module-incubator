<?php

namespace ipl\Html\FormElement;

use ipl\Html\Html;

class SelectElement extends BaseFormElement
{
    protected $tag = 'select';

    /** @var SelectOption[] */
    protected $options = [];

    protected $optionContent = [];

    public function __construct($name, $attributes = null)
    {
        $this->getAttributes()->registerAttributeCallback(
            'options',
            null,
            [$this, 'setOptions']
        );
        // ZF1 compatibility:
        $this->getAttributes()->registerAttributeCallback(
            'multiOptions',
            null,
            [$this, 'setOptions']
        );

        // Make sure we set value last, options must be set before
        if (isset($attributes['value'])) {
            $value = $attributes['value'];
            unset($attributes['value']);
            $attributes['value'] = $value;
        }
        parent::__construct($name, $attributes);
    }

    public function hasOption($value)
    {
        return isset($this->options[$value]);
    }

    public function setValue($value)
    {
        if (! $this->hasOption($value)) {
            $this->isValid = false;
            $this->addMessage("'$value' is not allowed here");

            return $this;
        }

        if ($value === '') {
            $value = null;
        }

        if ($option = $this->getOption($value)) {
            if ($option->getAttributes()->has('disabled')) {
                $this->isValid = false;
                $this->addMessage("'$value' is not allowed here");

                return $this;
            }
        }
        $this->deselect();
        $option->getAttributes()->add('selected', true);

        return parent::setValue($value);
    }

    public function isValid()
    {
        if ($this->isRequired() && strlen($this->getValue()) === 0) {
            return false;
        } else {
            return parent::isValid();
        }
    }

    public function deselect()
    {
        if ($option = $this->getOption($this->getValue())) {
            $option->getAttributes()->remove('selected');
        }

        return $this;
    }

    public function disableOption($value)
    {
        if ($option = $this->getOption($value)) {
            $option->getAttributes()->add('disabled', true);
        }
        if ($this->getValue() == $value) {
            $this->isValid = false;
            $this->addMessage("'$value' is not allowed here");
        }

        return $this;
    }

    public function disableOptions($values)
    {
        foreach ($values as $value) {
            $this->disableOption($value);
        }

        return $this;
    }

    /**
     * @param $value
     * @return SelectOption|null
     */
    public function getOption($value)
    {
        if ($this->hasOption($value)) {
            return $this->options[$value];
        } else {
            return null;
        }
    }

    /**
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->options = [];
        foreach ($options as $value => $label) {
            $this->optionContent[$value] = $this->makeOption($value, $label);
        }

        return $this;
    }

    protected function makeOption($value, $label)
    {
        if (is_array($label)) {
            $grp = Html::tag('optgroup', ['label' => $value]);
            foreach ($label as $option => $val) {
                $grp->add($this->makeOption($option, $val));
            }

            return $grp;
        } else {
            $this->options[$value] = new SelectOption($value, $label);

            return $this->options[$value];
        }
    }

    protected function assemble()
    {
        foreach ($this->optionContent as $value => $option) {
            $this->add($option);
        }
    }
}
