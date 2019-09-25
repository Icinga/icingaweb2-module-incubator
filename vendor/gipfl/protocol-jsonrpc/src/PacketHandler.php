<?php

namespace gipfl\Protocol\JsonRpc;

interface PacketHandler
{
    public function handle(Notification $notification);
}
