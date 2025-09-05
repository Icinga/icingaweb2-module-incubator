<?php

namespace gipfl\Web\Form\Decorator;

use gipfl\Web\HtmlHelper;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\FormDecorator\DecoratorInterface;
use ipl\Html\FormElement\BaseFormElement;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;

class DdDtDecorator extends BaseHtmlElement implements DecoratorInterface
{
    const CSS_CLASS_ELEMENT_HAS_ERRORS = 'gipfl-form-element-has-errors';

    const CSS_CLASS_ELEMENT_ERRORS = 'gipfl-form-element-errors';

    const CSS_CLASS_DESCRIPTION = 'gipfl-element-description';

    protected $tag = 'dl';

    protected $dt;

    protected $dd;

    /** @var BaseFormElement */
    protected $element;

    /** @var HtmlDocument */
    protected $elementDoc;

    /**
     * @param BaseFormElement $element
     * @return static
     */
    public function decorate(BaseFormElement $element)
    {
        $decorator = clone($this);
        $decorator->element = $element;
        $decorator->elementDoc = new HtmlDocument();
        $decorator->elementDoc->add($element);
        // if (! $element instanceof HiddenElement) {
        $element->prependWrapper($decorator);

        return $decorator;
    }

    protected function prepareLabel()
    {
        $element = $this->element;
        $label = $element->getLabel();
        if ($label === null || \strlen($label) === 0) {
            return null;
        }

        // Set HTML element.id to element name unless defined
        if ($element->getAttributes()->has('id')) {
            $attributes = ['for' => $element->getAttributes()->get('id')->getValue()];
        } else {
            $attributes = null;
        }

        if ($element->isRequired()) {
            $label = [$label, Html::tag('span', ['aria-hidden' => 'true'], '*')];
        }

        return Html::tag('label', $attributes, $label);
    }

    public function getAttributes(): Attributes
    {
        $attributes = parent::getAttributes();

        // TODO: only when sent?!
        if ($this->element->hasBeenValidated() && ! $this->element->isValid()) {
            HtmlHelper::addClassOnce($attributes, static::CSS_CLASS_ELEMENT_HAS_ERRORS);
        }

        return $attributes;
    }

    protected function prepareDescription()
    {
        if ($this->element) {
            $description = $this->element->getDescription();
            if ($description !== null && \strlen($description)) {
                return Html::tag('p', ['class' => static::CSS_CLASS_DESCRIPTION], $description);
            }
        }

        return null;
    }

    protected function prepareErrors()
    {
        $errors = [];
        foreach ($this->element->getMessages() as $message) {
            $errors[] = Html::tag('li', $message);
        }

        if (empty($errors)) {
            return null;
        } else {
            return Html::tag('ul', ['class' => static::CSS_CLASS_ELEMENT_ERRORS], $errors);
        }
    }

    public function add($content)
    {
        // Our wrapper implementation automatically adds the wrapped element but
        // we already do so in assemble()
        if ($content !== $this->element) {
            parent::add($content);
        }

        return $this;
    }

    protected function assemble()
    {
        $this->add([$this->dt(), $this->dd()]);
    }

    public function getElementDocument()
    {
        return $this->elementDoc;
    }

    public function dt()
    {
        if ($this->dt === null) {
            $this->dt = Html::tag('dt', null, $this->prepareLabel());
        }

        return $this->dt;
    }

    /**
     * @return \ipl\Html\HtmlElement
     */
    public function dd()
    {
        if ($this->dd === null) {
            $this->dd = Html::tag('dd', null, [
                $this->getElementDocument(),
                $this->prepareErrors(),
                $this->prepareDescription()
            ]);
        }

        return $this->dd;
    }

    public function __destruct()
    {
        $this->wrapper = null;
    }
}
