<?php

namespace gipfl\Protocol\JsonRpc;

use gipfl\Protocol\Exception\ProtocolError;

class Request extends Notification
{
    /** @var mixed */
    protected $id;

    /**
     * Request constructor.
     * @param $method
     * @param mixed $id
     * @param null $params
     */
    public function __construct($method, $id = null, $params = null)
    {
        parent::__construct($method, $params);

        $this->id = $id;
    }

    /**
     * @return object
     * @throws ProtocolError
     */
    public function toPlainObject()
    {
        if ($this->id === null) {
            throw new ProtocolError(
                'A request without an ID is not valid'
            );
        }

        $plain = parent::toPlainObject();
        $plain->id = $this->id;

        return $plain;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}
