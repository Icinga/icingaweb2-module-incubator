<?php

namespace gipfl\OpenRpc;

use JsonSerializable;

/**
 * Holds a set of reusable objects for different aspects of the OpenRPC. All
 * objects defined within the components object will have no effect on the API
 * unless they are explicitly referenced from properties outside the components
 * object.
 *
 * All the fixed fields declared are objects that MUST use keys that match the
 * regular expression: ^[a-zA-Z0-9\.\-_]+$
 */
class Components implements JsonSerializable
{
    use SimpleJsonSerializer;

    /**
     * An object to hold reusable Content Descriptor Objects
     *
     * @var ContentDescriptor[] Map[string, Content Descriptor Object]
     */
    public $contentDescriptors;

    /**
     * An object to hold reusable Schema Objects
     *
     * @var SchemaObject[] Map[string, Schema Object]
     */
    public $schemas;

    /**
     * An object to hold reusable Example Objects
     *
     * @var Example[] Map[string, Example Object]
     */
    public $examples;

    /**
     * An object to hold reusable Link Objects
     *
     * @var Link[] Map[string, Link Object]
     */
    public $links;

    /**
     * An object to hold reusable Error Objects
     *
     * @var Error[] Map[string, Error Object]
     */
    public $errors;

    /**
     * An object to hold reusable Example Pairing Objects
     *
     * @var ExamplePairing[] Map[string, Example Pairing Object]
     */
    public $examplePairingObjects;

    /**
     * An object to hold reusable Tag Objects
     *
     * @var TagObject[] Map[string, Tag Object]
     */
    public $tags;
}
