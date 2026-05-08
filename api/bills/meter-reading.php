<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAuth();

$data = Api::input();
Api::requireCsrf($data);

$roomId = (int) ($data['room_id'] ?? 0);
$oldMeter = (float) ($data['old_meter'] ?? 0);
$newMeter = (float) ($data['new_meter'] ?? 0);
$unitPrice = (float) ($data['unit_price'] ?? 50000);
$month = (int) ($data['month'] ?? (int) date('n'));
$year = (int) ($data['year'] ?? (int) date('Y'));

try {
    $room = RoomRepository::find($roomId);
    if (!$room) {
        Api::json(['ok' => false, 'message' => 'Phòng không tồn tại'], 404);
    }

    $hasActiveContract = false;
    $stmt = Database::connection()->prepare("SELECT 1 FROM Contract WHERE room_id = :room_id AND status = 'Đang ở' LIMIT 1");
    $stmt->execute([':room_id' => $roomId]);
    $hasActiveContract = (bool) $stmt->fetchColumn();

    if (!$hasActiveContract) {
        Api::json(['ok' => false, 'message' => 'Phòng này hiện không có người ở, không thể nhập điện nước'], 422);
    }

    $usage = max(0, $newMeter - $oldMeter);
    $totalAmount = $usage * $unitPrice;

    if (UtilityBillRepository::existsForRoomAndMonthYear($roomId, $month, $year)) {
        Api::json(['ok' => false, 'message' => 'Hóa đơn tháng này đã tồn tại'], 409);
    }

    $billData = [
        'room_id' => $roomId,
        'billing_month' => $month,
        'billing_year' => $year,
        'total_amount' => $totalAmount,
        'status' => 'Chưa thanh toán',
    ];

    $billId = UtilityBillRepository::save($billData);

    Api::json([
        'ok' => true,
        'message' => 'Tạo hóa đơn thành công',
        'bill_id' => $billId,
        'usage' => $usage,
        'total_amount' => $totalAmount
    ]);
} catch (Throwable $e) {
    Api::json(['ok' => false, 'message' => $e->getMessage()], 500);
}
