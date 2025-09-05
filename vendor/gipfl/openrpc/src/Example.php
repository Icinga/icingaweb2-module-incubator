<?php

namespace gipfl\OpenRpc;

use JsonSerializable;

/**
 * The Example object is an object the defines an example that is intended to
 * match a given Content Descriptor Schema. If the Content Descriptor Schema
 * includes examples, the value from this Example Object supersedes the value
 * of the schema example.
 *
 * In all cases, the example vaJsonSerializablelue is expected to be compatible with the type
 * schema of its associated value. Tooling implementations MAY choose to
 * validate compatibility automatically, and reject the example value(s) if
 * incompatible.
 */
class Example implements JsonSerializable
{
    use SimpleJsonSerializer;

    /** @var string|null Name for the example pairing */
    public $name;

    /** @var string|null A verbose explanation of the example pairing */
    public $summary;

    /** @var string|null Short description for the example pairing */
    public $description;

    /** @var <Example|Reference>[] Example parameters */
    public $params;

    /** @var Example|Reference Example result */
    public $result;
}
