<?php

use Formwork\Admin\Admin;
use Formwork\Core\Formwork;

define('DS', DIRECTORY_SEPARATOR);
define('ROOT_PATH', dirname(__DIR__) . DS);
define('FORMWORK_PATH', ROOT_PATH . 'formwork' . DS);
define('CONFIG_PATH', ROOT_PATH . 'config' . DS);
define('ADMIN_PATH', __DIR__ . DS);
define('ACCOUNTS_PATH', ADMIN_PATH . 'accounts' . DS);
define('SCHEMES_PATH', ADMIN_PATH . 'schemes' . DS);
define('LANGUAGES_PATH', ADMIN_PATH . 'languages' . DS);
define('LOGS_PATH', ADMIN_PATH . 'logs' . DS);
define('VIEWS_PATH', ADMIN_PATH . 'views' . DS);

require ROOT_PATH . 'vendor' . DS . 'autoload.php';

$formwork = new Formwork();

$admin = new Admin();

$admin->run();
