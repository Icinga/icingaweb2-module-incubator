<?php

namespace gipfl\Protocol\JsonRpc;

class Notification extends Packet
{
    /** @var string */
    protected $method;

    /** @var \stdClass|array */
    protected $params;

    public function __construct($method, $params)
    {
        $this->setMethod($method);
        $this->setParams($params);
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * @return object|array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param object|array $params
     */
    public function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed|null
     */
    public function getParam($name, $default = null)
    {
        $p = & $this->params;
        if (\is_object($p) && \property_exists($p, $name)) {
            return $p->$name;
        } elseif (\is_array($p) && \array_key_exists($name, $p)) {
            return $p[$name];
        }

        return $default;
    }

    /**
     * @return object
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $plain = [
            'jsonrpc' => '2.0',
            'method'  => $this->method,
            'params'  => $this->params,
        ];

        if ($this->hasExtraProperties()) {
            $plain += (array) $this->getExtraProperties();
        }

        return (object) $plain;
    }

    /**
     * @param $method
     * @param $params
     * @return static
     */
    public static function create($method, $params)
    {
        $packet = new Notification($method, $params);

        return $packet;
    }
}
