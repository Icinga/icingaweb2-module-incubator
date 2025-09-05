<?php

namespace gipfl\OpenRpc;

use JsonSerializable;

/**
 * Content Descriptors are objects that do just as they suggest - describe
 * content. They are reusable ways of describing either parameters or result.
 * They MUST have a schema.
 */
class ContentDescriptor implements JsonSerializable
{
    use SimpleJsonSerializer;

    /**
     * REQUIRED. Name of the content that is being described. If the content
     * described is a method parameter assignable by-name, this field SHALL
     * define the parameter’s key (ie name).
     *
     * @var string
     */
    public $name;

    /**
     * A short summary of the content that is being described.
     *
     * @var string|null
     */
    public $summary;

    /**
     * A verbose explanation of the content descriptor behavior. GitHub Flavored
     * Markdown syntax MAY be used for rich text representation.
     *
     * @var string|null
     */
    public $description;

    /**
     * Determines if the content is a required field. Default value is false.
     *
     * @var boolean|null
     */
    public $required;

    /**
     * REQUIRED. Schema that describes the content.
     *
     * The Schema Object allows the definition of input and output data types.
     * The Schema Objects MUST follow the specifications outline in the JSON
     * Schema Specification 7 Alternatively, any time a Schema Object can be
     * used, a Reference Object can be used in its place. This allows referencing
     * definitions instead of defining them inline.
     *
     * @var SchemaObject
     */
    public $schema;

    /**
     * Specifies that the content is deprecated and SHOULD be transitioned out
     * of usage. Default value is false.
     *
     * @var boolean|null
     */
    public $deprecated;

    public function __construct($name, $schema)
    {
        $this->name = $name;
        $this->schema = $schema;
    }
}
