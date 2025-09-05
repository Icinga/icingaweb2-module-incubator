<?php

namespace gipfl\OpenRpc;

use JsonSerializable;

/**
 * Describes the interface for the given method name. The method name is used
 * as the method field of the JSON-RPC body. It therefore MUST be unique.
 */
class Method implements JsonSerializable
{
    use SimpleJsonSerializer;

    /**
     * REQUIRED. The cannonical name for the method. The name MUST be unique
     * within the methods array.
     *
     * @var string
     */
    public $name;

    /**
     * A list of tags for API documentation control. Tags can be used for
     * logical grouping of methods by resources or any other qualifier.
     *
     * @var TagObject[]|Reference[]
     */
    public $tags;

    /**
     * A short summary of what the method does
     *
     * @var string|null
     */
    public $summary;

    /**
     * A verbose explanation of the method behavior. GitHub Flavored Markdown
     * syntax MAY be used for rich text representation.
     *
     * @var string|null
     */
    public $description;

    /**
     * Additional external documentation for this method
     *
     * @var ExternalDocumentation
     */
    public $externalDocs;

    /**
     * REQUIRED. A list of parameters that are applicable for this method. The
     * list MUST NOT include duplicated parameters and therefore require name
     * to be unique. The list can use the Reference Object to link to parameters
     * that are defined by the Content Descriptor Object. All optional params
     * (content descriptor objects with “required”: false) MUST be positioned
     * after all required params in the list.
     *
     * @var <ContentDescriptor|Reference>[]
     */
    public $params;

    /**
     * REQUIRED. The description of the result returned by the method. It MUST
     * be a Content Descriptor.
     *
     * @var ContentDescriptor|Reference
     */
    public $result;

    /**
     * Declares this method to be deprecated. Consumers SHOULD refrain from
     * usage of the declared method. Default value is false.
     *
     * @var boolean
     */
    public $deprecated;

    /**
     * An alternative servers array to service this method. If an alternative
     * servers array is specified at the Root level, it will be overridden by
     * this value.
     *
     * @var Server[]
     */
    public $servers;

    /**
     * A list of custom application defined errors that MAY be returned. The
     * Errors MUST have unique error codes.
     *
     * @var <Error|Reference>[]
     */
    public $errors;

    /**
     * A list of possible links from this method call
     *
     * @var <Link|Reference>[]
     */
    public $links;

    /**
     * The expected format of the parameters. As per the JSON-RPC 2.0 specification,
     * the params of a JSON-RPC request object may be an array, object, or either
     * (represented as by-position, by-name, and either respectively). When a method
     * has a paramStructure value of by-name, callers of the method MUST send a
     * JSON-RPC request object whose params field is an object. Further, the key
     * names of the params object MUST be the same as the contentDescriptor.names
     * for the given method. Defaults to "either".
     *
     * @var string "by-name" | "by-position" | "either"
     */
    public $paramStructure;

    /**
     * Array of Example Pairing Object where each example includes a valid
     * params-to-result Content Descriptor pairing.
     *
     * @var ExamplePairing []
     */
    public $examples;

    /**
     * @param $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }
}
