<?php

namespace gipfl\OpenRpc;

use JsonSerializable;

/**
 * An object representing a Server Variable for server URL template substitution.
 */
class ServerVariable implements JsonSerializable
{
    use SimpleJsonSerializer;

    /**
     * REQUIRED. The default value to use for substitution, which SHALL be sent
     * if an alternate value is not supplied. Note this behavior is different
     * than the Schema Object’s treatment of default values, because in those
     * cases parameter values are optional.
     *
     * @var string
     */
    public $default;

    /**
     * An optional description for the server variable. GitHub Flavored Markdown
     * syntax MAY be used for rich text representation.
     *
     * @var string|null
     */
    public $description;

    /**
     * An enumeration of string values to be used if the substitution options are from a limited set.
     *
     * @var string[]
     */
    public $enum;

    /**
     * @param string $default
     */
    public function __construct($default)
    {
        $this->default = $default;
    }
}
