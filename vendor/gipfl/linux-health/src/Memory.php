<?php

namespace gipfl\LinuxHealth;

class Memory
{
    protected static $pageSize;

    public static function getUsageForPid($pid)
    {
        $pid = (int) $pid;
        $content = @file_get_contents("/proc/$pid/statm");
        if ($content === false) {
            return false;
        }

        $pageSize = static::getPageSize();
        if ($pageSize === null) {
            return false;
        }
        $parts = explode(' ', $content);

        return (object) [
            'size'   => $pageSize * (int) $parts[0],
            'rss'    => $pageSize * (int) $parts[1],
            'shared' => $pageSize * (int) $parts[3],
        ];
    }

    /**
     * @return int
     */
    public static function getPageSize()
    {
        if (self::$pageSize === null) {
            $output = trim(`getconf PAGESIZE 2>&1`);
            if (strlen($output)) {
                self::$pageSize = (int) $output;
            }
        }

        return self::$pageSize;
    }

    /**
     * @param int $pageSize
     */
    public static function setPageSize($pageSize)
    {
        self::$pageSize = (int) $pageSize;
    }
}
