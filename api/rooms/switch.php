<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAdminAuth();

$data = Api::input();

$studentId = (int) ($data['student_id'] ?? 0);
$newRoomId = (int) ($data['new_room_id'] ?? 0);

if ($studentId <= 0 || $newRoomId <= 0) {
    Api::json(['ok' => false, 'message' => 'Thiếu student_id hoặc new_room_id'], 422);
}

try {
    // Kiểm tra phòng mới tồn tại
    $newRoom = RoomRepository::find($newRoomId);
    if (!$newRoom) {
        Api::json(['ok' => false, 'message' => 'Phòng không tồn tại'], 422);
    }

    // Kiểm tra sức chứa của phòng
    $occupancy = RoomRepository::getOccupancy($newRoomId);
    if ($occupancy >= $newRoom['capacity']) {
        Api::json(['ok' => false, 'message' => 'Phòng đã đầy (sức chứa: ' . $newRoom['capacity'] . ', hiện tại: ' . $occupancy . '), không thể chuyển sinh viên'], 400);
    }

    StudentRepository::transferRoom($studentId, $newRoomId);
    Api::json(['ok' => true, 'message' => 'Chuyển phòng thành công']);
} catch (Throwable $e) {
    Api::json(['ok' => false, 'message' => $e->getMessage()], 500);
}
