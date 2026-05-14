<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAdminAuth();

$data = Api::input();

$roomId = (int) ($data['room_id'] ?? 0);
if ($roomId <= 0) {
    Api::json(['ok' => false, 'message' => 'room_id không hợp lệ'], 422);
}

try {
    RoomRepository::delete($roomId);
    Api::json(['ok' => true, 'message' => 'Xóa phòng thành công']);
} catch (Throwable $e) {
    Api::json(['ok' => false, 'message' => $e->getMessage()], 500);
}
