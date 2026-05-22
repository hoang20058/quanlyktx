<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAdminAuth();

$data = Api::input();

try {
    $contractId = (int) ($data['contract_id'] ?? 0);
    if ($contractId <= 0) {
        throw new InvalidArgumentException('Dữ liệu không hợp lệ.');
    }
    ContractRepository::delete($contractId);
    Api::json(['ok' => true, 'message' => 'Xóa hợp đồng thành công']);
} catch (Throwable $e) {
    Api::json(['ok' => false, 'message' => $e->getMessage()], 500);
}
