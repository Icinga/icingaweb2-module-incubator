<?php

namespace gipfl\OpenRpc;

use JsonSerializable;

/**
 * License information for the exposed API.
 */
class License implements JsonSerializable
{
    use SimpleJsonSerializer;

    /**
     * REQUIRED. The license name used for the API.
     *
     * @var string
     */
    public $name;

    /**
     * A URL to the license used for the API. MUST be in the format of a URL.
     *
     * @var string|null
     */
    public $url;

    /**
     * @param string $name
     * @param string|null $url
     */
    public function __construct($name, $url = null)
    {
        $this->name = $name;
        $this->url = $url;
    }
}
