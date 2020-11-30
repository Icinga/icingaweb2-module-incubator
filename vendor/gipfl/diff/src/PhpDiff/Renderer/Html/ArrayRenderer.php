<?php

namespace gipfl\Diff\PhpDiff\Renderer\Html;

use gipfl\Diff\PhpDiff\Renderer\AbstractRenderer;

/**
 * Base renderer for rendering HTML based diffs for PHP DiffLib.
 */
class ArrayRenderer extends AbstractRenderer
{
    /** @var array default options */
    protected $defaultOptions = [
        'tabSize' => 4
    ];

    /**
     * Render and return an array structure suitable for generating HTML
     * based differences. Generally called by subclasses that generate a
     * HTML based diff and return an array of the changes to show in the diff.
     *
     * @return array An array of the generated chances, suitable for presentation in HTML.
     */
    public function render()
    {
        // As we'll be modifying a & b to include our change markers,
        // we need to get the contents and store them here. That way
        // we're not going to destroy the original data
        $a = $this->diff->getLeft();
        $b = $this->diff->getRight();

        $changes = [];
        foreach ($this->diff->getGroupedOpcodes() as $group) {
            $changes[] = $this->renderOpcodesGroup($group, $a, $b);
        }
        return $changes;
    }

    protected function insertLineMarkers($line, $start, $end)
    {
        $last = $end + mb_strlen($line);

        return mb_substr($line, 0, $start)
            . "\0"
            . mb_substr($line, $start, $last - $start)
            . "\1"
            . mb_substr($line, $last);
    }

    /**
     * @param $group
     * @param array $a
     * @param array $b
     * @return array
     */
    protected function renderOpcodesGroup($group, array $a, array $b)
    {
        $blocks = [];
        $lastTag = null;
        $lastBlock = 0;
        foreach ($group as list($tag, $i1, $i2, $j1, $j2)) {
            if ($tag === 'replace' && $i2 - $i1 === $j2 - $j1) {
                for ($i = 0; $i < ($i2 - $i1); ++$i) {
                    $fromLine = $a[$i1 + $i];
                    $toLine = $b[$j1 + $i];

                    list($start, $end) = $this->getChangeExtent($fromLine, $toLine);
                    if ($start !== 0 || $end !== 0) {
                        $a[$i1 + $i] = $this->insertLineMarkers($fromLine, $start, $end);
                        $b[$j1 + $i] = $this->insertLineMarkers($toLine, $start, $end);
                    }
                }
            }

            if ($tag !== $lastTag) {
                $blocks[] = [
                    'tag' => $tag,
                    'base' => [
                        'offset' => $i1,
                        'lines' => []
                    ],
                    'changed' => [
                        'offset' => $j1,
                        'lines' => []
                    ]
                ];
                $lastBlock = count($blocks) - 1;
            }

            $lastTag = $tag;

            if ($tag === 'equal') {
                $lines = array_slice($a, $i1, ($i2 - $i1));
                $blocks[$lastBlock]['base']['lines'] += $this->formatLines($lines);
                $lines = array_slice($b, $j1, ($j2 - $j1));
                $blocks[$lastBlock]['changed']['lines'] += $this->formatLines($lines);
            } else {
                if ($tag === 'replace' || $tag === 'delete') {
                    $lines = array_slice($a, $i1, ($i2 - $i1));
                    $lines = $this->formatLines($lines);
                    $lines = str_replace(array("\0", "\1"), array('<del>', '</del>'), $lines);
                    $blocks[$lastBlock]['base']['lines'] += $lines;
                }

                if ($tag === 'replace' || $tag === 'insert') {
                    $lines = array_slice($b, $j1, ($j2 - $j1));
                    $lines = $this->formatLines($lines);
                    $lines = str_replace(array("\0", "\1"), array('<ins>', '</ins>'), $lines);
                    $blocks[$lastBlock]['changed']['lines'] += $lines;
                }
            }
        }

        return $blocks;
    }

    /**
     * Given two strings, determine where the changes in the two strings
     * begin, and where the changes in the two strings end.
     *
     * @param string $fromLine The first string.
     * @param string $toLine The second string.
     * @return array Array containing the starting position (0 by default) and the ending position (-1 by default)
     */
    private function getChangeExtent($fromLine, $toLine)
    {
        $start = 0;
        $limit = min(strlen($fromLine), strlen($toLine));
        while ($start < $limit && $fromLine[$start] === $toLine[$start]) {
            ++$start;
        }
        $end = -1;
        $limit -= $start;
        while (-$end <= $limit && $fromLine[$end] === $toLine[$end]) {
            --$end;
        }
        return [
            $start,
            $end + 1
        ];
    }

    /**
     * Format a series of lines suitable for output in a HTML rendered diff.
     * This involves replacing tab characters with spaces, making the HTML safe
     * for output, ensuring that double spaces are replaced with &nbsp; etc.
     *
     * @param array $lines lines to format.
     * @return array formatted lines.
     */
    protected function formatLines($lines)
    {
        $lines = array_map([$this, 'ExpandTabs'], $lines);
        $lines = array_map([$this, 'HtmlSafe'], $lines);
        foreach ($lines as &$line) {
            $line = preg_replace_callback('# ( +)|^ #', [$this, 'fixSpaces'], $line);
        }
        return $lines;
    }

    /**
     * Replace a string containing spaces with a HTML representation using &nbsp;.
     *
     * @param string[] $matches preg matches.
     * @return string HTML representation of the string.
     */
    private function fixSpaces(array $matches)
    {
        $count = 0;

        if (count($matches) > 1) {
            $spaces = $matches[1];
            $count = strlen($spaces);
        }

        if ($count === 0) {
            return '';
        }

        $div = floor($count / 2);
        $mod = $count % 2;
        return str_repeat('&nbsp; ', $div).str_repeat('&nbsp;', $mod);
    }

    /**
     * Replace tabs in a single line with a number of spaces as defined by the tabSize option.
     *
     * @param string $line The containing tabs to convert.
     * @return string The line with the tabs converted to spaces.
     */
    private function expandTabs($line)
    {
        return str_replace("\t", str_repeat(' ', $this->options['tabSize']), $line);
    }

    /**
     * Make a string containing HTML safe for output on a page.
     *
     * @param string $string The string.
     * @return string The string with the HTML characters replaced by entities.
     */
    private function htmlSafe($string)
    {
        return htmlspecialchars($string, ENT_NOQUOTES, 'UTF-8');
    }
}
