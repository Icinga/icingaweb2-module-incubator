<?php

namespace gipfl\SimpleDaemon;

use gipfl\SystemD\NotifySystemD;

interface SystemdAwareTask
{
    /**
     * @param NotifySystemD $systemd
     */
    public function setSystemd(NotifySystemD $systemd);
}
