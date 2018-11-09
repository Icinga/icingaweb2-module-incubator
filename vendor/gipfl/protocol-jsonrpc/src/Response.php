<?php

namespace gipfl\Protocol\JsonRpc;

class Response extends Packet
{
    /** @var mixed|null This could be null when sending a parse error */
    protected $id;

    /** @var mixed */
    protected $result;

    /** @var Error */
    protected $error;

    public function __construct($id = null)
    {
        $this->id = $id;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public static function forRequest(Request $request)
    {
        $response = new Response($request->getId());

        return $response;
    }

    /**
     * @return object
     */
    public function toPlainObject()
    {
        $plain = (object) [
            'jsonrpc' => '2.0',
        ];

        if ($this->id !== null) {
            $plain->id = $this->id;
        }

        if ($this->error === null) {
            $plain->result = $this->result;
        } else {
            $plain->error = $this->error->toPlainObject();
        }

        return $plain;
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param $result
     */
    public function setResult($result)
    {
        $this->result = $result;
    }

    /**
     * @return bool
     */
    public function hasId()
    {
        return null !== $this->id;
    }

    /**
     * @return null|int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    public function isError()
    {
        return $this->error !== null;
    }

    /**
     * @return Error|null
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param $error
     */
    public function setError(Error $error)
    {
        $this->error = $error;
    }
}
