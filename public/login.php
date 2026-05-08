<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

Security::startSession();

$pageTitle = 'Đăng nhập - ' . APP_NAME;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $token = $_POST['csrf_token'] ?? null;

    if (!Security::verifyCsrfToken($token)) {
        $error = 'CSRF token không hợp lệ.';
    } else {
        // Hardcoded admin credentials per project constraint (no users table)
        if ($username === 'admin' && $password === 'admin') {
            $user = ['id' => 1, 'username' => 'admin', 'full_name' => 'Administrator'];
            Security::login($user);
            header('Location: ' . (getenv('APP_URL') ?: APP_URL) . '/admin/index.php');
            exit;
        }

        $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
    }
}

require_once __DIR__ . '/../views/auth/login.php';
