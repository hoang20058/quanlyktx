<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAdminAuth();

$data = Api::input();

try {
    $billId = (int) ($data['bill_id'] ?? 0);
    if ($billId <= 0) {
        throw new InvalidArgumentException('Dữ liệu không hợp lệ.');
    }
    UtilityBillRepository::delete($billId);
    Api::json(['ok' => true, 'message' => 'Xóa hóa đơn thành công']);
} catch (Throwable $e) {
    Api::json(['ok' => false, 'message' => $e->getMessage()], 500);
}
