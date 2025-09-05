<?php

namespace gipfl\OpenRpc;

use JsonSerializable;

/**
 * This is the root object of the OpenRPC document. The contents of this object
 * represent a whole OpenRPC document. How this object is constructed or stored
 * is outside the scope of the OpenRPC Specification.
 */
class OpenRpcDocument implements JsonSerializable
{
    use SimpleJsonSerializer;

    /**
     * REQUIRED. This string MUST be the semantic version number of the OpenRPC
     * Specification version that the OpenRPC document uses. The openrpc field
     * SHOULD be used by tooling specifications and clients to interpret the
     * OpenRPC document. This is not related to the API info.version string.
     *
     * @var string
     */
    public $openrpc;

    /**
     * REQUIRED. Provides metadata about the API. The metadata MAY be used by
     * tooling as required.
     *
     * @var Info
     */
    public $info;

    /**
     * An array of Server Objects, which provide connectivity information to a
     * target server. If the servers property is not provided, or is an empty
     * array, the default value would be a Server Object with a url value of
     * localhost.
     *
     * @var Server[]|null
     */
    public $servers;

    /**
     * REQUIRED. The available methods for the API. While it is required, the
     * array may be empty (to handle security filtering, for example).
     *
     * @var Method[]|Reference[]
     */
    public $methods = [];

    /**
     * An element to hold various schemas for the specification
     *
     * @var Components|null
     */
    public $components;

    /**
     * Additional external documentation
     *
     * @var ExternalDocumentation|null
     */
    public $externalDocs;

    /**
     * @param string $openRpcVersion
     * @param Info $info
     */
    public function __construct($openRpcVersion, Info $info)
    {
        $this->openrpc = $openRpcVersion;
        $this->info = $info;
    }
}
