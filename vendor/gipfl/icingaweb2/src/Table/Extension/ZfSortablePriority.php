<?php

namespace gipfl\IcingaWeb2\Table\Extension;

use gipfl\IcingaWeb2\Table\ZfQueryBasedTable;
use gipfl\IcingaWeb2\IconHelper;
use gipfl\ZfDb\Exception\SelectException;
use gipfl\ZfDb\Select;
use Icinga\Web\Request;
use Icinga\Web\Response;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlString;
use RuntimeException;
use Zend_Db_Select_Exception as ZfDbSelectException;

/**
 * Trait ZfSortablePriority
 *
 * Assumes to run in a ZfQueryBasedTable
 */
trait ZfSortablePriority
{
    /** @var Request */
    protected $request;

    /** @var Response */
    protected $response;

    public function handleSortPriorityActions(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
        return $this;
    }

    protected function reallyHandleSortPriorityActions()
    {
        $request = $this->request;

        if ($request->isPost() && $this->hasBeenSent($request)) {
            // $this->fixPriorities();
            foreach (array_keys($request->getPost()) as $key) {
                if (substr($key, 0, 8) === 'MOVE_UP_') {
                    $id = (int) substr($key, 8);
                    $this->moveRow($id, 'up');
                }
                if (substr($key, 0, 10) === 'MOVE_DOWN_') {
                    $id = (int) substr($key, 10);
                    $this->moveRow($id, 'down');
                }
            }
            $this->response->redirectAndExit($request->getUrl());
        }
    }

    protected function hasBeenSent(Request $request)
    {
        return $request->getPost('__FORM_NAME') === $this->getUniqueFormName();
    }

    protected function addSortPriorityButtons(BaseHtmlElement $tr, $row)
    {
        $tr->add(
            Html::tag(
                'td',
                null,
                $this->createUpDownButtons($row->{$this->getKeyColumn()})
            )
        );

        return $tr;
    }

    protected function getKeyColumn()
    {
        if (isset($this->keyColumn)) {
            return $this->keyColumn;
        } else {
            throw new RuntimeException(
                'ZfSortablePriority requires keyColumn'
            );
        }
    }

    protected function getPriorityColumn()
    {
        if (isset($this->priorityColumn)) {
            return $this->priorityColumn;
        } else {
            throw new RuntimeException(
                'ZfSortablePriority requires priorityColumn'
            );
        }
    }

    protected function getPriorityColumns()
    {
        return [
            'id'   => $this->getKeyColumn(),
            'prio' => $this->getPriorityColumn()
        ];
    }

    protected function moveRow($id, $direction)
    {
        /** @var $this ZfQueryBasedTable */
        $db = $this->db();
        /** @var $this ZfQueryBasedTable */
        $query = $this->getQuery();
        $tableParts = $this->getQueryPart(Select::FROM);
        $alias = key($tableParts);
        $table = $tableParts[$alias]['tableName'];

        $whereParts = $this->getQueryPart(Select::WHERE);
        unset($query);
        if (empty($whereParts)) {
            $where = '';
        } else {
            $where = ' AND ' . implode(' ', $whereParts);
        }

        $prioCol = $this->getPriorityColumn();
        $keyCol = $this->getKeyColumn();
        $myPrio = (int) $db->fetchOne(
            $db->select()
                ->from($table, $prioCol)
                ->where("$keyCol = ?", $id)
        );

        $op = $direction === 'up' ? '<' : '>';
        $sortDir = $direction === 'up' ? 'DESC' : 'ASC';
        $query = $db->select()
            ->from([$alias => $table], $this->getPriorityColumns())
            ->where("$prioCol $op ?", $myPrio)
            ->order("$prioCol $sortDir")
            ->limit(1);

        if (! empty($whereParts)) {
            $query->where(implode(' ', $whereParts));
        }

        $next = $db->fetchRow($query);

        if ($next) {
            $sql = 'UPDATE %s %s'
                 . ' SET %s = CASE WHEN %s = %s THEN %d ELSE %d END'
                 . ' WHERE %s IN (%s, %s)';

            $query = sprintf(
                $sql,
                $table,
                $alias,
                $prioCol,
                $keyCol,
                $id,
                (int) $next->prio,
                $myPrio,
                $keyCol,
                $id,
                (int) $next->id
            ) . $where;

            $db->query($query);
        }
    }

    protected function getSortPriorityTitle()
    {
        /** @var ZfQueryBasedTable $table */
        $table = $this;

        return Html::tag(
            'span',
            ['title' => $table->translate('Change priority')],
            $table->translate('Prio')
        );
    }

    protected function createUpDownButtons($key)
    {
        /** @var ZfQueryBasedTable $table */
        $table = $this;
        $up = $this->createIconButton(
            "MOVE_UP_$key",
            'up-big',
            $table->translate('Move up (raise priority)')
        );
        $down = $this->createIconButton(
            "MOVE_DOWN_$key",
            'down-big',
            $table->translate('Move down (lower priority)')
        );

        if ($table->isOnFirstRow()) {
            $up->getAttributes()->add('disabled', 'disabled');
        }

        if ($table->isOnLastRow()) {
            $down->getAttributes()->add('disabled', 'disabled');
        }

        return [$down, $up];
    }

    protected function createIconButton($key, $icon, $title)
    {
        return Html::tag('input', [
            'type'  => 'submit',
            'class' => 'icon-button',
            'name'  => $key,
            'title' => $title,
            'value' => IconHelper::instance()->iconCharacter($icon)
        ]);
    }

    protected function getUniqueFormName()
    {
        $parts = explode('\\', get_class($this));
        return end($parts);
    }

    protected function renderWithSortableForm()
    {
        if ($this->request === null) {
            return parent::render();
        }
        $this->reallyHandleSortPriorityActions();

        $url = $this->request->getUrl();
        // TODO: No margin for form
        $form = Html::tag('form', [
            'action' => $url->getAbsoluteUrl(),
            'method' => 'POST'
        ], [
            Html::tag('input', [
                'type'  => 'hidden',
                'name'  => '__FORM_NAME',
                'value' => $this->getUniqueFormName()
            ]),
            new HtmlString(parent::render())
        ]);

        return $form->render();
    }

    protected function getQueryPart($part)
    {
        /** @var ZfQueryBasedTable $table */
        $table = $this;
        /** @var Select|\Zend_Db_Select $query */
        $query = $table->getQuery();
        try {
            return $query->getPart($part);
        } catch (SelectException $e) {
            // Will not happen if $part is correct.
            throw new RuntimeException($e);
        } catch (ZfDbSelectException $e) {
            // Will not happen if $part is correct.
            throw new RuntimeException($e);
        }
    }
}
