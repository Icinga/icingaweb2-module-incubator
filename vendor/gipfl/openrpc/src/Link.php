<?php

namespace gipfl\OpenRpc;

use JsonSerializable;

/**
 * The Link object represents a possible design-time link for a result. The
 * presence of a link does not guarantee the caller’s ability to successfully
 * invoke it, rather it provides a known relationship and traversal mechanism
 * between results and other methods.
 *
 * Unlike dynamic links (i.e. links provided in the result payload), the OpenRPC
 * linking mechanism does not require link information in the runtime result.
 *
 * For computing links, and providing instructions to execute them, a runtime
 * expression is used for accessing values in an method and using them as
 * parameters while invoking the linked method.
 */
class Link implements JsonSerializable
{
    use SimpleJsonSerializer;

    /**
     * REQUIRED. Canonical name of the link.
     *
     * @var string
     */
    public $name;

    /**
     * Short description for the link.
     *
     * @var string|null
     */
    public $summary;

    /**
     * A description of the link. GitHub Flavored Markdown syntax MAY be used
     * for rich text representation.
     *
     * @var string|null
     */
    public $description;

    /**
     * The name of an existing, resolvable OpenRPC method, as defined with a
     * unique method. This field MUST resolve to a unique Method Object. As
     * opposed to Open Api, Relative method values ARE NOT permitted.
     *
     * @var string|null
     */
    public $method;

    /**
     * A map representing parameters to pass to a method as specified with
     * method. The key is the parameter name to be used, whereas the value can
     * be a constant or a runtime expression to be evaluated and passed to the
     * linked method.
     *
     * A linked method must be identified directly, and must exist in the list
     * of methods defined by the Methods Object.
     *
     * When a runtime expression fails to evaluate, no parameter value is passed
     * to the target method.
     *
     * Values from the result can be used to drive a linked method.
     *
     * Clients follow all links at their discretion. Neither permissions, nor
     * the capability to make a successful call to that link, is guaranteed
     * solely by the existence of a relationship.
     *
     * @var array Map[string, Any | RuntimeExpression]
     */
    public $params;

    /**
     * A server object to be used by the target method.
     *
     * @var Server|null
     */
    public $server;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }
}
