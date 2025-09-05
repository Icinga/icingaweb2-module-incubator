<?php

namespace gipfl\OpenRpc\Reflection;

use InvalidArgumentException;

class MetaDataMethod
{
    /** @var string */
    public $name;

    /** @var string Either 'request' or 'notification' */
    public $requestType;

    /** @var string */
    public $resultType;

    /** @var MetaDataParameter[] */
    public $parameters = [];

    /** @var string */
    public $title;

    /** @var string */
    public $description;

    public function __construct($name, $requestType)
    {
        $this->name = $name;
        $this->requestType = $requestType;
    }

    public function addParsed(MethodCommentParser $parser)
    {
        $this->resultType = $parser->getResultType();
        $this->parameters = $parser->getParams();
        $this->title = $parser->getTitle();
        $this->description = $parser->getDescription();

        return $this;
    }

    /**
     * @param MetaDataParameter $parameter
     */
    public function addParameter(MetaDataParameter $parameter)
    {
        $this->parameters[$parameter->getName()] = $parameter;
    }

    /**
     * @param $name
     * @return MetaDataParameter
     */
    public function getParameter($name)
    {
        if (isset($this->parameters[$name])) {
            return $this->parameters[$name];
        }

        throw new InvalidArgumentException("There is no '$name' parameter" . print_r($this->parameters, 1));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getRequestType()
    {
        return $this->requestType;
    }

    /**
     * @return string
     */
    public function getResultType()
    {
        return $this->resultType ?: 'void';
    }

    /**
     * @return MetaDataParameter[]
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
}
