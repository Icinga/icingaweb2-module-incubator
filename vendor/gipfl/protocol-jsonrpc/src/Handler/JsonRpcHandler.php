<?php

namespace gipfl\Protocol\JsonRpc\Handler;

use gipfl\Protocol\JsonRpc\Error;
use gipfl\Protocol\JsonRpc\Notification;
use gipfl\Protocol\JsonRpc\Request;
use React\Promise\PromiseInterface;

interface JsonRpcHandler
{
    /**
     * @param Request $request
     * @return Error|PromiseInterface|mixed
     */
    public function processRequest(Request $request);

    /**
     * @param Notification $notification
     * @return void
     */
    public function processNotification(Notification $notification);
}
