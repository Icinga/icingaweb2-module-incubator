<?php

namespace gipfl\Diff\PhpDiff;

use function count;
use function max;
use function min;

abstract class OpCodeHelper
{
    /**
     * Return a list of all of the opcodes for the differences between the
     * two strings.
     *
     * The nested array returned contains an array describing the opcode
     * which includes:
     * 0 - The type of tag (as described below) for the opcode.
     * 1 - The beginning line in the first sequence.
     * 2 - The end line in the first sequence.
     * 3 - The beginning line in the second sequence.
     * 4 - The end line in the second sequence.
     *
     * The different types of tags include:
     * replace - The string from $i1 to $i2 in $a should be replaced by
     *           the string in $b from $j1 to $j2.
     * delete -  The string in $a from $i1 to $j2 should be deleted.
     * insert -  The string in $b from $j1 to $j2 should be inserted at
     *           $i1 in $a.
     * equal  -  The two strings with the specified ranges are equal.
     *
     * @param array $blocks
     * @return array Array of the opcodes describing the differences between the strings.
     */
    public static function calculateOpCodes(array $blocks)
    {
        $lastLeftEnd = 0;
        $lastRightEnd = 0;
        $opCodes = [];

        foreach ($blocks as list($beginLeft, $beginRight, $cntLines)) {
            $tag = null;
            if ($lastLeftEnd < $beginLeft) {
                if ($lastRightEnd < $beginRight) {
                    $tag = 'replace';
                } else {
                    $tag = 'delete';
                }
            } elseif ($lastRightEnd < $beginRight) {
                $tag = 'insert';
            }

            if ($tag) {
                $opCodes[] = [$tag, $lastLeftEnd, $beginLeft, $lastRightEnd, $beginRight];
            }

            $lastLeftEnd = $beginLeft + $cntLines;
            $lastRightEnd = $beginRight + $cntLines;

            if ($cntLines) {
                $opCodes[] = ['equal', $beginLeft, $lastLeftEnd, $beginRight, $lastRightEnd];
            }
        }

        return $opCodes;
    }

    /**
     * Return a series of nested arrays containing different groups of generated
     * opcodes for the differences between the strings with up to $context lines
     * of surrounding content.
     *
     * Essentially what happens here is any big equal blocks of strings are stripped
     * out, the smaller subsets of changes are then arranged in to their groups.
     * This means that the sequence matcher and diffs do not need to include the full
     * content of the different files but can still provide context as to where the
     * changes are.
     *
     * @param array $opCodes
     * @param int $context The number of lines of context to provide around the groups.
     * @return array Nested array of all of the grouped opcodes.
     */
    public static function getGroupedOpcodes(array $opCodes, $context = 3)
    {
        if (empty($opCodes)) {
            $opCodes = [
                ['equal', 0, 1, 0, 1]
            ];
        }

        if ($opCodes[0][0] === 'equal') {
            $opCodes[0] = [
                $opCodes[0][0],
                max($opCodes[0][1], $opCodes[0][2] - $context),
                $opCodes[0][2],
                max($opCodes[0][3], $opCodes[0][4] - $context),
                $opCodes[0][4]
            ];
        }

        $lastItem = count($opCodes) - 1;
        if ($opCodes[$lastItem][0] === 'equal') {
            list($tag, $beginLeft, $endLeft, $beginRight, $endRight) = $opCodes[$lastItem];
            $opCodes[$lastItem] = [
                $tag,
                $beginLeft,
                min($endLeft, $beginLeft + $context),
                $beginRight,
                min($endRight, $beginRight + $context)
            ];
        }
        /*
        public $type;
        public $beginLeft;
        public $endLeft;
        public $beginRight;
        public $endRight;
        */
        $maxRange = $context * 2;
        $groups = [];
        $group = [];
        foreach ($opCodes as list($tag, $beginLeft, $endLeft, $beginRight, $endRight)) {
            if ($tag === 'equal' && $endLeft - $beginLeft > $maxRange) {
                $group[] = [
                    $tag,
                    $beginLeft,
                    min($endLeft, $beginLeft + $context),
                    $beginRight,
                    min($endRight, $beginRight + $context)
                ];
                $groups[] = $group;
                $group = [];
                $beginLeft = max($beginLeft, $endLeft - $context);
                $beginRight = max($beginRight, $endRight - $context);
            }
            $group[] = [$tag, $beginLeft, $endLeft, $beginRight, $endRight];
        }

        if (!empty($group) && !(count($group) === 1 && $group[0][0] === 'equal')) {
            $groups[] = $group;
        }

        return $groups;
    }
}
