<?php

namespace gipfl\Web\Form\Element;

use gipfl\Web\Form\Decorator\DdDtDecorator;
use ipl\Html\Attributes;
use ipl\Html\Form;
use ipl\Html\FormElement\SubmitElement;
use ipl\Html\FormElement\TextElement;

class TextWithActionButton
{
    /** @var SubmitElement */
    protected $button;

    /** @var TextElement */
    protected $element;

    protected $buttonSuffix = '_related_button';

    /** @var string */
    protected $elementName;

    /** @var array|Attributes */
    protected $elementAttributes;

    /** @var array|Attributes */
    protected $buttonAttributes;

    protected $elementClasses = ['input-with-button'];

    protected $buttonClasses = ['input-element-related-button'];

    /**
     * TextWithActionButton constructor.
     * @param string $elementName
     * @param array|Attributes $elementAttributes
     * @param array|Attributes $buttonAttributes
     */
    public function __construct($elementName, $elementAttributes, $buttonAttributes)
    {
        $this->elementName = $elementName;
        $this->elementAttributes = $elementAttributes;
        $this->buttonAttributes = $buttonAttributes;
    }

    public function addToForm(Form $form)
    {
        $button = $this->getButton();
        $form->registerElement($button);
        $element = $this->getElement();
        $form->addElement($element);
        /** @var DdDtDecorator $deco */
        $deco = $element->getWrapper();
        if ($deco instanceof DdDtDecorator) {
            $deco->addAttributes(['position' => 'relative'])->getElementDocument()->add($button);
        }
    }

    public function getElement()
    {
        if ($this->element === null) {
            $this->element = $this->createTextElement(
                $this->elementName,
                $this->elementAttributes
            );
        }

        return $this->element;
    }

    public function getButton()
    {
        if ($this->button === null) {
            $this->button = $this->createSubmitElement(
                $this->elementName . $this->buttonSuffix,
                $this->buttonAttributes
            );
        }

        return $this->button;
    }

    protected function createTextElement($name, $attributes = null)
    {
        $element = new TextElement($name, $attributes);
        $element->addAttributes([
            'class' => $this->elementClasses,
        ]);

        return $element;
    }

    protected function createSubmitElement($name, $attributes = null)
    {
        $element = new SubmitElement($name, $attributes);
        $element->addAttributes([
            'formnovalidate' => true,
            'class' => $this->buttonClasses,
        ]);

        return $element;
    }
}
