<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAuth();

$data = Api::input();
Api::requireCsrf($data);

try {
    $contractId = ContractRepository::save($data);
    Api::json(['ok' => true, 'message' => 'Lưu hợp đồng thành công', 'contract_id' => $contractId]);
} catch (Throwable $e) {
    Api::json(['ok' => false, 'message' => $e->getMessage()], 500);
}
