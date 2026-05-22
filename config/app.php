<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Env.php';
require_once __DIR__ . '/../core/Security.php';
require_once __DIR__ . '/../core/Database.php';

Env::load(__DIR__ . '/../.env');

function detectAppUrl(): string
{
    $envAppUrl = trim((string) (getenv('APP_URL') ?: ''));

    if (PHP_SAPI === 'cli' || empty($_SERVER['HTTP_HOST'])) {
        return rtrim($envAppUrl !== '' ? $envAppUrl : '/quanlyktx/public', '/');
    }

    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $isHttps ? 'https' : 'http';
    $host = (string) $_SERVER['HTTP_HOST'];
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $scriptDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

    if (str_ends_with($scriptDir, '/admin')) {
        $scriptDir = rtrim(str_replace('\\', '/', dirname($scriptDir)), '/');
    }

    if ($scriptDir === '.' || $scriptDir === '/') {
        $scriptDir = '';
    }

    return rtrim($scheme . '://' . $host . $scriptDir, '/');
}

function detectAppBaseUrl(string $appUrl): string
{
    if (!str_starts_with($appUrl, 'http://') && !str_starts_with($appUrl, 'https://')) {
        $basePath = rtrim(str_replace('\\', '/', dirname($appUrl)), '/');
        return $basePath === '' || $basePath === '.' ? '/' : $basePath;
    }

    $parts = parse_url($appUrl);
    if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
        return rtrim(dirname($appUrl), '/\\');
    }

    $port = isset($parts['port']) ? ':' . $parts['port'] : '';
    $path = (string) ($parts['path'] ?? '');
    $basePath = rtrim(str_replace('\\', '/', dirname($path)), '/');

    if ($basePath === '' || $basePath === '.') {
        $basePath = '';
    }

    return $parts['scheme'] . '://' . $parts['host'] . $port . $basePath;
}

spl_autoload_register(static function (string $class): void {
    $paths = [
        __DIR__ . '/../core/' . $class . '.php',
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
    define('APP_URL', detectAppUrl());
}

if (!defined('APP_BASE_URL')) {
    define('APP_BASE_URL', detectAppBaseUrl(APP_URL));
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

