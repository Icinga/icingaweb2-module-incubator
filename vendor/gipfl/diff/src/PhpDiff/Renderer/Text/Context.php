<?php

namespace gipfl\Diff\PhpDiff\Renderer\Text;

use gipfl\Diff\PhpDiff\Renderer\AbstractRenderer;

/**
 * Context diff generator for PHP DiffLib.
 */
class Context extends AbstractRenderer
{
    /**
     * @var array Array of the different opcode tags and how they map to the context diff equivalent.
     */
    private $tagMap = [
        'insert' => '+',
        'delete' => '-',
        'replace' => '!',
        'equal' => ' '
    ];

    /**
     * Render and return a context formatted (old school!) diff file.
     *
     * @return string The generated context diff.
     */
    public function render()
    {
        $diff = '';
        $opCodes = $this->diff->getGroupedOpcodes();
        foreach ($opCodes as $group) {
            $diff .= "***************\n";
            $lastItem = count($group)-1;
            $i1 = $group[0][1];
            $i2 = $group[$lastItem][2];
            $j1 = $group[0][3];
            $j2 = $group[$lastItem][4];

            if ($i2 - $i1 >= 2) {
                $diff .= '*** '.($group[0][1] + 1).','.$i2." ****\n";
            } else {
                $diff .= '*** '.$i2." ****\n";
            }

            if ($j2 - $j1 >= 2) {
                $separator = '--- '.($j1 + 1).','.$j2." ----\n";
            } else {
                $separator = '--- '.$j2." ----\n";
            }

            $hasVisible = false;
            foreach ($group as $code) {
                if ($code[0] === 'replace' || $code[0] === 'delete') {
                    $hasVisible = true;
                    break;
                }
            }

            if ($hasVisible) {
                foreach ($group as $code) {
                    list($tag, $i1, $i2, $j1, $j2) = $code;
                    if ($tag === 'insert') {
                        continue;
                    }
                    $diff .= $this->tagMap[$tag]
                        . ' '
                        . implode("\n" . $this->tagMap[$tag] . ' ', $this->diff->getLeft($i1, $i2))
                        . "\n";
                }
            }

            $hasVisible = false;
            foreach ($group as $code) {
                if ($code[0] === 'replace' || $code[0] === 'insert') {
                    $hasVisible = true;
                    break;
                }
            }

            $diff .= $separator;

            if ($hasVisible) {
                foreach ($group as $code) {
                    list($tag, $i1, $i2, $j1, $j2) = $code;
                    if ($tag === 'delete') {
                        continue;
                    }
                    $diff .= $this->tagMap[$tag]
                        . ' '
                        . implode("\n" . $this->tagMap[$tag] . ' ', $this->diff->getRight($j1, $j2))
                        . "\n";
                }
            }
        }

        return $diff;
    }
}
