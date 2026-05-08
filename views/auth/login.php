<?php

declare(strict_types=1);

?>
<!doctype html>
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
                            <div class="text-muted small">Quản trị viên hệ thống</div>
                        </div>
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?= Security::e($error); ?></div>
                        <?php endif; ?>
                        <form method="post" action="">
                            <?= Security::csrfField(); ?>
                            <div class="mb-3"><label class="form-label">Tên đăng nhập</label><input name="username" class="form-control form-control-lg" required></div>
                            <div class="mb-3"><label class="form-label">Mật khẩu</label><input name="password" type="password" class="form-control form-control-lg" required></div>
                            <div class="d-flex justify-content-between align-items-center">
                                <button class="btn btn-primary btn-lg rounded-pill">Đăng nhập</button>
                                <a href="<?= Security::e(APP_BASE_URL); ?>/" class="text-muted">Về trang chủ</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
