<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

// Redirect to admin login - user login is no longer supported
header('Location: ' . APP_URL . '/admin/login.php');
exit;

