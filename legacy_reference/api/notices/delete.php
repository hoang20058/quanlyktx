<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAdminAuth();

$data = Api::input();

$noticeId = (int) ($data['notice_id'] ?? 0);
if ($noticeId <= 0) {
    Api::json(['ok' => false, 'message' => 'notice_id không hợp lệ'], 422);
}

try {
    NoticeRepository::delete($noticeId);
    Api::json(['ok' => true, 'message' => 'Xóa thông báo thành công']);
} catch (Throwable $e) {
    Api::json(['ok' => false, 'message' => $e->getMessage()], 500);
}
