<?php

namespace gipfl\Protocol\JsonRpc;

use Exception;

class Error
{
    const PARSE_ERROR = -32700;

    const INVALID_REQUEST = -32600;

    const METHOD_NOT_FOUND = -32601;

    const INVALID_PARAMS = 32602;

    const INTERNAL_ERROR = 32603;

    // Reserved for implementation-defined server-errors:
    const MIN_CUSTOM_ERROR = -32000;

    const MAX_CUSTOM_ERROR = -32099;

    protected static $wellKnownErrorCodes = [
        self::PARSE_ERROR,
        self::INVALID_REQUEST,
        self::METHOD_NOT_FOUND,
        self::INVALID_PARAMS,
        self::INTERNAL_ERROR,
    ];

    protected static $errorMessages = [
        self::PARSE_ERROR => 'Invalid JSON was received by the server.',
        self::INVALID_REQUEST => 'The JSON sent is not a valid Request object',
        self::METHOD_NOT_FOUND => 'The method does not exist / is not available',
        self::INVALID_PARAMS => 'Invalid method parameter(s)',
        self::INTERNAL_ERROR => 'Internal JSON-RPC error',
    ];

    protected static $defaultCustomMessage = 'Server error. Reserved for implementation-defined server-errors.';

    /** @var int */
    protected $code;

    /** @var string */
    protected $message;

    /** @var mixed|null */
    protected $data;

    /**
     * Error constructor.
     * @param int $code
     * @param string $message
     * @param mixed $data
     */
    public function __construct($code, $message = null, $data = null)
    {
        if ($message === null) {
            if ($this->isCustomErrorCode($code)) {
                $message = self::$defaultCustomMessage;
            } elseif (static::isWellKnownErrorCode($code)) {
                $message = self::$errorMessages[$code];
            } else {
                $message = 'Unknown error';
            }
        }
        $this->code    = $code;
        $this->message = $message;
        $this->data    = $data;
    }

    public static function forException(Exception $exception)
    {
        $code = $exception->getCode();
        if (! static::isCustomErrorCode($code)
            && ! static::isWellKnownErrorCode($code)
        ) {
            $code = self::INTERNAL_ERROR;
        }
        if (static::isWellKnownErrorCode($code) && $code !== self::INTERNAL_ERROR) {
            $data = null;
        } else {
            $data = $exception->getTraceAsString();
        }

        return new static($code, sprintf(
            '%s in %s(%d)',
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        ), $data);
    }

    /**
     * @return int
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param int $code
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     * @return $this
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @return mixed|null
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed|null $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    public function toPlainObject()
    {
        $result = [
            'code'    => $this->code,
            'message' => $this->message,
        ];

        if ($this->data !== null) {
            $result['data'] = $this->data;
        }

        return (object) $result;
    }

    public static function isWellKnownErrorCode($code)
    {
        return isset(self::$errorMessages[$code]);
    }

    public static function isCustomErrorCode($code)
    {
        return $code >= self::MIN_CUSTOM_ERROR && $code <= self::MAX_CUSTOM_ERROR;
    }
}
