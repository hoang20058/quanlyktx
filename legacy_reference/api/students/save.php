<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAdminAuth();

$data = Api::input();

try {
    $studentId = (int) ($data['student_id'] ?? 0);
    
    // Validation dữ liệu
    $validation = StudentRepository::validate($data, $studentId);
    if (!$validation['ok']) {
        Api::json(['ok' => false, 'message' => implode('; ', $validation['errors'])], 400);
    }

    // Lưu sinh viên
    $newId = StudentRepository::save($data);
    Api::json(['ok' => true, 'message' => 'Lưu sinh viên thành công', 'student_id' => $newId]);
} catch (Throwable $e) {
    Api::json(['ok' => false, 'message' => $e->getMessage()], 500);
}
