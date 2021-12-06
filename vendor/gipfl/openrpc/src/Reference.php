<?php

namespace gipfl\OpenRpc;

use JsonSerializable;

/**
 * A simple object to allow referencing other components in the specification,
 * internally and externally.
 *
 * The Reference Object is defined by JSON Schema and follows the same structure,
 * behavior and rules.
 */
class Reference implements JsonSerializable
{
    /** @var string REQUIRED. The reference string */
    public $ref;

    /**
     * @param string $ref
     */
    public function __construct($ref)
    {
        $this->ref = $ref;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
       return (object) ['$ref' => $this->ref];
    }
}
