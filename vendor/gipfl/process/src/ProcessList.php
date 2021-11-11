<?php

namespace gipfl\Process;

/**
 * @method ChildProcess current
 */
use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use InvalidArgumentException;
use React\ChildProcess\Process as ChildProcess;
use SplObjectStorage;

class ProcessList extends SplObjectStorage implements EventEmitterInterface
{
    const ON_ATTACHED = 'attached';
    const ON_DETACHED = 'detached';

    use EventEmitterTrait;

    public function attach($object, $info = null)
    {
        if (! $object instanceof ChildProcess || $info !== null) {
            throw new InvalidArgumentException(sprintf(
                'Can attach only %s instances',
                ChildProcess::class
            ));
        }
        $object->on('exit', function () use ($object) {
            $this->detach($object);
        });

        parent::attach($object, $info);
        $this->emit(self::ON_ATTACHED, [$object]);
    }

    public function detach($object)
    {
        parent::detach($object);
        $this->emit(self::ON_DETACHED, [$object]);
    }
}
