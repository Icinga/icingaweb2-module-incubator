<?php

namespace gipfl\Diff\PhpDiff;

use function count;

class Ratio
{
    /**
     * @var SequenceMatcher
     */
    private $matcher;

    /** @var float */
    private $ratio;

    /** @var array */
    private $a;

    /** @var array */
    private $b;

    /** @var array */
    private $fullBCount;

    public function __construct(SequenceMatcher $matcher)
    {
        $this->matcher = $matcher;
        $this->a = $matcher->getLeftSequence();
        $this->b = $matcher->getRightSequence();
    }

    /**
     * Return a measure of the similarity between the two sequences.
     * This will be a float value between 0 and 1.
     *
     * Out of all of the ratio calculation functions, this is the most
     * expensive to call if getMatchingBlocks or getOpCodes is yet to be
     * called. The other calculation methods (quickRatio and realquickRatio)
     * can be used to perform quicker calculations but may be less accurate.
     *
     * The ratio is calculated as (2 * number of matches) / total number of
     * elements in both sequences.
     *
     * @return float The calculated ratio.
     */
    public function getRatio()
    {
        if ($this->ratio === null) {
            $matcher = $this->matcher;
            $matches = array_reduce($matcher->getMatchingBlocks(), [$this, 'ratioReduce'], 0);
            $this->ratio = $this->calculateRatio(
                $matches,
                count($this->a) + count($this->b)
            );
        }

        return $this->ratio;
    }

    /**
     * Helper function to calculate the number of matches for Ratio().
     *
     * @param int $sum The running total for the number of matches.
     * @param array $triple Array containing the matching block triple to add to the running total.
     * @return int The new running total for the number of matches.
     */
    private function ratioReduce($sum, $triple)
    {
        return $sum + ($triple[count($triple) - 1]);
    }

    /**
     * Quickly return an upper bound ratio for the similarity of the strings.
     * This is quicker to compute than Ratio().
     *
     * @return float The calculated ratio.
     */
    private function quickRatio()
    {
        $aLength = count($this->a);
        $bLength = count($this->b);
        if ($this->fullBCount === null) {
            $this->fullBCount = [];
            for ($i = 0; $i < $bLength; ++$i) {
                $char = $this->b[$i];
                $this->fullBCount[$char] = ArrayHelper::getPropertyOrDefault($this->fullBCount, $char, 0) + 1;
            }
        }

        $avail = array();
        $matches = 0;
        for ($i = 0; $i < $aLength; ++$i) {
            $char = $this->a[$i];
            if (isset($avail[$char])) {
                $numb = $avail[$char];
            } else {
                $numb = ArrayHelper::getPropertyOrDefault($this->fullBCount, $char, 0);
            }
            $avail[$char] = $numb - 1;
            if ($numb > 0) {
                ++$matches;
            }
        }

        $this->calculateRatio($matches, $aLength + $bLength);
    }

    /**
     * Return an upper bound ratio really quickly for the similarity of the strings.
     * This is quicker to compute than Ratio() and quickRatio().
     *
     * @return float The calculated ratio.
     */
    private function realquickRatio()
    {
        $aLength = count($this->a);
        $bLength = count($this->b);

        return $this->calculateRatio(min($aLength, $bLength), $aLength + $bLength);
    }

    /**
     * Helper function for calculating the ratio to measure similarity for the strings.
     * The ratio is defined as being 2 * (number of matches / total length)
     *
     * @param int $matches The number of matches in the two strings.
     * @param int $length The length of the two strings.
     * @return float The calculated ratio.
     */
    private function calculateRatio($matches, $length = 0)
    {
        if ($length) {
            return 2 * ($matches / $length);
        }

        return 1;
    }
}
