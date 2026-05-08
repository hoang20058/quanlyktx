<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAuth();

$data = Api::input();
Api::requireCsrf($data);

try {
    $noticeId = NoticeRepository::save($data);
    Api::json(['ok' => true, 'message' => 'Lưu thông báo thành công', 'notice_id' => $noticeId]);
} catch (Throwable $e) {
    Api::json(['ok' => false, 'message' => $e->getMessage()], 500);
}
