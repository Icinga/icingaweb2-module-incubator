<?php

namespace gipfl\Diff\PhpDiff\Renderer;

use gipfl\Diff\PhpDiff;

/**
 * Abstract class for diff renderers in PHP DiffLib.
 */
abstract class AbstractRenderer
{
    /** @var PhpDiff */
    public $diff;

    /** @var array default options that apply to this renderer */
    protected $defaultOptions = [];

    /** @var array merged (user applied and default) options for the renderer */
    protected $options = [];

    /**
     * @param PhpDiff $diff
     * @param array $options Optionally, an array of the options for the renderer.
     */
    public function __construct(PhpDiff $diff, array $options = [])
    {
        $this->diff = $diff;
        $this->setOptions($options);
    }

    /**
     * Set the options of the renderer to those supplied in the passed in array.
     * Options are merged with the default to ensure that there aren't any missing
     * options.
     *
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = array_merge($this->defaultOptions, $options);
    }

    abstract public function render();
}
