<?php

namespace gipfl\OpenRpc\Reflection;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use function lcfirst;
use function preg_match;

class MetaDataClass
{
    /** @var MetaDataMethod[] */
    public $methods = [];

    /** @var string|null */
    public $error;

    /**
     * @param string $class
     * @throws ReflectionException
     * @return static
     */
    public static function analyze($class)
    {
        $info = new static();

        $ref = new ReflectionClass($class);

        foreach ($ref->getMethods() as $method) {
            $methodName = $method->getName();
            if (! preg_match('/^(.+)(Request|Notification)$/', $methodName, $match)) {
                continue;
            }

            $info->addMethod(MethodCommentParser::parseMethod(
                $match[1],
                lcfirst($match[2]),
                $method->getDocComment()
            ));
        }

        return $info;
    }

    public function addMethod(MetaDataMethod $method)
    {
        $name = $method->name;
        if (isset($this->methods[$name])) {
            throw new InvalidArgumentException("Cannot add method '$name' twice");
        }

        $this->methods[$name] = $method;
    }

    /**
     * @return MetaDataMethod[]
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * @param $name
     * @return MetaDataMethod
     */
    public function getMethod($name)
    {
        if (isset($this->methods[$name])) {
            return $this->methods[$name];
        }

        throw new InvalidArgumentException("There is no '$name' method");
    }
}
