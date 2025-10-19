<?php

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

if (!defined('SYSTEM_PATH')) {
    define('SYSTEM_PATH', ROOT_PATH . '/formwork');
}

// Check PHP version requirements
if (!version_compare(PHP_VERSION, '8.3.0', '>=')) {
    require __DIR__ . '/views/errors/phpversion.php';
    exit;
}

// Check if Composer autoloader is available
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require ROOT_PATH . '/vendor/autoload.php';
} else {
    require __DIR__ . '/views/errors/install.php';
    exit;
}
