<?php

namespace gipfl\Protocol\JsonRpc;

class Response extends Packet
{
    /** @var mixed|null This could be null when sending a parse error */
    protected $id;

    /** @var mixed */
    protected $result;

    /** @var Error|null */
    protected $error;

    /** @var string */
    protected $message;

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
        return new Response($request->getId());
    }

    /**
     * @return object
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $plain = [
            'jsonrpc' => '2.0',
        ];
        if ($this->hasExtraProperties()) {
            $plain += (array) $this->getExtraProperties();
        }

        if ($this->id !== null) {
            $plain['id'] = $this->id;
        }

        if ($this->error === null) {
            $plain['result'] = $this->result;
        } else {
            if (! isset($plain['id'])) {
                $plain['id'] = null;
            }
            $plain['error'] = $this->error;
        }

        return (object) $plain;
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
     * @return $this
     */
    public function setResult($result)
    {
        $this->result = $result;

        return $this;
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
     * @return $this;
     */
    public function setError(Error $error)
    {
        $this->error = $error;

        return $this;
    }
}
