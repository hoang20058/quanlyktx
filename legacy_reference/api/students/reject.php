<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAdminAuth();

$data = Api::input();

try {
    $studentId = (int) ($data['student_id'] ?? 0);
    if ($studentId <= 0) {
        throw new InvalidArgumentException('Dữ liệu không hợp lệ.');
    }

    $student = StudentRepository::find($studentId);
    if (!$student) {
        Api::json(['ok' => false, 'message' => 'Sinh viên không tồn tại'], 404);
    }

    $updated = StudentRepository::rejectRegistration($studentId);
    if (!$updated) {
        Api::json(['ok' => false, 'message' => 'Chỉ có thể từ chối hồ sơ đang chờ duyệt'], 409);
    }

    Api::json(['ok' => true, 'message' => 'Từ chối hồ sơ thành công', 'student_id' => $studentId]);
} catch (Throwable $e) {
    Api::json(['ok' => false, 'message' => $e->getMessage()], 500);
}
