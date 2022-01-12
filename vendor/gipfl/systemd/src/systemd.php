<?php

namespace gipfl\SystemD;

class systemd
{
    /**
     * @param null $env
     * @return bool
     */
    public static function startedThisProcess($env = null)
    {
        if ($env === null) {
            $env = $_SERVER;
        }

        return isset($env['NOTIFY_SOCKET']);
    }
}
