<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAuth();

$data = Api::input();
Api::requireCsrf($data);

$studentId = (int) ($data['student_id'] ?? 0);
if ($studentId <= 0) {
    Api::json(['ok' => false, 'message' => 'student_id không hợp lệ'], 422);
}

try {
    StudentRepository::delete($studentId);
    Api::json(['ok' => true, 'message' => 'Xóa sinh viên thành công']);
} catch (Throwable $e) {
    Api::json(['ok' => false, 'message' => $e->getMessage()], 500);
}
