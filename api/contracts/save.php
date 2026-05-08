<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAuth();

$data = Api::input();
Api::requireCsrf($data);

try {
    // Basic server-side validation: ensure student and room exist
    $contractId = (int) ($data['contract_id'] ?? 0);
    $studentId = (int) ($data['student_id'] ?? 0);
    $roomId = (int) ($data['room_id'] ?? 0);

    // validate existence more verbosely to help debug missing payloads
    $student = $studentId > 0 ? StudentRepository::find($studentId) : null;
    if ($studentId <= 0 || !$student) {
        Api::json([
            'ok' => false,
            'message' => 'Sinh viên không hợp lệ',
            'student_id_received' => $studentId,
            'student_found' => (bool)$student
        ], 400);
    }

    $room = $roomId > 0 ? RoomRepository::find($roomId) : null;
    if ($roomId <= 0 || !$room) {
        Api::json([
            'ok' => false,
            'message' => 'Phòng không hợp lệ',
            'room_id_received' => $roomId,
            'room_found' => (bool)$room
        ], 400);
    }

    $contractId = ContractRepository::save($data);
    Api::json(['ok' => true, 'message' => 'Lưu hợp đồng thành công', 'contract_id' => $contractId]);
} catch (Throwable $e) {
    Api::json(['ok' => false, 'message' => $e->getMessage()], 500);
}
