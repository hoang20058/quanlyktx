<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAuth();

$data = Api::input();
Api::requireCsrf($data);

$roomId = (int) ($data['room_id'] ?? 0);
$oldE = (float) ($data['old_electric'] ?? 0);
$newE = (float) ($data['new_electric'] ?? 0);
$unitE = (float) ($data['unit_price_electric'] ?? 4000);
$oldW = (float) ($data['old_water'] ?? 0);
$newW = (float) ($data['new_water'] ?? 0);
$unitW = (float) ($data['unit_price_water'] ?? 50000);
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

    // Validate new >= old
    if ($newE < $oldE) {
        Api::json(['ok' => false, 'message' => 'Chỉ số điện mới phải >= chỉ số điện cũ'], 400);
    }
    if ($newW < $oldW) {
        Api::json(['ok' => false, 'message' => 'Chỉ số nước mới phải >= chỉ số nước cũ'], 400);
    }

    $usageE = max(0, $newE - $oldE);
    $usageW = max(0, $newW - $oldW);
    $totalE = $usageE * $unitE;
    $totalW = $usageW * $unitW;
    $totalAmount = $totalE + $totalW;

    if (UtilityBillRepository::existsForRoomAndMonthYear($roomId, $month, $year)) {
        Api::json(['ok' => false, 'message' => 'Hóa đơn tháng này đã tồn tại'], 409);
    }

    $billData = [
        'room_id' => $roomId,
        'billing_month' => $month,
        'billing_year' => $year,
        'total_amount' => $totalAmount,
        'usage_e' => $usageE,
        'usage_w' => $usageW,
        'unit_price_e' => $unitE,
        'unit_price_w' => $unitW,
        'status' => 'Chưa thanh toán',
    ];

    $billId = UtilityBillRepository::save($billData);

    Api::json([
        'ok' => true,
        'message' => 'Tạo hóa đơn thành công',
        'bill_id' => $billId,
        'usage_e' => $usageE,
        'usage_w' => $usageW,
        'total_amount' => $totalAmount
    ]);
} catch (Throwable $e) {
    Api::json(['ok' => false, 'message' => $e->getMessage()], 500);
}
