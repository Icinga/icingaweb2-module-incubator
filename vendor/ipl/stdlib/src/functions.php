<?php

namespace ipl\Stdlib;

use InvalidArgumentException;
use Traversable;
use stdClass;

/**
 * Detect and return the PHP type of the given subject
 *
 * If subject is an object, the name of the object's class is returned, otherwise the subject's type.
 *
 * @param   $subject
 *
 * @return  string
 */
function get_php_type($subject)
{
    if (is_object($subject)) {
        return get_class($subject);
    } else {
        return gettype($subject);
    }
}

/**
 * Get the array value of the given subject
 *
 * @param   array|object|Traversable   $subject
 *
 * @return  array
 *
 * @throws  InvalidArgumentException   If subject type is invalid
 */
function arrayval($subject)
{
    if (is_array($subject)) {
        return $subject;
    }

    if ($subject instanceof stdClass) {
        return (array) $subject;
    }

    if ($subject instanceof Traversable) {
        // Works for generators too
        return iterator_to_array($subject);
    }

    throw new InvalidArgumentException(sprintf(
        'arrayval expects arrays, objects or instances of Traversable. Got %s instead.',
        get_php_type($subject)
    ));
}
