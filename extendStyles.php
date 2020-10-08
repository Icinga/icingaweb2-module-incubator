<?php /** @noinspection PhpMissingParentConstructorInspection */

namespace Icinga\Module\Incubator;

use Icinga\Application\Icinga;
use Icinga\Web\StyleSheet;

class Styles extends StyleSheet
{
    public function __construct()
    {
        $pubPath = Icinga::app()->getBaseDir('public');
        $slashes = \substr_count($pubPath, '/');
        // TODO: combine at build time / with composer
        $prefix = \str_repeat('/..', $slashes) . __DIR__;
        $files = [
            "$prefix/public/css/combined.less",
        ];

        foreach ($files as $file) {
            self::$lessFiles[] = $file;
        }
    }
}
