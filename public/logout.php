<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

Security::logout();
header('Location: ' . APP_URL . '/admin/login.php');
exit;
