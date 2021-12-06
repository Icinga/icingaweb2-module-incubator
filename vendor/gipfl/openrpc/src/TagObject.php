<?php

namespace gipfl\OpenRpc;

use JsonSerializable;

/**
 * Adds metadata to a single tag that is used by the Method Object. It is not
 * mandatory to have a Tag Object per tag defined in the Method Object instances
 */
class TagObject implements JsonSerializable
{
    use SimpleJsonSerializer;

    /**
     * REQUIRED. The name of the tag.
     *
     * @var string
     */
    public $name;

    /**
     * A short summary of the tag.
     *
     * @var string|null
     */
    public $summary;

    /**
     * A verbose explanation for the tag. GitHub Flavored Markdown syntax MAY
     * be used for rich text representation.
     *
     * @var string|null
     */
    public $description;

    /**
     * Additional external documentation for this tag.
     *
     * @var ExternalDocumentation
     */
    public $externalDocs;

    public function __construct($name)
    {
        $this->name = $name;
    }
}
