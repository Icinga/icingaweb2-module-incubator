<?php

namespace gipfl\IcingaWeb2\Table\Extension;

use gipfl\IcingaWeb2\Url;
use gipfl\IcingaWeb2\Widget\Controls;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;

trait QuickSearch
{
    /** @var BaseHtmlElement */
    private $quickSearchForm;

    public function getQuickSearch(BaseHtmlElement $parent, Url $url)
    {
        $this->requireQuickSearchForm($parent, $url);
        $search = $url->getParam('q');
        return $search;
    }

    private function requireQuickSearchForm(BaseHtmlElement $parent, Url $url)
    {
        if ($this->quickSearchForm === null) {
            $this->quickSearchForm = $this->buildQuickSearchForm($parent, $url);
        }
    }

    private function buildQuickSearchForm(BaseHtmlElement $parent, Url $url)
    {
        $search = $url->getParam('q');

        $form = Html::tag('form', [
            'action' => $url->without(array('q', 'page', 'modifyFilter'))->getAbsoluteUrl(),
            'class'  => ['gipfl-quicksearch'],
            'method' => 'GET'
        ]);

        $form->add(
            Html::tag('input', [
                'type' => 'text',
                'name' => 'q',
                'title' => $this->translate('Search is simple! Try to combine multiple words'),
                'value' => $search,
                'placeholder' => $this->translate('Search...'),
                'class' => 'search'
            ])
        );

        $this->addQuickSearchToControls($parent, $form);

        return $form;
    }

    protected function addQuickSearchToControls(BaseHtmlElement $parent, BaseHtmlElement $form)
    {
        if ($parent instanceof Controls) {
            $title = $parent->getTitleElement();
            if ($title === null) {
                $parent->prepend($form);
            } else {
                $input = $form->getFirst('input');
                $form->remove($input);
                $title->add($input);
                $form->add($title);
                $parent->setTitleElement($form);
            }
        } else {
            $parent->prepend($form);
        }

        return $this;
    }
}
