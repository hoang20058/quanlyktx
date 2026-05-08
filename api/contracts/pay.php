<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAuth();

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('Only POST allowed');
    }

    // Parse JSON or form data input
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
    } else {
        $input = $_POST;
    }
    
    $data = $input;
    if (!Security::verifyCsrfToken($data['csrf_token'] ?? null)) {
        throw new RuntimeException('CSRF token không hợp lệ');
    }

    $contractId = (int) ($data['contract_id'] ?? 0);
    $amount = (float) ($data['amount'] ?? 0);

    if ($contractId <= 0 || $amount <= 0) {
        throw new InvalidArgumentException('Dữ liệu thanh toán không hợp lệ');
    }

    $contract = ContractRepository::find($contractId);
    if (!$contract) {
        throw new RuntimeException('Hợp đồng không tồn tại');
    }

    $ok = ContractRepository::addPayment($contractId, $amount);
    if (!$ok) throw new RuntimeException('Không thể ghi nhận khoản thanh toán');

    echo json_encode(['ok' => true, 'message' => 'Thanh toán thành công']);
    exit;
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
    exit;
}
