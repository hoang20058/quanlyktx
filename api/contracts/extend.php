<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAdminAuth();

$data = Api::input();

$contractId = (int) ($data['contract_id'] ?? 0);
$newEndDate = trim((string) ($data['new_end_date'] ?? ''));

try {
    $contract = ContractRepository::find($contractId);
    if (!$contract) {
        Api::json(['ok' => false, 'message' => 'Hợp đồng không tồn tại'], 404);
    }

    $contract['end_date'] = $newEndDate === '' ? null : $newEndDate;
    $contract['status'] = $contract['status'] ?? 'Đang ở';

    ContractRepository::save($contract);

    Api::json(['ok' => true, 'message' => 'Gia hạn hợp đồng thành công', 'contract_id' => $contractId]);
} catch (Throwable $e) {
    Api::json(['ok' => false, 'message' => $e->getMessage()], 500);
}
