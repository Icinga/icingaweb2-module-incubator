<?php

namespace gipfl\Diff\PhpDiff;

use function array_merge;
use function count;
use function is_array;
use function str_replace;
use function str_split;
use function strtolower;

/**
 * Sequence matcher for Diff
 */
class SequenceMatcher
{
    /**
     * Either a string or an array containing a callback function to determine
     * if a line is "junk" or not
     *
     * @var string|array
     */
    private $junkCallback;

    /**
     * @var array The first sequence to compare against.
     */
    private $left = [];

    /**
     * @var array The second sequence.
     */
    private $right = [];

    /**
     * @var array Characters that are considered junk from the second sequence. Characters are the array key.
     */
    private $junkCharacters = [];

    /**
     * @var array Array of indices that do not contain junk elements.
     */
    private $b2j = [];

    private $options = [];

    private $defaultOptions = [
        'ignoreNewLines' => false,
        'ignoreWhitespace' => false,
        'ignoreCase' => false
    ];

    /** @var array|null */
    private $matchingBlocks;

    /** @var array|null */
    private $opCodes;

    /**
     * The constructor. With the sequences being passed, they'll be set for the
     * sequence matcher and it will perform a basic cleanup & calculate junk
     * elements.
     *
     * @param string|array $left A string or array containing the lines to compare against.
     * @param string|array $right A string or array containing the lines to compare.
     * @param string|array $junkCallback Either an array or string that references a callback
     *                     function (if there is one) to determine 'junk' characters.
     * @param array $options
     */
    public function __construct($left, $right, $junkCallback = null, $options = [])
    {
        $this->junkCallback = $junkCallback;
        $this->setOptions($options);
        $this->setSequences($left, $right);
    }

    public function setOptions($options)
    {
        $this->options = array_merge($this->defaultOptions, $options);
    }

    /**
     * Set the first and second sequences to use with the sequence matcher.
     *
     * @param string|array $left A string or array containing the lines to compare against.
     * @param string|array $right A string or array containing the lines to compare.
     */
    public function setSequences($left, $right)
    {
        $this->setLeftSequence($left);
        $this->setRightSequence($right);
    }

    /**
     * Set the first sequence and reset any internal caches to indicate that
     * when calling the calculation methods, we need to recalculate them.
     *
     * @param string|array $sequence The sequence to set as the first sequence.
     */
    protected function setLeftSequence($sequence)
    {
        if (!is_array($sequence)) {
            $sequence = str_split($sequence);
        }
        if ($sequence === $this->left) {
            return;
        }

        $this->resetCalculation();
        $this->left = $sequence;
    }

    /**
     * Set the second sequence ($b) and reset any internal caches to indicate that
     * when calling the calculation methods, we need to recalculate them.
     *
     * @param string|array $sequence The sequence to set as the second sequence.
     */
    protected function setRightSequence($sequence)
    {
        if (!is_array($sequence)) {
            $sequence = str_split($sequence);
        }
        if ($sequence === $this->right) {
            return;
        }

        $this->resetCalculation();
        $this->right = $sequence;
        $this->generateRightChain();
    }

    protected function resetCalculation()
    {
        $this->matchingBlocks = null;
        $this->opCodes = null;
    }

    /**
     * @return array
     */
    public function getLeftSequence()
    {
        return $this->left;
    }

    /**
     * @return array
     */
    public function getRightSequence()
    {
        return $this->right;
    }

    /**
     * Generate the internal arrays containing the list of junk and non-junk
     * characters for the second ($b) sequence.
     */
    private function generateRightChain()
    {
        $length = count($this->right);
        $this->b2j = [];
        $popularDict = [];

        foreach ($this->right as $i => $char) {
            if (isset($this->b2j[$char])) {
                if ($length >= 200 && count($this->b2j[$char]) * 100 > $length) {
                    $popularDict[$char] = 1;
                    unset($this->b2j[$char]);
                } else {
                    $this->b2j[$char][] = $i;
                }
            } else {
                $this->b2j[$char] = [$i];
            }
        }

        // Remove leftovers
        foreach (array_keys($popularDict) as $char) {
            unset($this->b2j[$char]);
        }

        $this->junkCharacters = [];
        if (is_callable($this->junkCallback)) {
            foreach (array_keys($popularDict) as $char) {
                if (call_user_func($this->junkCallback, $char)) {
                    $this->junkCharacters[$char] = 1;
                    unset($popularDict[$char]);
                }
            }

            foreach (array_keys($this->b2j) as $char) {
                if (call_user_func($this->junkCallback, $char)) {
                    $this->junkCharacters[$char] = 1;
                    unset($this->b2j[$char]);
                }
            }
        }
    }

    /**
     * Checks if a particular character is in the junk dictionary
     * for the list of junk characters.
     *
     * @param $b
     * @return boolean whether the character is considered junk
     */
    private function isBJunk($b)
    {
        if (isset($this->junkCharacters[$b])) {
            return true;
        }

        return false;
    }

    /**
     * Find the longest matching block in the two sequences, as defined by the
     * lower and upper constraints for each sequence. (for the first sequence,
     * $alo - $ahi and for the second sequence, $blo - $bhi)
     *
     * Essentially, of all of the maximal matching blocks, return the one that
     * starts earliest in $a, and all of those maximal matching blocks that
     * start earliest in $a, return the one that starts earliest in $b.
     *
     * If the junk callback is defined, do the above but with the restriction
     * that the junk element appears in the block. Extend it as far as possible
     * by matching only junk elements in both $a and $b.
     *
     * @param int $beginLeft The lower constraint for the first sequence.
     * @param int $endLeft The upper constraint for the first sequence.
     * @param int $beginRight The lower constraint for the second sequence.
     * @param int $endRight The upper constraint for the second sequence.
     * @return array Array containing the longest match that includes the starting
     *               position in $a, start in $b and the length/size.
     */
    public function findLongestMatch($beginLeft, $endLeft, $beginRight, $endRight)
    {
        $left = $this->left;
        $right = $this->right;

        $bestBeginLeft = $beginLeft;
        $bestBeginRight = $beginRight;
        $bestSize = 0;

        $j2Len = [];
        $nothing = [];

        for ($currentLeft = $beginLeft; $currentLeft < $endLeft; ++$currentLeft) {
            $newJ2Len = [];
            $junkList = ArrayHelper::getPropertyOrDefault($this->b2j, $left[$currentLeft], $nothing);
            foreach ($junkList as $junk) {
                if ($junk < $beginRight) {
                    continue;
                }
                if ($junk >= $endRight) {
                    break;
                }

                $k = ArrayHelper::getPropertyOrDefault($j2Len, $junk -1, 0) + 1;
                $newJ2Len[$junk] = $k;
                if ($k > $bestSize) {
                    $bestBeginLeft = $currentLeft - $k + 1;
                    $bestBeginRight = $junk - $k + 1;
                    $bestSize = $k;
                }
            }

            $j2Len = $newJ2Len;
        }

        while ($bestBeginLeft > $beginLeft
            && $bestBeginRight > $beginRight
            && !$this->isBJunk($right[$bestBeginRight - 1])
            && !$this->linesAreDifferent($bestBeginLeft - 1, $bestBeginRight - 1)
        ) {
            --$bestBeginLeft;
            --$bestBeginRight;
            ++$bestSize;
        }

        while ($bestBeginLeft + $bestSize < $endLeft && ($bestBeginRight + $bestSize) < $endRight
            && !$this->isBJunk($right[$bestBeginRight + $bestSize])
            && !$this->linesAreDifferent($bestBeginLeft + $bestSize, $bestBeginRight + $bestSize)
        ) {
            ++$bestSize;
        }

        while ($bestBeginLeft > $beginLeft
            && $bestBeginRight > $beginRight
            && $this->isBJunk($right[$bestBeginRight - 1])
            && !$this->linesAreDifferent($bestBeginLeft - 1, $bestBeginRight - 1)
        ) {
            --$bestBeginLeft;
            --$bestBeginRight;
            ++$bestSize;
        }

        while ($bestBeginLeft + $bestSize < $endLeft
            && $bestBeginRight + $bestSize < $endRight
            && $this->isBJunk($right[$bestBeginRight + $bestSize])
            && !$this->linesAreDifferent($bestBeginLeft + $bestSize, $bestBeginRight + $bestSize)
        ) {
            ++$bestSize;
        }

        return [$bestBeginLeft, $bestBeginRight, $bestSize];
    }

    /**
     * Check if the two lines at the given indexes are different or not.
     *
     * @param int $leftIndex Line number to check against in a.
     * @param int $rightIndex Line number to check against in b.
     * @return boolean True if the lines are different and false if not.
     */
    public function linesAreDifferent($leftIndex, $rightIndex)
    {
        $leftLine = $this->left[$leftIndex];
        $rightLine = $this->right[$rightIndex];

        if ($this->options['ignoreWhitespace']) {
            $replace = ["\t", ' '];
            $leftLine = str_replace($replace, '', $leftLine);
            $rightLine = str_replace($replace, '', $rightLine);
        }

        if ($this->options['ignoreCase']) {
            $leftLine = strtolower($leftLine);
            $rightLine = strtolower($rightLine);
        }

        return $leftLine !== $rightLine;
    }

    /**
     * Return a nested set of arrays for all of the matching sub-sequences
     * in the strings $a and $b.
     *
     * Each block contains the lower constraint of the block in $a, the lower
     * constraint of the block in $b and finally the number of lines that the
     * block continues for.
     *
     * @return array Nested array of the matching blocks, as described by the function.
     */
    public function getMatchingBlocks()
    {
        if ($this->matchingBlocks === null) {
            $this->matchingBlocks = $this->calculateMatchingBlocks();
        }

        return $this->matchingBlocks;
    }

    public function calculateMatchingBlocks()
    {
        $leftLength = count($this->left);
        $rightLength = count($this->right);

        $queue = [
            [0, $leftLength, 0, $rightLength]
        ];

        $matchingBlocks = [];
        while (!empty($queue)) {
            list($leftBegin, $leftEnd, $rightBegin, $rightEnd) = array_pop($queue);
            $block = $this->findLongestMatch($leftBegin, $leftEnd, $rightBegin, $rightEnd);
            list($bestBeginLeft, $bestBeginRight, $bestSize) = $block;
            if ($bestSize) {
                $matchingBlocks[] = $block;
                if ($leftBegin < $bestBeginLeft && $rightBegin < $bestBeginRight) {
                    $queue[] = [
                        $leftBegin,
                        $bestBeginLeft,
                        $rightBegin,
                        $bestBeginRight
                    ];
                }

                if ($bestBeginLeft + $bestSize < $leftEnd && $bestBeginRight + $bestSize < $rightEnd) {
                    $queue[] = [
                        $bestBeginLeft + $bestSize,
                        $leftEnd,
                        $bestBeginRight + $bestSize,
                        $rightEnd
                    ];
                }
            }
        }

        usort($matchingBlocks, [ArrayHelper::class, 'tupleSort']);

        return static::getNonAdjacentBlocks($matchingBlocks, $leftLength, $rightLength);
    }

    public function getOpcodes()
    {
        if ($this->opCodes === null) {
            $this->opCodes = OpCodeHelper::calculateOpCodes($this->getMatchingBlocks());
        }

        return $this->opCodes;
    }

    /**
     * @param array $matchingBlocks
     * @param $leftLength
     * @param $rightLength
     * @return array
     */
    protected static function getNonAdjacentBlocks(array $matchingBlocks, $leftLength, $rightLength)
    {
        $newLeft = 0;
        $newRight = 0;
        $newCnt = 0;
        $nonAdjacent = [];
        foreach ($matchingBlocks as list($beginLeft, $beginRight, $cntLines)) {
            if ($newLeft + $newCnt === $beginLeft && $newRight + $newCnt === $beginRight) {
                $newCnt += $cntLines;
            } else {
                if ($newCnt) {
                    $nonAdjacent[] = [$newLeft, $newRight, $newCnt];
                }

                $newLeft = $beginLeft;
                $newRight = $beginRight;
                $newCnt = $cntLines;
            }
        }

        if ($newCnt) {
            $nonAdjacent[] = [$newLeft, $newRight, $newCnt];
        }

        $nonAdjacent[] = [$leftLength, $rightLength, 0];
        return $nonAdjacent;
    }
}
