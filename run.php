<?php

use Icinga\Module\Incubator\Styles;

// We extend Less files shipped per default
if (isset($_SERVER['REQUEST_URI'])
    && preg_match('#/css/icinga(?:\.min)?\.css#', $_SERVER['REQUEST_URI'])
    && ! class_exists(__NAMESPACE__ . '\\Styles')) {
    require __DIR__ . '/extendStyles.php';
    $styles = new Styles();
    return;
}

require_once __DIR__ . '/vendor/autoload.php';
