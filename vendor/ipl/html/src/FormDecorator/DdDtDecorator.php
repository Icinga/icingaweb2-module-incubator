<?php

namespace ipl\Html\FormDecorator;

use ipl\Html\BaseHtmlElement;
use ipl\Html\FormElement\BaseFormElement;
use ipl\Html\Html;

class DdDtDecorator extends BaseHtmlElement implements DecoratorInterface
{
    protected $tag = 'dl';

    protected $dt;

    protected $dd;

    /** @var BaseFormElement */
    protected $wrappedElement;

    protected $ready = false;

    /**
     * @param BaseFormElement $element
     * @return static
     */
    public function decorate(BaseFormElement $element)
    {
        // TODO: ignore hidden?
        $newWrapper = clone($this);
        $newWrapper->wrappedElement = $element;
        $element->prependWrapper($newWrapper);

        return $newWrapper;
    }

    protected function renderLabel()
    {
        if ($this->wrappedElement instanceof BaseFormElement) {
            $label = $this->wrappedElement->getLabel();
            if (strlen($label)) {
                return Html::tag('label', null, $label);
            }
        }

        return null;
    }

    public function getAttributes()
    {
        $attributes = parent::getAttributes();

        // TODO: only when sent?!
        if ($this->wrappedElement->hasBeenValidatedAndIsNotValid()) {
            $classes = $attributes->get('class');
            if (empty($classes)
                || (is_array($classes) && ! in_array('errors', $classes))
                || (is_string($classes) && $classes !== 'errors')
            ) {
                $attributes->add('class', 'errors');
            }
        }

        return $attributes;
    }

    protected function renderDescription()
    {
        if ($this->wrappedElement instanceof BaseFormElement) {
            $description = $this->wrappedElement->getDescription();
            if (strlen($description)) {
                return Html::tag('p', ['class' => 'description'], $description);
            }
        }

        return null;
    }

    protected function renderErrors()
    {
        if ($this->wrappedElement instanceof BaseFormElement) {
            $errors = [];
            foreach ($this->wrappedElement->getMessages() as $message) {
                $errors[] = Html::tag('p', ['class' => 'error'], $message);
            }

            if (! empty($errors)) {
                return $errors;
            }
        }

        return null;
    }

    public function add($content)
    {
        // TODO: is this required?
        if ($content !== $this->wrappedElement) {
            parent::add($content);
        }

        return $this;
    }

    protected function assemble()
    {
        $this->add([$this->dt(), $this->dd()]);
        $this->ready = true;
    }

    protected function dt()
    {
        if ($this->dt === null) {
            $this->dt = Html::tag('dt', null, $this->renderLabel());
        }

        return $this->dt;
    }

    /**
     * @return \ipl\Html\HtmlElement
     */
    protected function dd()
    {
        if ($this->dd === null) {
            $this->dd = Html::tag('dd', null, [
                $this->wrappedElement,
                $this->renderErrors(),
                $this->renderDescription()
            ]);
        }

        return $this->dd;
    }

    public function __destruct()
    {
        $this->wrapper = null;
    }
}
