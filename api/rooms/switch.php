<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAuth();

$data = Api::input();
Api::requireCsrf($data);

$studentId = (int) ($data['student_id'] ?? 0);
$newRoomId = (int) ($data['new_room_id'] ?? 0);

if ($studentId <= 0 || $newRoomId <= 0) {
    Api::json(['ok' => false, 'message' => 'Thiếu student_id hoặc new_room_id'], 422);
}

try {
    StudentRepository::transferRoom($studentId, $newRoomId);
    Api::json(['ok' => true, 'message' => 'Chuyển phòng thành công']);
} catch (Throwable $e) {
    Api::json(['ok' => false, 'message' => $e->getMessage()], 500);
}
