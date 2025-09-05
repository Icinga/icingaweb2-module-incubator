<?php

namespace gipfl\Diff\PhpDiff;

abstract class ArrayHelper
{
    /**
     * Helper function that provides the ability to return the value for a key
     * in an array of it exists, or if it doesn't then return a default value.
     * Essentially cleaner than doing a series of if(isset()) {} else {} calls.
     *
     * @param array $array The array to search.
     * @param string $key The key to check that exists.
     * @param mixed $default The value to return as the default value if the key doesn't exist.
     * @return mixed The value from the array if the key exists or otherwise the default.
     */
    public static function getPropertyOrDefault($array, $key, $default)
    {
        if (isset($array[$key])) {
            return $array[$key];
        }

        return $default;
    }

    /**
     * Sort an array by the nested arrays it contains. Helper function for getMatchingBlocks
     *
     * @param array $a First array to compare.
     * @param array $b Second array to compare.
     * @return int -1, 0 or 1, as expected by the usort function.
     */
    public static function tupleSort($a, $b)
    {
        $max = max(count($a), count($b));
        for ($i = 0; $i < $max; ++$i) {
            if ($a[$i] < $b[$i]) {
                return -1;
            }
            if ($a[$i] > $b[$i]) {
                return 1;
            }
        }

        if (count($a) === count($b)) {
            return 0;
        }
        if (count($a) < count($b)) {
            return -1;
        }

        return 1;
    }
}
