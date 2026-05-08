<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAdminAuth();

// This endpoint has been deprecated. Batch billing operations are no longer supported.
// Individual bills should be managed manually or through meter reading automation.

header('Content-Type: application/json; charset=utf-8');
http_response_code(410); // Gone
echo json_encode(['ok' => false, 'message' => 'Chức năng này đã bị loại bỏ. Quản lý hóa đơn thông qua các công cụ khác.']);
exit;


