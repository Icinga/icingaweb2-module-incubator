<?php

namespace ipl\Html;

use ipl\Html\FormElement\FormElementContainer;
use ipl\Html\FormElement\SubmitElement;
use ipl\Stdlib\MessageContainer;
use Exception;
use Psr\Http\Message\ServerRequestInterface;

class Form extends BaseHtmlElement
{
    use FormElementContainer;
    use MessageContainer;

    protected $tag = 'form';

    protected $action;

    protected $method;

    /** @var SubmitElement */
    protected $submitButton;

    /** @var ServerRequestInterface */
    private $request;

    private $isValid;

    public function setRequest($request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * @param ServerRequestInterface $request
     * @return $this
     */
    public function handleRequest(ServerRequestInterface $request)
    {
        $this->setRequest($request);
        if ($this->hasBeenSent()) {
            if ($request->getMethod() === 'POST') {
                $params = $request->getParsedBody();
            } elseif ($this->getMethod() === 'GET') {
                parse_str($request->getUri()->getQuery(), $params);
            } else {
                $params = [];
            }
            $this->populate($params);
        }

        $this->ensureAssembled();
        if ($this->hasBeenSubmitted()) {
            if ($this->isValid()) {
                try {
                    $this->onSuccess();
                } catch (Exception $e) {
                    $this->addMessage($e);
                    $this->onError();
                }
            } else {
                $this->onError();
            }
        } elseif ($this->hasBeenSent()) {
            $this->validatePartial();
        }

        return $this;
    }

    /**
     * @return ServerRequestInterface|null
     */
    public function getRequest()
    {
        return $this->request;
    }

    public function onSuccess()
    {
        $this->add(Html::tag('p', ['class' => 'information'], 'SUCCESS'));
        // $this->redirectOnSuccess();
    }

    public function onError()
    {
        $error = Html::tag('p', ['class' => 'error']);
        foreach ($this->getMessages() as $message) {
            if ($message instanceof Exception) {
                $error->add($message->getMessage());
            } else {
                $error->add($message);
            }
        }
        $this->prepend($error);
    }

    public function isValid()
    {
        if ($this->isValid === null) {
            $this->validate();
        }

        return $this->isValid;
    }

    public function validate()
    {
        $valid = true;
        foreach ($this->elements as $element) {
            if ($element->isRequired() && ! $element->hasValue()) {
                $element->addMessage('This field is required');
                $valid = false;
                continue;
            }
            if (! $element->isValid()) {
                $valid = false;
            }
        }

        $this->isValid = $valid;
    }

    public function validatePartial()
    {
        foreach ($this->getElements() as $element) {
            if ($element->hasValue()) {
                $element->validate();
            }
        }
    }

    /**
     * @return bool
     */
    public function hasBeenSent()
    {
        if ($this->request === null) {
            return false;
        }

        if ($this->request->getMethod() !== $this->getMethod()) {
            return false;
        }

        // TODO: Check form name element

        return true;
    }

    /**
     * @return bool
     */
    public function hasBeenSubmitted()
    {
        if ($this->hasSubmitButton()) {
            return $this->getSubmitButton()->hasBeenPressed();
        } else {
            return $this->hasBeenSent();
        }
    }

    public function getSubmitButton()
    {
        return $this->submitButton;
    }

    public function hasSubmitButton()
    {
        return $this->submitButton !== null;
    }

    public function setSubmitButton(SubmitElement $element)
    {
        $this->submitButton = $element;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getMethod()
    {
        $method = $this->getAttributes()->get('method')->getValue();
        if ($method === null) {
            // WRONG. Problem:
            // right now we get the method in assemble, that's too late.
            // TODO: fix this via getMethodAttribute callback
            return 'POST';
        }

        return $method;
    }

    /**
     * @param $method
     * @return $this
     */
    public function setMethod($method)
    {
        $this->getAttributes()->set('method', strtoupper($method));

        return $this;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->getAttributes()->get('action')->getValue();
    }

    /**
     * @param $action
     * @return $this
     */
    public function setAction($action)
    {
        $this->getAttributes()->set('action', $action);

        return $this;
    }
}
