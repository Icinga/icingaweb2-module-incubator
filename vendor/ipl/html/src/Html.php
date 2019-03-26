<?php

namespace ipl\Html;

use InvalidArgumentException;
use Traversable;

/**
 * Class Html
 *
 * This is your main utility class when working with ipl\Html
 *
 * @package ipl\Html
 */
abstract class Html
{
    /**
     * Create a HTML element from the given tag, attributes and content
     *
     * This method does not render the HTML element but creates a {@link HtmlElement}
     * instance from the given tag, attributes and content
     *
     * @param   string $name       The desired HTML tag name
     * @param   mixed  $attributes HTML attributes or content for the element
     * @param   mixed  $content    The content of the element if no attributes have been given
     *
     * @return  HtmlElement The created element
     */
    public static function tag($name, $attributes = null, $content = null)
    {
        if ($attributes instanceof ValidHtml || is_scalar($attributes)) {
            $content = $attributes;
            $attributes = null;
        } elseif (is_array($attributes)) {
            reset($attributes);
            if (is_int(key($attributes))) {
                $content = $attributes;
                $attributes = null;
            }
        }

        return new HtmlElement($name, $attributes, $content);
    }

    /**
     * Convert special characters to HTML5 entities using the UTF-8 character
     * set for encoding
     *
     * This method internally uses {@link htmlspecialchars} with the following
     * flags:
     *
     * * Single quotes are not escaped (ENT_COMPAT)
     * * Uses HTML5 entities, disallowing &#013; (ENT_HTML5)
     * * Invalid characters are replaced with � (ENT_SUBSTITUTE)
     *
     * Already existing HTML entities will be encoded as well.
     *
     * @param   string  $content        The content to encode
     *
     * @return  string  The encoded content
     */
    public static function escape($content)
    {
        return htmlspecialchars($content, ENT_COMPAT | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * sprintf()-like helper method
     *
     * This allows to use sprintf with ValidHtml elements, but with the
     * advantage that they'll not be rendered immediately. The result is an
     * instance of FormattedString, being ValidHtml
     *
     * Usage:
     *
     *     echo Html::sprintf('Hello %s!', Html::tag('strong', $name));
     *
     * @param $string
     * @return FormattedString
     */
    public static function sprintf($string)
    {
        $args = func_get_args();
        array_shift($args);

        return new FormattedString($string, $args);
    }

    /**
     * Wraps each Item of a given list
     *
     * Wrapper is a simple HTML tag per entry if a string is given, otherwise
     * a given callback/callable is being called passing key and value of each
     * list entry as parameters
     *
     * @param array|Traversable $list
     * @param string|callable $wrapper
     * @return HtmlDocument
     */
    public static function wrapEach($list, $wrapper)
    {
        if (! is_array($list) && ! $list instanceof Traversable) {
            throw new InvalidArgumentException(sprintf(
                'Html::wrapEach() requires a traversable list, got "%s"',
                Error::getPhpTypeName($list)
            ));
        }
        $result = new HtmlDocument();
        foreach ($list as $name => $value) {
            if (is_string($wrapper)) {
                $result->add(Html::tag($wrapper, $value));
            } elseif (is_callable($wrapper)) {
                $result->add($wrapper($name, $value));
            } else {
                throw new InvalidArgumentException(sprintf(
                    'Wrapper must be callable or a string in Html::wrapEach(), got "%s"',
                    Error::getPhpTypeName($wrapper)
                ));
            }
        }

        return $result;
    }

    /**
     * Accept any input and try to convert it to ValidHtml
     *
     * Returns the very same element in case it's already valid
     *
     * @param mixed $any
     * @return ValidHtml
     * @throws InvalidArgumentException
     */
    public static function wantHtml($any)
    {
        if ($any instanceof ValidHtml) {
            return $any;
        } elseif (static::canBeRenderedAsString($any)) {
            return new Text($any);
        } elseif (is_array($any)) {
            $html = new HtmlDocument();
            foreach ($any as $el) {
                if ($el !== null) {
                    $html->add(static::wantHtml($el));
                }
            }

            return $html;
        } else {
            throw new InvalidArgumentException(sprintf(
                'String, Html Element or Array of such expected, got "%s"',
                Error::getPhpTypeName($any)
            ));
        }
    }

    /**
     * Whether a given variable can be rendered as a string
     *
     * @param $any
     * @return bool
     */
    public static function canBeRenderedAsString($any)
    {
        return is_scalar($any) || is_null($any) || (
            is_object($any) && method_exists($any, '__toString')
        );
    }

    /**
     * @param $name
     * @param $arguments
     * @return HtmlElement
     */
    public static function __callStatic($name, $arguments)
    {
        $attributes = array_shift($arguments);
        $content = array_shift($arguments);

        return static::tag($name, $attributes, $content);
    }

    /**
     * @deprecated Use {@link Html::encode()} instead
     */
    public static function escapeForHtml($content)
    {
        return static::escape($content);
    }

    /**
     * @deprecated Use {@link Error::render()} instead
     */
    public static function renderError($error)
    {
        return Error::render($error);
    }
}
