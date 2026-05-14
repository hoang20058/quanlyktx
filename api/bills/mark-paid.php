<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAdminAuth();

$data = Api::input();

$billId = (int) ($data['bill_id'] ?? 0);

try {
    $bill = UtilityBillRepository::find($billId);
    if (!$bill) {
        Api::json(['ok' => false, 'message' => 'Hóa đơn không tồn tại'], 404);
    }

    $bill['status'] = 'Đã thanh toán';
    UtilityBillRepository::save($bill);

    Api::json(['ok' => true, 'message' => 'Cập nhật trạng thái thành công', 'bill_id' => $billId]);
} catch (Throwable $e) {
    Api::json(['ok' => false, 'message' => $e->getMessage()], 500);
}
