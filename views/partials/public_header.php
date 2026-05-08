<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? APP_NAME;
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
    <link href="<?= Security::e(APP_BASE_URL); ?>/assets/css/app.css?v=1.1" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-glass sticky-top py-3 mb-4">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-3 fw-bold" href="<?= Security::e(APP_URL); ?>/">
            <span class="brand-mark">KTX</span>
            <span class="fs-5"><?= Security::e(APP_NAME); ?></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#siteNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="siteNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item"><a class="nav-link fw-semibold" href="<?= Security::e(APP_URL); ?>/">Trang chủ</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= Security::e(APP_URL); ?>/#rooms">Phòng trống</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= Security::e(APP_URL); ?>/#notices">Thông báo</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= Security::e(APP_URL); ?>/#leaderboard">Xếp hạng</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= Security::e(APP_URL); ?>/register.php">Đăng ký</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= Security::e(APP_URL); ?>/contact.php">Liên hệ</a></li>
                <li class="nav-item ms-lg-2"><a class="btn btn-primary rounded-pill px-4" href="<?= Security::e(APP_URL); ?>/admin/">Khu vực quản trị</a></li>
            </ul>
        </div>
    </div>
</nav>
<main>
