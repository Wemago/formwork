<?php

// Check PHP version requirements
if (!version_compare(PHP_VERSION, '8.0.2', '>=')) {
    require __DIR__ . '/views/errors/phpversion.php';
    exit;
}

// Check if Composer autoloader is available
if (file_exists($autoload = ROOT_PATH . '/vendor/autoload.php')) {
    require $autoload;
} else {
    require __DIR__ . '/views/errors/install.php';
    exit;
}
