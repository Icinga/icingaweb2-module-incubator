<?php

namespace gipfl\OpenRpc;

use JsonSerializable;

/**
 * The example Pairing object consists of a set of example params and result.
 * The result is what you can expect from the JSON-RPC service given the exact
 * params.
 */
class ExamplePairing implements JsonSerializable
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
