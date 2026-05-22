<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAdminAuth();

$data = Api::input();

$contractId = (int) ($data['contract_id'] ?? 0);
$endDate = trim((string) ($data['end_date'] ?? date('Y-m-d')));
$reason = trim((string) ($data['reason'] ?? ''));

try {
    $contract = ContractRepository::find($contractId);
    if (!$contract) {
        Api::json(['ok' => false, 'message' => 'Hợp đồng không tồn tại'], 404);
    }

    $contract['end_date'] = $endDate === '' ? date('Y-m-d') : $endDate;
    $contract['status'] = 'Đã chuyển ra';

    ContractRepository::save($contract);

    if ($reason !== '') {
        NoticeRepository::save([
            'target_type' => 'Cá nhân',
            'category' => 'Chuyển ra',
            'point_change' => 0,
            'student_id' => $contract['student_id'],
            'description' => $reason,
            'date' => date('Y-m-d'),
        ]);
    }

    Api::json(['ok' => true, 'message' => 'Kết thúc hợp đồng thành công', 'contract_id' => $contractId]);
} catch (Throwable $e) {
    Api::json(['ok' => false, 'message' => $e->getMessage()], 500);
}
