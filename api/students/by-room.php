<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAdminAuth();

$roomId = (int) ($_GET['room_id'] ?? 0);

if ($roomId <= 0) {
    Api::json(['ok' => false, 'message' => 'room_id không hợp lệ', 'students' => []], 422);
}

try {
    $students = RoomRepository::studentsByRoom($roomId);
    
    // Format lại để dễ dùng ở frontend
    $result = array_map(fn($s) => [
        'student_id' => $s['student_id'],
        'full_name' => $s['full_name'],
        'student_code' => $s['student_code'],
    ], $students);
    
    Api::json(['ok' => true, 'students' => $result]);
} catch (Throwable $e) {
    Api::json(['ok' => false, 'message' => $e->getMessage(), 'students' => []], 500);
}
