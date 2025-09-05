<?php

namespace gipfl\OpenRpc;

use JsonSerializable;

/**
 * Defines an application level error.
 */
class Error implements JsonSerializable
{
    use SimpleJsonSerializer;

    /**
     * Application Defined Error Code
     *
     * REQUIRED. A Number that indicates the error type that occurred. This
     * MUST be an integer. The error codes from and including -32768 to -32000
     * are reserved for pre-defined errors. These pre-defined errors SHOULD be
     * assumed to be returned from any JSON-RPC api.
     *
     * @var int
     */
    public $code;

    /**
     * REQUIRED. A String providing a short description of the error. The
     * message SHOULD be limited to a concise single sentence.
     *
     * @var string
     */
    public $message;

    /**
     * A Primitive or Structured value that contains additional information
     * about the error. This may be omitted. The value of this member is defined
     * by the Server (e.g. detailed error information, nested errors etc.).
     *
     * @var mixed
     */
    public $data;

    /**
     * @param int $code
     * @param string $message
     * @param mixed|null $data
     */
    public function __construct($code, $message, $data = null)
    {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }
}
