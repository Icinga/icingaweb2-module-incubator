<?php

namespace gipfl\Process;

use React\ChildProcess\Process as ChildProcess;
use SplObjectStorage;

class ProcessList extends SplObjectStorage
{
    public function attach($object, $info = null)
    {
        if (! $object instanceof ChildProcess || $info !== null) {
            throw new \InvalidArgumentException(sprintf(
                'Can attach only %s instances', ChildProcess::class
            ));
        }
        $object->on('exit', function () use ($object) {
            $this->detach($object);
        });

        parent::attach($object, $info);
    }
}
