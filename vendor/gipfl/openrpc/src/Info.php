<?php

namespace gipfl\OpenRpc;

use JsonSerializable;

/**
 * The object provides metadata about the API. The metadata MAY be used by the
 * clients if needed, and MAY be presented in editing or documentation
 * generation tools for convenience.
 */
class Info implements JsonSerializable
{
    use SimpleJsonSerializer;

    /**
     * REQUIRED. The title of the application
     *
     * @var string
     */
    public $title;
    /**
     * A verbose description of the application. GitHub Flavored Markdown syntax
     * MAY be used for rich text representation.
     *
     * @var string|null
     */
    public $description;

    /**
     * A URL to the Terms of Service for the API. MUST be in the format of a URL
     *
     * @var string|null
     */
    public $termsOfService;

    /**
     * The contact information for the exposed API
     *
     * @var Contact|null
     */
    public $contact;

    /**
     * 	The license information for the exposed API
     *
     * @var License|null
     */
    public $license;

    /**
     * REQUIRED. The version of the OpenRPC document (which is distinct from the
     * OpenRPC Specification version or the API implementation version)
     *
     * @var string
     */
    public $version;

    /**
     * Info constructor.
     * @param string $title
     * @param string $version
     */
    public function __construct($title, $version)
    {
        $this->title = $title;
        $this->version = $version;
    }
}
