<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? APP_NAME;
$activeMenu = $activeMenu ?? 'dashboard';
$currentUser = Security::admin();
?><!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Security::e($pageTitle); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="<?= Security::e(APP_BASE_URL); ?>/assets/css/app.css?v=1.3" rel="stylesheet">
</head>
<body>
<div class="app-shell">
    <aside class="app-sidebar">
        <div class="sidebar-brand mb-4">
            <span class="brand-mark">KTX</span>
            <div>
                <div class="fw-bold text-white">Admin Panel</div>
                <small class="text-white-50">Quản lý Ký Túc Xá</small>
            </div>
        </div>
        <nav class="sidebar-nav nav flex-column">
            <a class="nav-link <?= $activeMenu === 'dashboard' ? 'active' : ''; ?>" href="./index.php"><i class="bi bi-grid-1x2-fill"></i>Dashboard</a>
            <a class="nav-link <?= $activeMenu === 'rooms' ? 'active' : ''; ?>" href="./rooms.php"><i class="bi bi-door-open-fill"></i>Quản lý phòng</a>
            <a class="nav-link <?= $activeMenu === 'students' ? 'active' : ''; ?>" href="./students.php"><i class="bi bi-people-fill"></i>Quản lý sinh viên</a>
            <a class="nav-link <?= $activeMenu === 'contracts' ? 'active' : ''; ?>" href="./contracts.php"><i class="bi bi-file-earmark-text-fill"></i>Quản lý hợp đồng</a>
            <a class="nav-link <?= $activeMenu === 'bills' ? 'active' : ''; ?>" href="./bills.php"><i class="bi bi-receipt-cutoff"></i>Quản lý hóa đơn</a>
            <a class="nav-link <?= $activeMenu === 'meter' ? 'active' : ''; ?>" href="./meter-reading.php"><i class="bi bi-speedometer2"></i>Nhập chỉ số</a>
            <a class="nav-link <?= $activeMenu === 'notices' ? 'active' : ''; ?>" href="./notices.php"><i class="bi bi-megaphone-fill"></i>Quản lý thông báo</a>
        </nav>
        <div class="mt-auto">
            <div class="panel-glass rounded-4 p-3 bg-dark border-0 text-white-75">
                <div class="d-flex align-items-center gap-3">
                    <img src="https://i.pravatar.cc/40?u=admin" alt="avatar" class="rounded-circle">
                    <div>
                        <div class="fw-semibold text-white"><?= Security::e($currentUser['full_name'] ?? 'Admin'); ?></div>
                        <a class="small footer-note" href="<?= Security::e(APP_URL); ?>/logout.php">Đăng xuất</a>
                    </div>
                </div>
            </div>
        </div>
    </aside>
    <div class="app-main">
        <header class="topbar sticky-top">
            <div class="container-fluid py-3 px-4">
                        <div class="topbar-inner d-flex align-items-center gap-3">
                            <div class="left">
                                <h1 class="h4 mb-0 fw-bold"><?= Security::e($pageTitle); ?></h1>
                            </div>
                            <div class="right ms-auto d-flex align-items-center gap-2">
                                <a class="btn btn-light" href="../index.php" title="Trang chủ"><i class="bi bi-house"></i></a>
                                <a class="btn btn-light" href="./notices.php" title="Thông báo"><i class="bi bi-bell"></i></a>
                                <a class="btn btn-outline-danger" href="<?= Security::e(APP_URL); ?>/logout.php" title="Đăng xuất"><i class="bi bi-box-arrow-right"></i></a>
                            </div>
                        </div>
            </div>
        </header>
        <main class="container-fluid px-4 py-4">
            <?php $flashMessage = function_exists('getFlashMessage') ? getFlashMessage() : null; ?>
            <?php if ($flashMessage): ?>
                <div class="alert alert-<?= h((string) ($flashMessage['type'] ?? 'success')); ?> alert-dismissible fade show" role="alert">
                    <?= h((string) ($flashMessage['message'] ?? '')); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
