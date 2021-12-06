<?php

namespace gipfl\SimpleDaemon;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use gipfl\Json\JsonSerialization;
use function implode;
use function strlen;

class DaemonState implements JsonSerialization, EventEmitterInterface
{
    use EventEmitterTrait;

    const ON_CHANGE = 'change';

    /** @var string */
    protected $processTitle;

    /** @var ?string */
    protected $state;

    /** @var ?string */
    protected $currentProcessTitle;

    /** @var ?string */
    protected $currentMessage;

    /** @var string[] */
    protected $componentStates = [];

    /**
     * @return string
     */
    public function getProcessTitle()
    {
        return $this->processTitle;
    }

    /**
     * @param string $processTitle
     * @return DaemonState
     */
    public function setProcessTitle($processTitle)
    {
        $this->processTitle = $processTitle;
        $this->refreshMessage();
        return $this;
    }

    /**
     * @return string|null
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param string|null $state
     * @return DaemonState
     */
    public function setState($state)
    {
        $this->state = $state;
        $this->refreshMessage();

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCurrentMessage()
    {
        return $this->currentMessage;
    }

    /**
     * @return string[]
     */
    public function getComponentStates()
    {
        return $this->componentStates;
    }

    /**
     * @param string[] $componentStates
     * @return DaemonState
     */
    public function setComponentStates($componentStates)
    {
        $this->componentStates = $componentStates;
        $this->refreshMessage();
        return $this;
    }

    /**
     * @param string $name
     * @param string $stateMessage
     * @return $this
     */
    public function setComponentState($name, $stateMessage)
    {
        if ($stateMessage === null) {
            unset($this->componentStates[$name]);
        } else {
            $this->componentStates[$name] = $stateMessage;
        }
        $this->refreshMessage();

        return $this;
    }

    public function getComponentState($name)
    {
        if (isset($this->componentStates[$name])) {
            return $this->componentStates[$name];
        }

        return null;
    }

    public static function fromSerialization($any)
    {
        $self = new static();
        if (isset($any->state)) {
            $self->state = $any->state;
        }
        if (isset($any->currentMessage)) {
            $self->currentMessage = $any->currentMessage;
        }
        if (isset($any->processTitle)) {
            $self->processTitle = $any->processTitle;
        }
        if (isset($any->components)) {
            $self->componentStates = $any->components;
        }

        return $self;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return (object) [
            'state'          => $this->state,
            'currentMessage' => $this->currentMessage,
            'processTitle'   => $this->processTitle,
            'components'     => $this->componentStates
        ];
    }

    protected function refreshMessage()
    {
        $messageParts = [];
        $state = $this->getState();
        if (strlen($state)) {
            $messageParts[] = $state;
        }
        foreach ($this->getComponentStates() as $component => $message) {
            $messageParts[] = "$component: $message";
        }

        $message = implode(', ', $messageParts);
        if ($message !== $this->currentMessage || $this->processTitle !== $this->currentProcessTitle) {
            $this->currentMessage = $message;
            $this->currentProcessTitle = $this->processTitle;
            $this->emit(self::ON_CHANGE, [$this->currentProcessTitle, $this->currentMessage]);
        }
    }
}
