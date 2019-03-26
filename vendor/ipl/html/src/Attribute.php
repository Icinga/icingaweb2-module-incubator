<?php

namespace ipl\Html;

use InvalidArgumentException;

/**
 * HTML Attribute
 *
 * Every single HTML attribute is (or should be) an instance of this class.
 * This guarantees that every attribute is safe and escaped correctly.
 *
 * Usually attributes are not instantiated directly, but created through an HTML
 * element's exposed methods.
 */
class Attribute
{
    /** @var string */
    protected $name;

    /** @var string|array|bool|null */
    protected $value;

    /** @var string Glue string to join elements if the attribute's value is an array */
    protected $glue = ' ';

    /**
     * Create a new HTML attribute from the given name and value
     *
     * @param   string                  $name   The name of the attribute
     * @param   string|bool|array|null  $value  The value of the attribute
     *
     * @throws  InvalidArgumentException        If the name of the attribute contains special characters
     */
    public function __construct($name, $value = null)
    {
        $this->setName($name)->setValue($value);
    }

    /**
     * Create a new HTML attribute from the given name and value
     *
     * @param   string                  $name   The name of the attribute
     * @param   string|bool|array|null  $value  The value of the attribute
     *
     * @return  static
     *
     * @throws  InvalidArgumentException        If the name of the attribute contains special characters
     */
    public static function create($name, $value)
    {
        return new static($name, $value);
    }

    /**
     * Create a new empty HTML attribute from the given name
     *
     * The value of the attribute will be null after construction.
     *
     * @param   string                  $name   The name of the attribute
     *
     * @return  static
     *
     * @throws  InvalidArgumentException        If the name of the attribute contains special characters
     */
    public static function createEmpty($name)
    {
        return new static($name, null);
    }

    /**
     * Get the name of the attribute
     *
     * @return  string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the name of the attribute
     *
     * @param   string  $name
     *
     * @return  $this
     *
     * @throws  InvalidArgumentException    If the name contains special characters
     */
    protected function setName($name)
    {
        if (! preg_match('/^[a-z][a-z0-9:-]*$/i', $name)) {
            throw new InvalidArgumentException(sprintf(
                'Attribute names with special characters are not yet allowed: %s',
                $name
            ));
        }

        $this->name = $name;

        return $this;
    }

    /**
     * Get the value of the attribute
     *
     * @return  string|bool|array|null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set the value of the attribute
     *
     * @param   string|bool|array|null  $value
     *
     * @return  $this
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Add the given value(s) to the attribute
     *
     * @param   string|array    $value  The value(s) to add
     *
     * @return  $this
     */
    public function addValue($value)
    {
        $this->value = array_merge((array) $this->value, (array) $value);

        return $this;
    }

    /**
     * Remove the given value(s) from the attribute
     *
     * The current value is set to null if it matches the value to remove
     * or is in the array of values to remove.
     *
     * If the current value is an array, all elements are removed which
     * match the value(s) to remove.
     *
     * Does nothing if there is no such value to remove.
     *
     * @param   string|array    $value  The value(s) to remove
     *
     * @return  $this
     */
    public function removeValue($value)
    {
        $value = (array) $value;

        $current = $this->getValue();

        if (is_array($current)) {
            $this->setValue(array_diff($current, $value));
        } elseif (in_array($current, $value, true)) {
            $this->setValue(null);
        }

        return $this;
    }

    /**
     * Test and return true if the attribute is boolean, false otherwise
     *
     * @return  bool
     */
    public function isBoolean()
    {
        return is_bool($this->value);
    }

    /**
     * Test and return true if the attribute is empty, false otherwise
     *
     * Null and the empty array will be considered empty.
     *
     * @return  bool
     */
    public function isEmpty()
    {
        return $this->value === null || $this->value === [];
    }

    /**
     * Render the attribute to HTML
     *
     * If the value of the attribute is of type boolean, it will be rendered as
     * {@link http://www.w3.org/TR/html5/infrastructure.html#boolean-attributes boolean attribute}.
     * Note that in this case if the value of the attribute is false, the empty string will be returned.
     *
     * If the value of the attribute is null or an empty array,
     * the empty string will be returned as well.
     *
     * Escaping of the attribute's value takes place automatically using {@link Attribute::escapeValue()}.
     *
     * @return  string
     */
    public function render()
    {
        if ($this->isEmpty()) {
            return '';
        }

        if ($this->isBoolean()) {
            if ($this->value) {
                return $this->renderName();
            }

            return '';
        } else {
            return sprintf(
                '%s="%s"',
                $this->renderName(),
                $this->renderValue()
            );
        }
    }

    /**
     * Render the name of the attribute to HTML
     *
     * @return  string
     */
    public function renderName()
    {
        return static::escapeName($this->name);
    }

    /**
     * Render the value of the attribute to HTML
     *
     * @return  string
     */
    public function renderValue()
    {
        return static::escapeValue($this->value, $this->glue);
    }

    /**
     * Escape the name of an attribute
     *
     * Makes sure that the name of an attribute really is a string.
     *
     * @param   string  $name
     *
     * @return  string
     */
    public static function escapeName($name)
    {
        return (string) $name;
    }

    /**
     * Escape the value of an attribute
     *
     * If the value is an array, returns the string representation
     * of all array elements joined with the specified glue string.
     *
     * Values are escaped according to the HTML5 double-quoted attribute value syntax:
     * {@link https://html.spec.whatwg.org/multipage/syntax.html#attributes-2 }.
     *
     * @param   string|array    $value
     * @param   string          $glue   Glue string to join elements if value is an array
     *
     * @return  string
     */
    public static function escapeValue($value, $glue = ' ')
    {
        if (is_array($value)) {
            $value = implode($glue, $value);
        }

        // We force double-quoted attribute value syntax so let's start by escaping double quotes
        $value = str_replace('"', '&quot;', $value);

        // In addition, values must not contain ambiguous ampersands
        $value = preg_replace_callback(
            '/&[0-9A-Z]+;/i',
            function ($match) {
                $subject = $match[0];

                if (htmlspecialchars_decode($subject, ENT_COMPAT | ENT_HTML5) === $subject) {
                    // Ambiguous ampersand
                    return str_replace('&', '&amp;', $subject);
                }

                return $subject;
            },
            $value
        );

        return $value;
    }
}
