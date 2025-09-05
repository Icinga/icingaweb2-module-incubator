<?php

namespace gipfl\Protocol\JsonRpc\Handler;

use Exception;
use gipfl\Json\JsonSerialization;
use gipfl\OpenRpc\Reflection\MetaDataClass;
use gipfl\OpenRpc\Reflection\MetaDataMethod;
use gipfl\Protocol\JsonRpc\Error;
use gipfl\Protocol\JsonRpc\Notification;
use gipfl\Protocol\JsonRpc\Request;
use RuntimeException;
use TypeError;

use function call_user_func_array;
use function method_exists;
use function preg_split;
use function sprintf;
use function strpos;

class NamespacedPacketHandler implements JsonRpcHandler
{
    protected $nsSeparator = '.';

    protected $nsRegex = '/\./';

    protected $handlers = [];

    /**
     * @var MetaDataMethod[]
     */
    protected $knownMethods = [];

    public function processNotification(Notification $notification)
    {
        list($namespace, $method) = $this->splitMethod($notification->getMethod());
        try {
            $this->call($namespace, $method, $notification);
        } catch (Exception $exception) {
            // Well... we might want to log this
        } catch (TypeError $exception) {
            // Well... we might want to log this
        }
    }

    public function processRequest(Request $request)
    {
        list($namespace, $method) = $this->splitMethod($request->getMethod());

        try {
            return $this->call($namespace, $method, $request);
        } catch (Exception $exception) {
            return Error::forException($exception);
        } catch (TypeError $error) {
            return Error::forTypeError($error);
        }
    }

    public function listRegisteredNamespaces(): array
    {
        return array_keys($this->handlers);
    }

    public function getRegisteredNamespaceClasses(): array
    {
        $result = [];
        foreach ($this->handlers as $key => $handler) {
            $result[$key] = get_class($handler);
        }

        return $result;
    }

    /**
     * @param string $namespace
     * @param object $handler
     */
    public function registerNamespace($namespace, $handler)
    {
        if (isset($this->handlers[$namespace])) {
            throw new RuntimeException("Cannot register a namespace twice: '$namespace'");
        }
        $this->handlers[$namespace] = $handler;
        $this->analyzeNamespace($namespace, $handler);
    }

    protected function analyzeNamespace($namespace, $handler)
    {
        $meta = MetaDataClass::analyze(get_class($handler));
        foreach ($meta->getMethods() as $method) {
            $this->knownMethods[$namespace . $this->nsSeparator . $method->getName()] = $method;
        }
    }

    /**
     * @param string $namespace
     */
    public function removeNamespace($namespace)
    {
        unset($this->handlers[$namespace]);
    }

    public function setNamespaceSeparator($separator)
    {
        $this->nsSeparator = $separator;
        $this->nsRegex = '/' . preg_quote($separator, '/') . '/';

        return $this;
    }

    protected function call($namespace, $method, Notification $notification)
    {
        if (! isset($this->handlers[$namespace])) {
            return $this->notFound($notification, ', no handler for ' . $namespace);
        }

        $handler = $this->handlers[$namespace];
        if ($handler instanceof JsonRpcHandler) {
            if ($notification instanceof Request) {
                return $handler->processRequest($notification);
            } else {
                $handler->processNotification($notification);
            }
        }

        $params = $notification->getParams();
        if (! is_array($params)) {
            try {
                $params = $this->prepareParams($notification->getMethod(), $params);
            } catch (Exception $e) {
                return Error::forException($e);
            }
        }
        if ($notification instanceof Request) {
            $rpcMethod = $method . 'Request';
            if (is_callable([$handler, $rpcMethod])) {
                return call_user_func_array([$handler, $rpcMethod], $params);
            }

            return $this->notFound($notification, ', no ' . $rpcMethod);
        } else {
            $rpcMethod = $method . 'Notification';
            if (is_callable([$handler, $rpcMethod])) {
                call_user_func_array([$handler, $rpcMethod], $params);
            }

            return null;
        }
    }

    protected function prepareParams($method, $params)
    {
        if (! isset($this->knownMethods[$method])) {
            throw new Exception('Cannot map params for unknown method');
        }

        $meta = $this->knownMethods[$method];
        $result = [];
        foreach ($meta->getParameters() as $parameter) {
            $name = $parameter->getName();
            if (property_exists($params, $name)) {
                $value = $params->$name;
                if ($value === null) {
                    // TODO: check if required
                    $result[] = $value;
                    continue;
                }
                switch ($parameter->getType()) {
                    case 'int':
                        $result[] = (int) $value;
                        break;
                    case 'float':
                        $result[] = (float) $value;
                        break;
                    case 'string':
                        $result[] = (string) $value;
                        break;
                    case 'array':
                        $result[] = (array) $value;
                        break;
                    case 'bool':
                    case 'boolean':
                        $result[] = (bool) $value;
                        break;
                    case 'object':
                        $result[] = (object) $value;
                        break;
                    default:
                        $type = $parameter->getType();
                        if (class_exists($type)) {
                            foreach (class_implements($type) as $implement) {
                                if ($implement === JsonSerialization::class) {
                                    $result[] = $type::fromSerialization($value);
                                    break 2;
                                }
                            }
                        }
                        throw new Exception(sprintf(
                            'Unsupported parameter type for %s: %s',
                            $method,
                            $parameter->getType()
                        ));
                }
            } else {
                // TODO: isRequired? Set null
                $type = $parameter->getType();
                throw new Exception("Missing parameter for $method: $name ($type)");
            }
        }

        return $result;
    }

    protected function splitMethod($method)
    {
        if (strpos($method, $this->nsSeparator) === false) {
            return [null, $method];
        }

        return preg_split($this->nsRegex, $method, 2);
    }

    protected function notFound(Notification $notification, $suffix = '')
    {
        $error = new Error(Error::METHOD_NOT_FOUND);
        $error->setMessage(sprintf(
            '%s: %s' . $suffix,
            $error->getMessage(),
            $notification->getMethod()
        ));

        return $error;
    }
}
