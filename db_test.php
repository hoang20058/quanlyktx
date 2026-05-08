<?php

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $pdo = require __DIR__ . '/config/database.php';
    $serverVersion = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);

    echo '<h1>Kết nối thành công</h1>';
    echo '<p>Application: ' . Security::e((string) (getenv('APP_NAME') ?: APP_NAME)) . '</p>';
    echo '<p>Database: ' . Security::e((string) (getenv('DB_NAME') ?: DB_NAME)) . '</p>';
    echo '<p>Host: ' . Security::e((string) ((getenv('DB_HOST') ?: DB_HOST) . ':' . (getenv('DB_PORT') ?: DB_PORT))) . '</p>';
    echo '<p>Server version: ' . Security::e((string) $serverVersion) . '</p>';
} catch (Throwable $exception) {
    http_response_code(500);
    echo '<h1>Kết nối thất bại</h1>';
    echo '<p>' . Security::e($exception->getMessage()) . '</p>';
}

