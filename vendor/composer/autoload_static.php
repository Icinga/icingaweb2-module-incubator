<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInita4748dec1ae563e0f63d28f46bfd6eda
{
    public static $prefixLengthsPsr4 = array (
        'g' => 
        array (
            'gipfl\\ZfDb\\' => 11,
            'gipfl\\ZfDbStore\\' => 16,
            'gipfl\\Web\\' => 10,
            'gipfl\\Translation\\' => 18,
            'gipfl\\SystemD\\' => 14,
            'gipfl\\Stream\\' => 13,
            'gipfl\\Socket\\' => 13,
            'gipfl\\SimpleDaemon\\' => 19,
            'gipfl\\ReactUtils\\' => 17,
            'gipfl\\Protocol\\NetString\\' => 25,
            'gipfl\\Protocol\\JsonRpc\\' => 23,
            'gipfl\\Protocol\\Generic\\' => 23,
            'gipfl\\Protocol\\Exception\\' => 25,
            'gipfl\\Process\\' => 14,
            'gipfl\\OpenRpc\\' => 14,
            'gipfl\\Log\\' => 10,
            'gipfl\\LinuxHealth\\' => 18,
            'gipfl\\Json\\' => 11,
            'gipfl\\InfluxDb\\' => 15,
            'gipfl\\IcingaWeb2\\' => 17,
            'gipfl\\IcingaCliDaemon\\' => 22,
            'gipfl\\Format\\' => 13,
            'gipfl\\Diff\\' => 11,
            'gipfl\\DbMigration\\' => 18,
            'gipfl\\DataType\\' => 15,
            'gipfl\\Curl\\' => 11,
            'gipfl\\Cli\\' => 10,
            'gipfl\\Calendar\\' => 15,
        ),
        'P' => 
        array (
            'Psr\\Log\\' => 8,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'gipfl\\ZfDb\\' => 
        array (
            0 => __DIR__ . '/..' . '/gipfl/zfdb/src',
        ),
        'gipfl\\ZfDbStore\\' => 
        array (
            0 => __DIR__ . '/..' . '/gipfl/zfdbstore/src',
        ),
        'gipfl\\Web\\' => 
        array (
            0 => __DIR__ . '/..' . '/gipfl/web/src',
        ),
        'gipfl\\Translation\\' => 
        array (
            0 => __DIR__ . '/..' . '/gipfl/translation/src',
        ),
        'gipfl\\SystemD\\' => 
        array (
            0 => __DIR__ . '/..' . '/gipfl/systemd/src',
        ),
        'gipfl\\Stream\\' => 
        array (
            0 => __DIR__ . '/..' . '/gipfl/stream/src',
        ),
        'gipfl\\Socket\\' => 
        array (
            0 => __DIR__ . '/..' . '/gipfl/socket/src',
        ),
        'gipfl\\SimpleDaemon\\' => 
        array (
            0 => __DIR__ . '/..' . '/gipfl/simple-daemon/src',
        ),
        'gipfl\\ReactUtils\\' => 
        array (
            0 => __DIR__ . '/..' . '/gipfl/react-utils/src',
        ),
        'gipfl\\Protocol\\NetString\\' => 
        array (
            0 => __DIR__ . '/..' . '/gipfl/protocol-netstring/src',
        ),
        'gipfl\\Protocol\\JsonRpc\\' => 
        array (
            0 => __DIR__ . '/..' . '/gipfl/protocol-jsonrpc/src',
        ),
        'gipfl\\Protocol\\Generic\\' => 
        array (
            0 => __DIR__ . '/..' . '/gipfl/protocol/src/Generic',
        ),
        'gipfl\\Protocol\\Exception\\' => 
        array (
            0 => __DIR__ . '/..' . '/gipfl/protocol/src/Exception',
        ),
        'gipfl\\Process\\' => 
        array (
            0 => __DIR__ . '/..' . '/gipfl/process/src',
        ),
        'gipfl\\OpenRpc\\' => 
        array (
            0 => __DIR__ . '/..' . '/gipfl/openrpc/src',
        ),
        'gipfl\\Log\\' => 
        array (
            0 => __DIR__ . '/..' . '/gipfl/log/src',
        ),
        'gipfl\\LinuxHealth\\' => 
        array (
            0 => __DIR__ . '/..' . '/gipfl/linux-health/src',
        ),
        'gipfl\\Json\\' => 
        array (
            0 => __DIR__ . '/..' . '/gipfl/json/src',
        ),
        'gipfl\\InfluxDb\\' => 
        array (
            0 => __DIR__ . '/..' . '/gipfl/influxdb/src',
        ),
        'gipfl\\IcingaWeb2\\' => 
        array (
            0 => __DIR__ . '/..' . '/gipfl/icingaweb2/src',
        ),
        'gipfl\\IcingaCliDaemon\\' => 
        array (
            0 => __DIR__ . '/..' . '/gipfl/icinga-cli-daemon/src',
        ),
        'gipfl\\Format\\' => 
        array (
            0 => __DIR__ . '/..' . '/gipfl/format/src',
        ),
        'gipfl\\Diff\\' => 
        array (
            0 => __DIR__ . '/..' . '/gipfl/diff/src',
        ),
        'gipfl\\DbMigration\\' => 
        array (
            0 => __DIR__ . '/..' . '/gipfl/db-migration/src',
        ),
        'gipfl\\DataType\\' => 
        array (
            0 => __DIR__ . '/..' . '/gipfl/data-type/src',
        ),
        'gipfl\\Curl\\' => 
        array (
            0 => __DIR__ . '/..' . '/gipfl/curl/src',
        ),
        'gipfl\\Cli\\' => 
        array (
            0 => __DIR__ . '/..' . '/gipfl/cli/src',
        ),
        'gipfl\\Calendar\\' => 
        array (
            0 => __DIR__ . '/..' . '/gipfl/calendar/src',
        ),
        'Psr\\Log\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/log/Psr/Log',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInita4748dec1ae563e0f63d28f46bfd6eda::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInita4748dec1ae563e0f63d28f46bfd6eda::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInita4748dec1ae563e0f63d28f46bfd6eda::$classMap;

        }, null, ClassLoader::class);
    }
}
