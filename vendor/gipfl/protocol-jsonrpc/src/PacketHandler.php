<?php

namespace gipfl\Protocol\JsonRpc;

/**
 * @deprecated
 */
interface PacketHandler
{
    public function handle(Notification $notification);
}
