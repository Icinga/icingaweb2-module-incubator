<?php

namespace gipfl\IcingaWeb2\Controller\Extension;

use InvalidArgumentException;

trait AutoRefreshHelper
{
    /** @var int|null */
    private $autorefreshInterval;

    public function setAutorefreshInterval($interval)
    {
        if (! is_int($interval) || $interval < 1) {
            throw new InvalidArgumentException(
                'Setting autorefresh interval smaller than 1 second is not allowed'
            );
        }
        $this->autorefreshInterval = $interval;
        $this->layout->autorefreshInterval = $interval;
        return $this;
    }

    public function disableAutoRefresh()
    {
        $this->autorefreshInterval = null;
        $this->layout->autorefreshInterval = null;
        return $this;
    }
}
