<?php
if (version_compare(PHP_VERSION, '5.3', '>=')) {
    $loader = require_once __DIR__ . '/vendor/autoload.php';
} else {
    require_once dirname(__FILE__) . '/vendor/splclassloader/SplClassLoader.php';

    $loader = new SplClassLoader('Snidel', dirname(__FILE__) . '/src');
    $loader->setNamespaceSeparator('_');
    $loader->register();
}
