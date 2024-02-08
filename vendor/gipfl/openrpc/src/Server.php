<?php

namespace gipfl\OpenRpc;

use JsonSerializable;

/**
 * An object representing a Server.
 */
class Server implements JsonSerializable
{
    use SimpleJsonSerializer;

    /**
     * REQUIRED. A name to be used as the canonical name for the server.
     *
     * @var string
     */
    public $name;

    /**
     * REQUIRED. A URL to the target host. This URL supports Server Variables and
     * MAY be relative, to indicate that the host location is relative to the
     * location where the OpenRPC document is being served. Server Variables are
     * passed into the Runtime Expression to produce a server URL.
     *
     * Runtime expressions allow the user to define an expression which will
     * evaluate to a string once the desired value(s) are known. They are used
     * when the desired value of a link or server can only be constructed at
     * run time. This mechanism is used by Link Objects and Server Variables.
     *
     * The runtime expression makes use of JSON Template Language syntax:
     *
     *   https://tools.ietf.org/html/draft-jonas-json-template-language-01
     *
     * Runtime expressions preserve the type of the referenced value.
     *
     * @var string RuntimeExpression
     */
    public $url;

    /**
     * A short summary of what the server is.
     *
     * @var string|null
     */
    public $summary;

    /**
     * An optional string describing the host designated by the URL. GitHub
     * Flavored Markdown syntax MAY be used for rich text representation.
     *
     * @var string|null
     */
    public $description;

    /**
     * A map between a variable name and its value. The value is passed into
     * the Runtime Expression to produce a server URL.
     *
     * @var ServerVariable Map[string, Server Variable Object]
     */
    public $variables;

    /**
     * @param string $name
     * @param string $url
     */
    public function __construct($name, $url)
    {
        $this->name = $name;
        $this->url = $url;
    }
}
