<?php

namespace gipfl\Web;

use Exception;
use gipfl\Web\Form\Decorator\DdDtDecorator;
use gipfl\Web\Form\Validator\AlwaysFailValidator;
use gipfl\Web\Form\Validator\PhpSessionBasedCsrfTokenValidator;
use gipfl\Web\Widget\Hint;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Contract\FormElement;
use ipl\Html\Error;
use ipl\Html\Form as iplForm;
use ipl\Html\FormElement\BaseFormElement;
use ipl\Html\FormElement\HiddenElement;
use ipl\Html\Html;
use RuntimeException;

use function array_key_exists;
use function get_class;
use function parse_str;

class Form extends iplForm
{
    protected $formNameElementName = '__FORM_NAME';

    protected $useCsrf = true;

    protected $useFormName = true;

    protected $defaultDecoratorClass = DdDtDecorator::class;

    protected $formCssClasses = ['gipfl-form'];

    /** @var boolean|null */
    protected $hasBeenSubmitted;

    public function ensureAssembled()
    {
        if ($this->hasBeenAssembled === false) {
            if ($this->getRequest() === null) {
                throw new RuntimeException('Cannot assemble a WebForm without a Request');
            }
            $this->registerGipflElementLoader();
            $this->setupStyling();
            parent::ensureAssembled();
            $this->prepareWebForm();
        }

        return $this;
    }

    protected function registerGipflElementLoader()
    {
        $this->addElementLoader(__NAMESPACE__ . '\\Form\\Element');
    }

    public function setSubmitted($submitted = true)
    {
        $this->hasBeenSubmitted = (bool) $submitted;

        return $this;
    }

    public function hasBeenSubmitted()
    {
        if ($this->hasBeenSubmitted === null) {
            return parent::hasBeenSubmitted();
        } else {
            return $this->hasBeenSubmitted;
        }
    }

    public function disableCsrf()
    {
        $this->useCsrf = false;

        return $this;
    }

    public function doNotCheckFormName()
    {
        $this->useFormName = false;

        return $this;
    }

    protected function prepareWebForm()
    {
        if ($this->hasElement($this->formNameElementName)) {
            return; // Called twice
        }
        if ($this->useFormName) {
            $this->addFormNameElement();
        }
        if ($this->useCsrf && $this->getMethod() === 'POST') {
            $this->addCsrfElement();
        }
    }

    protected function getUniqueFormName()
    {
        return get_class($this);
    }

    protected function addFormNameElement()
    {
        $element = new HiddenElement($this->formNameElementName, [
            'value'  => $this->getUniqueFormName(),
            'ignore' => true,
        ]);
        $this->prepend($element);
        $this->registerElement($element);
    }

    public function addHidden($name, $value = null, $attributes = [])
    {
        if (is_array($value) && empty($attributes)) {
            $attributes = $value;
            $value = null;
        } elseif ($value === null && is_scalar($attributes)) {
            $value = $attributes;
            $attributes = [];
        }
        if ($value !== null) {
            $attributes['value'] = $value;
        }
        $element = new HiddenElement($name, $attributes);
        $this->prepend($element);
        $this->registerElement($element);
    }

    public function registerElement(FormElement $element)
    {
        $idPrefix = '';
        if ($element instanceof BaseHtmlElement) {
            if (! $element->getAttributes()->has('id')) {
                $element->addAttributes(['id' => $idPrefix . $element->getName()]);
            }
        }

        return parent::registerElement($element);
    }

    public function setElementValue($element, $value)
    {
        $this->wantFormElement($element)->setValue($value);
    }

    public function getElementValue($elementName, $defaultValue = null)
    {
        $value = $this->getElement($elementName)->getValue();
        if ($value === null) {
            return $defaultValue;
        } else {
            return $value;
        }
    }

    public function hasElementValue($elementName)
    {
        if ($this->hasElement($elementName)) {
            return $this->getElement($elementName)->hasValue();
        } else {
            return false;
        }
    }

    /**
     * @param $element
     * @return FormElement
     */
    protected function wantFormElement($element)
    {
        if ($element instanceof BaseFormElement) {
            return $element;
        } else {
            return $this->getElement($element);
        }
    }

    public function triggerElementError($element, $message, ...$params)
    {
        if (! empty($params)) {
            $message = Html::sprintf($message, $params);
        }

        $element = $this->wantFormElement($this->getElement($element));
        $element->addValidators([
            new AlwaysFailValidator(['message' => $message])
        ]);
    }

    protected function setupStyling()
    {
        $this->setSeparator("\n");
        $this->addAttributes(['class' => $this->formCssClasses]);
        if ($this->defaultDecoratorClass !== null) {
            $this->setDefaultElementDecorator(new $this->defaultDecoratorClass);
        }
    }

    protected function addCsrfElement()
    {
        $element = new HiddenElement('__FORM_CSRF', [
            'ignore'   => true,
            'required' => true,
        ]);
        $element->setValidators([
            new PhpSessionBasedCsrfTokenValidator()
        ]);
        // prepend / register -> avoid decorator
        $this->prepend($element);
        $this->registerElement($element);
        if (! $this->hasBeenSent()) {
            $element->setValue(PhpSessionBasedCsrfTokenValidator::generateCsrfValue());
        }
    }

    public function getSentValue($name, $default = null)
    {
        $params = $this->getSentValues();

        if (array_key_exists($name, $params)) {
            return $params[$name];
        } else {
            return $default;
        }
    }

    public function getSentValues()
    {
        $request = $this->getRequest();
        if ($request === null) {
            throw new RuntimeException(
                "It's impossible to access SENT values with no request"
            );
        }

        if ($request->getMethod() === 'POST') {
            $params = $request->getParsedBody();
        } elseif ($this->getMethod() === 'GET') {
            parse_str($request->getUri()->getQuery(), $params);
        } else {
            $params = [];
        }

        return $params;
    }

    protected function onError()
    {
        $messages = $this->getMessages();
        if (empty($messages)) {
            return;
        }
        $errors = [];
        foreach ($this->getMessages() as $message) {
            if ($message instanceof Exception) {
                $this->prepend(Error::show($message));
            } else {
                $errors[] = $message;
            }
        }
        if (! empty($errors)) {
            $this->prepend(Hint::error(implode(', ', $errors)));
        }
    }

    public function hasBeenSent()
    {
        if (parent::hasBeenSent()) {
            return !$this->useFormName  || $this->getSentValue($this->formNameElementName)
                === $this->getUniqueFormName();
        } else {
            return false;
        }
    }
}
