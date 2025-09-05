<?php

namespace gipfl\OpenRpc;

use JsonSerializable;

/**
 * Contact information for the exposed API
 */
class Contact implements JsonSerializable
{
    use SimpleJsonSerializer;

    /**
     * The identifying name of the contact person/organization
     *
     * @var string|null
     */
    public $name;

    /**
     * The URL pointing to the contact information. MUST be in the format of a
     * URL.
     *
     * @var string|null
     */
    public $url;

    /**
     * The email address of the contact person/organization. MUST be in the
     * format of an email address.
     *
     * @var string|null
     */
    public $email;

    /**
     * Contact constructor.
     * @param string|null $name
     * @param string|null $url
     * @param string|null $email
     */
    public function __construct($name = null, $url = null, $email = null)
    {
        $this->name = $name;
        $this->url = $url;
        $this->email = $email;
    }
}
