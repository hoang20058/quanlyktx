<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAdminAuth();

$roomId = (int) ($_POST['room_id'] ?? 0);
if ($roomId <= 0) {
    Api::json(['ok' => false, 'message' => 'Room ID không hợp lệ'], 400);
}

$uploadDir = __DIR__ . '/../../storage/uploads/rooms/';

// Create directory if it doesn't exist
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$file = $_FILES['image'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    Api::json(['ok' => false, 'message' => 'Không có file hoặc lỗi upload'], 400);
}

// Validate file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowedTypes)) {
    Api::json(['ok' => false, 'message' => 'Chỉ chấp nhận file ảnh (JPG, PNG, GIF, WebP)'], 400);
}

// Validate file size (max 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    Api::json(['ok' => false, 'message' => 'Kích thước file không được vượt quá 5MB'], 400);
}

// Generate unique filename
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'room_' . $roomId . '_' . time() . '.' . $ext;
$filepath = $uploadDir . $filename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    Api::json(['ok' => false, 'message' => 'Lỗi khi lưu file'], 500);
}

// Store relative path in database
$relativePath = '/storage/uploads/rooms/' . $filename;

try {
    $db = Database::connection();
    $stmt = $db->prepare('UPDATE Room SET room_image_url = :url WHERE room_id = :room_id');
    $stmt->execute([':url' => $relativePath, ':room_id' => $roomId]);
    
    Api::json(['ok' => true, 'message' => 'Tải ảnh thành công', 'image_url' => $relativePath]);
} catch (Throwable $e) {
    // Delete the uploaded file if database update fails
    unlink($filepath);
    Api::json(['ok' => false, 'message' => 'Lỗi khi lưu dữ liệu: ' . $e->getMessage()], 500);
}
