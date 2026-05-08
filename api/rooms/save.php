<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAuth();

$data = Api::input();
Api::requireCsrf($data);

try {
    $roomId = RoomRepository::save($data);
    Api::json(['ok' => true, 'message' => 'Lưu phòng thành công', 'room_id' => $roomId]);
} catch (Throwable $e) {
    Api::json(['ok' => false, 'message' => $e->getMessage()], 500);
}
