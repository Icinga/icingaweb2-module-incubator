<?php

namespace gipfl\Web\Form\Feature;

use gipfl\Web\Form;
use ipl\Html\DeferredText;
use ipl\Html\FormElement\BaseFormElement;
use ipl\Html\FormElement\SubmitElement;
use ipl\Html\HtmlDocument;
use ipl\Html\ValidHtml;

class NextConfirmCancel
{
    /** @var SubmitElement */
    protected $next;

    /** @var SubmitElement */
    protected $confirm;

    /** @var SubmitElement */
    protected $cancel;

    protected $withNext;

    protected $withNextContent;

    protected $withConfirm;

    protected $withConfirmContent;

    protected $confirmFirst = true;

    public function __construct(SubmitElement $next, SubmitElement $confirm, SubmitElement $cancel)
    {
        $this->next = $next;
        $this->confirm = $confirm;
        $this->cancel = $cancel;
        $this->withNextContent = new HtmlDocument();
        $this->withNext = new DeferredText(function () {
            return $this->withNextContent;
        });
        $this->withNext->setEscaped();

        $this->withConfirmContent = new HtmlDocument();
        $this->withConfirm = new DeferredText(function () {
            return $this->withConfirmContent;
        });
        $this->withConfirm->setEscaped();
    }

    public function showWithNext($content)
    {
        $this->withNextContent->add($content);
    }

    public function showWithConfirm($content)
    {
        $this->withConfirmContent->add($content);
    }

    /**
     * @param ValidHtml $html
     * @param array $found Internal parameter
     * @return BaseFormElement[]
     */
    protected function pickFormElements(ValidHtml $html, &$found = [])
    {
        if ($html instanceof BaseFormElement) {
            $found[] = $html;
        } elseif ($html instanceof HtmlDocument) {
            foreach ($html->getContent() as $content) {
                $this->pickFormElements($content, $found);
            }
        }

        return $found;
    }

    /**
     * @param string $label
     * @param array $attributes
     * @return SubmitElement
     */
    public static function buttonNext($label, $attributes = [])
    {
        return new SubmitElement('next', $attributes + [
            'label' => $label
        ]);
    }

    /**
     * @param string $label
     * @param array $attributes
     * @return SubmitElement
     */
    public static function buttonConfirm($label, $attributes = [])
    {
        return new SubmitElement('submit', $attributes + [
            'label' => $label
        ]);
    }

    /**
     * @param string $label
     * @param array $attributes
     * @return SubmitElement
     */
    public static function buttonCancel($label, $attributes = [])
    {
        return new SubmitElement('cancel', $attributes + [
            'label' => $label
        ]);
    }

    public function addToForm(Form $form)
    {
        $cancel = $this->cancel;
        $confirm = $this->confirm;
        $next = $this->next;
        if ($form->hasBeenSent()) {
            $form->add($this->withConfirm);
            if ($this->confirmFirst) {
                $form->addElement($confirm);
                $form->addElement($cancel);
            } else {
                $form->addElement($cancel);
                $form->addElement($confirm);
            }
            if ($cancel->hasBeenPressed()) {
                $this->withConfirmContent = new HtmlDocument();
                // HINT: we might also want to redirect on cancel and stop here,
                //       but currently we have no Response
                $form->setSubmitted(false);
                $form->remove($confirm);
                $form->remove($cancel);
                $form->add($next);
                $form->setSubmitButton($next);
            } else {
                $form->setSubmitButton($confirm);
                $form->remove($next);
                foreach ($this->pickFormElements($this->withConfirmContent) as $element) {
                    $form->registerElement($element);
                }
            }
        } else {
            $form->add($this->withNext);
            foreach ($this->pickFormElements($this->withNextContent) as $element) {
                $form->registerElement($element);
            }
            $form->addElement($next);
        }
    }
}
