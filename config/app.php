<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Env.php';
require_once __DIR__ . '/../core/Security.php';
require_once __DIR__ . '/../core/Database.php';

Env::load(__DIR__ . '/../.env');

spl_autoload_register(static function (string $class): void {
    $paths = [
        __DIR__ . '/../core/' . $class . '.php',
        __DIR__ . '/../models/' . $class . '.php',
        __DIR__ . '/../controllers/' . $class . '.php',
    ];

    foreach ($paths as $path) {
        if (is_file($path)) {
            require_once $path;
            return;
        }
    }
});

if (!defined('APP_NAME')) {
    define('APP_NAME', getenv('APP_NAME') ?: 'Dormitory Management System');
}

if (!defined('APP_URL')) {
    define('APP_URL', getenv('APP_URL') ?: 'http://localhost/quanlyktx/public');
}

if (!defined('APP_BASE_URL')) {
    define('APP_BASE_URL', rtrim(dirname(APP_URL), '/\\'));
}

if (!defined('APP_TIMEZONE')) {
    define('APP_TIMEZONE', getenv('APP_TIMEZONE') ?: 'Asia/Ho_Chi_Minh');
}

if (!defined('DB_HOST')) {
    define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
}

if (!defined('DB_PORT')) {
    define('DB_PORT', getenv('DB_PORT') ?: '3306');
}

if (!defined('DB_NAME')) {
    define('DB_NAME', getenv('DB_NAME') ?: 'quanlyktx');
}

if (!defined('DB_USER')) {
    define('DB_USER', getenv('DB_USER') ?: 'root');
}

if (!defined('DB_PASS')) {
    define('DB_PASS', getenv('DB_PASS') ?: '');
}

date_default_timezone_set(APP_TIMEZONE);
Security::startSession();

// load helpers (pure functions)
if (is_file(__DIR__ . '/../core/Helpers.php')) {
    require_once __DIR__ . '/../core/Helpers.php';
}

