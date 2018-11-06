<?php

namespace Icinga\Module\Incubator {

    use Icinga\Application\Hook\ApplicationStateHook;

    class ApplicationState extends ApplicationStateHook
    {
        public function collectMessages()
        {
            $this->addError(
                'incubator.master',
                time(),
                'Please install a Release version of the Incubator module, not the GIT master'
            );
        }
    }

    $this->provideHook('ApplicationState', '\\Icinga\\Module\\Incubator\\ApplicationState');
}
