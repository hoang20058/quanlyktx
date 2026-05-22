<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAdminAuth();

function fetchLatestMeterIndices(PDO $pdo, int $roomId): array
{
    $stmt = $pdo->prepare('
        SELECT new_electric_index, new_water_index
          FROM UtilityBill
         WHERE room_id = :room_id
      ORDER BY billing_year DESC, billing_month DESC, bill_id DESC
         LIMIT 1
    ');
    $stmt->execute([':room_id' => $roomId]);
    $row = $stmt->fetch() ?: [];

    return [
        'new_electric_index' => (float) ($row['new_electric_index'] ?? 0),
        'new_water_index' => (float) ($row['new_water_index'] ?? 0),
    ];
}

$pdo = Database::connection();
$roomId = (int) ($_GET['room_id'] ?? 0);

try {
    if ($roomId <= 0) {
        throw new InvalidArgumentException('Dữ liệu không hợp lệ.');
    }

    $latest = fetchLatestMeterIndices($pdo, $roomId);
    Api::json([
        'ok' => true,
        'new_electric_index' => $latest['new_electric_index'],
        'new_water_index' => $latest['new_water_index'],
    ]);
} catch (Throwable $e) {
    Api::json(['ok' => false, 'message' => $e->getMessage()], 500);
}
