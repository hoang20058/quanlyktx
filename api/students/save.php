<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAuth();

$data = Api::input();
Api::requireCsrf($data);

try {
    $studentId = StudentRepository::save($data);
    Api::json(['ok' => true, 'message' => 'Lưu sinh viên thành công', 'student_id' => $studentId]);
} catch (Throwable $e) {
    Api::json(['ok' => false, 'message' => $e->getMessage()], 500);
}
