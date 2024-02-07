<?php

namespace gipfl\Diff;

use gipfl\Diff\PhpDiff\OpCodeHelper;
use gipfl\Diff\PhpDiff\SequenceMatcher;

class PhpDiff
{
    /** @var array The "old" sequence to use as the basis for the comparison */
    private $left;

    /** @var array The "new" sequence to generate the changes for */
    private $right;

    /** @var array contains the generated opcodes for the differences between the two items */
    private $groupedCodes;

    /**
     * @var array Associative array of the default options available for the diff class and their default value.
     */
    private $defaultOptions = [
        'context'          => 3,
        'ignoreNewLines'   => false,
        'ignoreWhitespace' => false,
        'ignoreCase'       => false
    ];

    /**
     * @var array Array of the options that have been applied for generating the diff.
     */
    private $options;

    /**
     * $left and $right can be strings, arrays of lines, null or any object that
     * can be casted to a string
     *
     * @param mixed $left Left hand (old) side of the comparison
     * @param mixed $right Right hand (new) side of the comparison
     * @param array $options see $defaultOptions for possible settings
     */
    public function __construct($left, $right, array $options = [])
    {
        $this->setLeftLines($this->wantArray($left));
        $this->setRightLines($this->wantArray($right));
        $this->options = array_merge($this->defaultOptions, $options);
    }

    /**
     * Get a range of lines from $start to $end from the first comparison string
     * and return them as an array. If no values are supplied, the entire string
     * is returned. It's also possible to specify just one line to return only
     * that line.
     *
     * @param int $start The starting number.
     * @param int $end The ending number. If not supplied, only the item in $start will be returned.
     * @return array Array of all of the lines between the specified range.
     */
    public function getLeft($start = 0, $end = null)
    {
        if ($start === 0 && $end === null) {
            return $this->left;
        }

        if ($end === null) {
            $length = 1;
        } else {
            $length = $end - $start;
        }

        return array_slice($this->left, $start, $length);
    }

    /**
     * Get a range of lines from $start to $end from the second comparison string
     * and return them as an array. If no values are supplied, the entire string
     * is returned. It's also possible to specify just one line to return only
     * that line.
     *
     * @param int $start The starting number.
     * @param int $end The ending number. If not supplied, only the item in $start will be returned.
     * @return array Array of all of the lines between the specified range.
     */
    public function getRight($start = 0, $end = null)
    {
        if ($start === 0 && $end === null) {
            return $this->right;
        }

        if ($end === null) {
            $length = 1;
        } else {
            $length = $end - $start;
        }

        return array_slice($this->right, $start, $length);
    }

    /**
     * Generate a list of the compiled and grouped opcodes for the differences between the
     * two strings. Generally called by the renderer, this class instantiates the sequence
     * matcher and performs the actual diff generation and return an array of the opcodes
     * for it. Once generated, the results are cached in the diff class instance.
     *
     * @return array Array of the grouped opcodes for the generated diff.
     */
    public function getGroupedOpcodes()
    {
        if ($this->groupedCodes === null) {
            $this->groupedCodes = $this->fetchGroupedOpCodes();
        }

        return $this->groupedCodes;
    }

    protected function fetchGroupedOpCodes()
    {
        $matcher = new SequenceMatcher($this->left, $this->right, null, $this->options);
        return OpCodeHelper::getGroupedOpcodes(
            $matcher->getOpcodes(),
            $this->options['context']
        );
    }

    protected function wantArray($value)
    {
        if (empty($value)) {
            return [];
        }
        if (! is_array($value)) {
            return explode("\n", (string) $value);
        }

        return $value;
    }

    protected function setLeftLines(array $lines)
    {
        $this->left = $lines;
    }

    protected function setRightLines(array $lines)
    {
        $this->right = $lines;
    }

}
