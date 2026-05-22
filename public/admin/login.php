<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';

Security::startSession();

function handleAdminLogin(string $username, string $password): void
{
    if ($username !== 'admin' || $password !== 'admin') {
        throw new InvalidArgumentException('Tên đăng nhập hoặc mật khẩu không đúng.');
    }

    Security::loginAdmin([
        'id' => 1,
        'username' => 'admin',
        'full_name' => 'Administrator',
    ]);
}

$pageTitle = 'Đăng nhập Admin - ' . APP_NAME;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'login');
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    try {
        if ($action !== 'login') {
            throw new InvalidArgumentException('Thao tác không hợp lệ.');
        }

        handleAdminLogin($username, $password);
        header('Location: ' . APP_URL . '/admin/index.php');
        exit;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

?><!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Security::e($pageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= Security::e(APP_BASE_URL); ?>/assets/css/app.css" rel="stylesheet">
</head>
<body>
<div class="login-hero d-flex align-items-stretch">
    <div class="login-left d-none d-md-block">
        <div class="login-left-bg"></div>
    </div>
    <div class="login-right d-flex align-items-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8">
                    <div class="login-card panel-glass p-4 rounded-4">
                        <div class="text-center mb-3">
                            <span class="brand-mark">KTX</span>
                            <h3 class="mb-0 mt-2">Đăng nhập Admin</h3>
                            <div class="text-muted small">Quản trị hệ thống</div>
                        </div>
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?= Security::e($error); ?></div>
                        <?php endif; ?>
                        <form method="post" action="">
                            <input type="hidden" name="action" value="login">
                            <div class="mb-3">
                                <label class="form-label">Tên đăng nhập</label>
                                <input name="username" class="form-control form-control-lg" placeholder="admin" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mật khẩu</label>
                                <input name="password" type="password" class="form-control form-control-lg" placeholder="••••••••" required>
                            </div>
                            <div class="d-flex justify-content-between align-items-center gap-2">
                                <button class="btn btn-primary btn-lg rounded-pill flex-grow-1">Đăng nhập</button>
                                <a href="<?= Security::e(APP_URL); ?>/" class="btn btn-outline-secondary rounded-pill">Về trang chủ</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
