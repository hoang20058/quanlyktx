<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAuth();

$data = Api::input();
Api::requireCsrf($data);

try {
    $billId = UtilityBillRepository::save($data);
    Api::json(['ok' => true, 'message' => 'Lưu hóa đơn thành công', 'bill_id' => $billId]);
} catch (Throwable $e) {
    Api::json(['ok' => false, 'message' => $e->getMessage()], 500);
}
