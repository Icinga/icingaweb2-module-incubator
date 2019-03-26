<?php

namespace ipl\Html\FormDecorator;

use ipl\Html\FormElement\BaseFormElement;

// TODO: FormElementDecoratorInterface?
interface DecoratorInterface
{
    /**
     * @param BaseFormElement $element
     * @return static
     */
    public function decorate(BaseFormElement $element);
}
