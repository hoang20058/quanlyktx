<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAdminAuth();

$data = Api::input();
Api::requireCsrf($data);

$month = (int) ($data['month'] ?? (int) date('n'));
$year = (int) ($data['year'] ?? (int) date('Y'));

try {
    $db = Database::connection();
    $sql = "SELECT r.* FROM Room r WHERE r.status = 'Hoạt động' AND r.room_id NOT IN (SELECT DISTINCT room_id FROM Contract WHERE status = 'Đang ở')";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $emptyRooms = $stmt->fetchAll();

    $created = 0;

    foreach ($emptyRooms as $room) {
        $roomId = (int) $room['room_id'];

        if (UtilityBillRepository::existsForRoomAndMonthYear($roomId, $month, $year)) {
            continue;
        }

        $billData = [
            'room_id' => $roomId,
            'billing_month' => $month,
            'billing_year' => $year,
            'total_amount' => 0,
            'status' => 'Chưa thanh toán',
        ];

        UtilityBillRepository::save($billData);
        $created++;
    }

    Api::json(['ok' => true, 'message' => 'Chốt hóa đơn hoàn tất', 'created' => $created, 'skipped' => 0]);
} catch (Throwable $e) {
    Api::json(['ok' => false, 'message' => $e->getMessage()], 500);
}

