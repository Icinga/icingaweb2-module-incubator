<?php

namespace gipfl\Log;

use Composer\InstalledVersions;
use Psr\Log\LoggerInterface;
use RuntimeException;

class PsrVersion
{
    protected const SEARCH_FILES = [
        'log/src/LoggerInterface.php',
        'Psr/Log/LoggerInterface.php',
    ];
    public static function isV3(): bool
    {
        if (PHP_VERSION_ID < 80000) {
            return false;
        }

        $installed = InstalledVersions::getVersion('psr/log');
        if ($installed !== null) {
            return version_compare(InstalledVersions::getVersion('psr/log'), '3.0.0.0', '>=');
        }

        if (interface_exists(LoggerInterface::class)) {
            $length = strlen(self::SEARCH_FILES[0]); // hint: they are the same length
            foreach (get_required_files() as $filename) {
                if (in_array(substr($filename, - $length), self::SEARCH_FILES)) {
                    if (strpos(file_get_contents($filename), 'Stringable') !== false) {
                        return true;
                    } else {
                        return false;
                    }
                }
            }
        }

        throw new RuntimeException('psr/log has not been found. Please check your dependencies');
    }
}
