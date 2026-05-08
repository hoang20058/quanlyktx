<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAuth();

$data = Api::input();
Api::requireCsrf($data);

$month = (int) ($data['month'] ?? (int) date('n'));
$year = (int) ($data['year'] ?? (int) date('Y'));

try {
    $db = Database::connection();
    $stmt = $db->prepare("SELECT * FROM Contract WHERE status = 'Đang ở'");
    $stmt->execute();
    $contracts = $stmt->fetchAll();

    $created = 0;
    $skipped = 0;

    foreach ($contracts as $c) {
        $roomId = (int) $c['room_id'];

        if (UtilityBillRepository::existsForRoomAndMonthYear($roomId, $month, $year)) {
            $skipped++;
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

    Api::json(['ok' => true, 'message' => 'Chốt hóa đơn hoàn tất', 'created' => $created, 'skipped' => $skipped]);
} catch (Throwable $e) {
    Api::json(['ok' => false, 'message' => $e->getMessage()], 500);
}
