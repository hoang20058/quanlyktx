<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAdminAuth();

function fetchContractDetail(PDO $pdo, int $contractId): ?array
{
    $stmt = $pdo->prepare('
        SELECT c.contract_id, c.student_id, c.room_id, c.start_date, c.end_date, c.status,
               s.full_name, s.student_code, s.department, s.phone, s.email,
               r.room_number, r.floor_number, r.capacity, r.room_type, r.status AS room_status
          FROM Contract c
          JOIN Student s ON s.student_id = c.student_id
          JOIN Room r ON r.room_id = c.room_id
         WHERE c.contract_id = :contract_id
         LIMIT 1
    ');
    $stmt->execute([':contract_id' => $contractId]);
    $contract = $stmt->fetch();

    return $contract ?: null;
}

function fetchContractBills(PDO $pdo, int $roomId): array
{
    $stmt = $pdo->prepare('
        SELECT bill_id, room_id, billing_month, billing_year, total_amount, status
          FROM UtilityBill
         WHERE room_id = :room_id
      ORDER BY billing_year DESC, billing_month DESC, bill_id DESC
    ');
    $stmt->execute([':room_id' => $roomId]);

    return $stmt->fetchAll();
}

function handleUpdateContractDate(PDO $pdo, int $contractId, string $endDate): void
{
    $contract = fetchContractDetail($pdo, $contractId);
    if (!$contract) {
        throw new InvalidArgumentException('Hợp đồng không tồn tại.');
    }

    if ($endDate !== '' && new DateTimeImmutable($endDate) < new DateTimeImmutable((string) $contract['start_date'])) {
        throw new InvalidArgumentException('Ngày kết thúc phải lớn hơn hoặc bằng ngày vào.');
    }

    $stmt = $pdo->prepare('UPDATE Contract SET end_date = :end_date WHERE contract_id = :contract_id');
    $stmt->execute([
        ':end_date' => $endDate !== '' ? $endDate : null,
        ':contract_id' => $contractId,
    ]);
}

function handleExtendContractDetail(PDO $pdo, int $contractId, string $endDate): void
{
    $contract = fetchContractDetail($pdo, $contractId);
    if (!$contract || trim($endDate) === '') {
        throw new InvalidArgumentException('Vui lòng nhập ngày kết thúc mới.');
    }

    if (new DateTimeImmutable($endDate) < new DateTimeImmutable((string) $contract['start_date'])) {
        throw new InvalidArgumentException('Ngày kết thúc mới phải lớn hơn hoặc bằng ngày vào.');
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("UPDATE Contract SET end_date = :end_date, status = 'Đang ở' WHERE contract_id = :contract_id");
        $stmt->execute([
            ':end_date' => $endDate,
            ':contract_id' => $contractId,
        ]);

        $stmt = $pdo->prepare("UPDATE Student SET status = 'Đang ở' WHERE student_id = :student_id");
        $stmt->execute([':student_id' => (int) $contract['student_id']]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function handleTerminateContractDetail(PDO $pdo, int $contractId, string $endDate): void
{
    $contract = fetchContractDetail($pdo, $contractId);
    if (!$contract) {
        throw new InvalidArgumentException('Hợp đồng không tồn tại.');
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("UPDATE Contract SET end_date = :end_date, status = 'Đã chuyển ra' WHERE contract_id = :contract_id");
        $stmt->execute([
            ':end_date' => $endDate !== '' ? $endDate : date('Y-m-d'),
            ':contract_id' => $contractId,
        ]);

        $stmt = $pdo->prepare("UPDATE Student SET status = 'Đã chuyển đi' WHERE student_id = :student_id");
        $stmt->execute([':student_id' => (int) $contract['student_id']]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

$pdo = Database::connection();
$contractId = (int) ($_GET['id'] ?? $_POST['contract_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'update_date') {
            handleUpdateContractDate($pdo, $contractId, trim((string) ($_POST['end_date'] ?? '')));
            setFlashMessage('success', 'Lưu ngày hợp đồng thành công.');
            redirectTo(APP_URL . '/admin/contract-detail.php?id=' . $contractId);
        }

        if ($action === 'extend') {
            handleExtendContractDetail($pdo, $contractId, trim((string) ($_POST['end_date'] ?? '')));
            setFlashMessage('success', 'Gia hạn hợp đồng thành công.');
            redirectTo(APP_URL . '/admin/contract-detail.php?id=' . $contractId);
        }

        if ($action === 'terminate') {
            handleTerminateContractDetail($pdo, $contractId, trim((string) ($_POST['end_date'] ?? date('Y-m-d'))));
            setFlashMessage('success', 'Kết thúc hợp đồng thành công.');
            redirectTo(APP_URL . '/admin/contracts.php');
        }

        throw new InvalidArgumentException('Thao tác không hợp lệ.');
    } catch (Throwable $e) {
        setFlashMessage('danger', $e->getMessage());
        redirectTo(APP_URL . '/admin/contract-detail.php?id=' . $contractId);
    }
}

$contract = fetchContractDetail($pdo, $contractId);
if (!$contract) {
    header('HTTP/1.1 404 Not Found');
    echo 'Hợp đồng không tồn tại';
    exit;
}

$bills = fetchContractBills($pdo, (int) $contract['room_id']);
$detailBillStatuses = array_values(array_unique(array_filter(array_map(static fn (array $bill): string => (string) ($bill['status'] ?? ''), $bills))));
$detailBillMonths = array_values(array_unique(array_filter(array_map(static fn (array $bill): string => (string) ($bill['billing_month'] ?? ''), $bills), static fn (string $value): bool => $value !== '')));
$detailBillYears = array_values(array_unique(array_filter(array_map(static fn (array $bill): string => (string) ($bill['billing_year'] ?? ''), $bills), static fn (string $value): bool => $value !== '')));
sort($detailBillStatuses);
sort($detailBillMonths, SORT_NUMERIC);
sort($detailBillYears, SORT_NUMERIC);

$pageTitle = 'Chi tiết hợp đồng #' . $contract['contract_id'] . ' - ' . APP_NAME;
$activeMenu = 'contracts';

require_once __DIR__ . '/../../views/partials/admin_header.php';
?>
<div class="container-fluid p-4">
    <div class="row g-4">
        <div class="col-xl-4">
            <div class="card card-glass p-3 h-100">
                <h5>Thông tin sinh viên</h5>
                <div><strong><?= h($contract['full_name']); ?></strong></div>
                <div class="text-muted small">MSSV: <?= h($contract['student_code']); ?></div>
                <div class="mt-2">Khoa: <?= h($contract['department']); ?></div>
                <div>Điện thoại: <?= h($contract['phone'] ?: '-'); ?></div>
                <div>Email: <?= h($contract['email'] ?: '-'); ?></div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card card-glass p-3 h-100">
                <h5>Thông tin phòng</h5>
                <div><strong>P<?= h($contract['room_number']); ?></strong></div>
                <div class="text-muted small">Tầng <?= h($contract['floor_number']); ?>, loại phòng <?= h($contract['room_type']); ?></div>
                <div class="mt-2">Sức chứa: <?= h($contract['capacity']); ?> sinh viên</div>
                <div>Trạng thái phòng: <?= h($contract['room_status']); ?></div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card card-glass p-3 h-100">
                <h5>Hợp đồng</h5>
                <form id="detailForm" method="post" class="row g-3">
                    <input type="hidden" name="contract_id" value="<?= h($contract['contract_id']); ?>">
                    <div class="col-6"><label class="form-label">Ngày vào</label><input name="start_date" type="date" class="form-control" value="<?= h($contract['start_date']); ?>" readonly></div>
                    <div class="col-6"><label class="form-label">Ngày kết thúc</label><input name="end_date" type="date" class="form-control" value="<?= h($contract['end_date'] ?? ''); ?>"></div>
                    <div class="col-12">Trạng thái: <span class="badge text-bg-info"><?= h($contract['status']); ?></span></div>
                    <div class="col-12 d-flex gap-2 flex-wrap">
                        <button name="action" value="update_date" type="submit" class="btn btn-primary">Lưu</button>
                        <button name="action" value="extend" type="submit" class="btn btn-outline-primary">Gia hạn</button>
                        <button name="action" value="terminate" type="submit" class="btn btn-danger" onclick="return confirm('Xác nhận kết thúc hợp đồng này?');">Kết thúc</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="mt-4 card card-glass p-3">
        <h5>Hóa đơn liên quan</h5>
        <?php if (empty($bills)): ?>
            <div class="alert alert-info border-0 mb-0">Chưa có hóa đơn nào liên quan đến phòng của hợp đồng này.</div>
        <?php else: ?>
            <div class="admin-filter-bar" data-filter-target="contractBillsTable">
                <div class="admin-filter-field">
                    <label for="detailBillMonth">Tháng</label>
                    <select id="detailBillMonth" class="form-select form-select-sm" data-filter-key="month">
                        <option value="">Tất cả tháng</option>
                        <?php foreach ($detailBillMonths as $month): ?>
                            <option value="<?= h($month); ?>">Tháng <?= h($month); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-filter-field">
                    <label for="detailBillYear">Năm</label>
                    <select id="detailBillYear" class="form-select form-select-sm" data-filter-key="year">
                        <option value="">Tất cả năm</option>
                        <?php foreach ($detailBillYears as $year): ?>
                            <option value="<?= h($year); ?>"><?= h($year); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-filter-field">
                    <label for="detailBillStatus">Trạng thái</label>
                    <select id="detailBillStatus" class="form-select form-select-sm" data-filter-key="status">
                        <option value="">Tất cả trạng thái</option>
                        <?php foreach ($detailBillStatuses as $status): ?>
                            <option value="<?= h($status); ?>"><?= h($status); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-filter-actions">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-filter-clear>Đặt lại</button>
                </div>
            </div>
            <div class="table-responsive">
                <table id="contractBillsTable" class="table datatable table-sm align-middle">
                    <thead><tr><th>Tháng</th><th>Số tiền</th><th>Trạng thái</th></tr></thead>
                    <tbody>
                    <?php foreach ($bills as $bill): ?>
                        <tr data-month="<?= h($bill['billing_month']); ?>"
                            data-year="<?= h($bill['billing_year']); ?>"
                            data-status="<?= h($bill['status']); ?>">
                            <td><?= h($bill['billing_month']); ?>/<?= h($bill['billing_year']); ?></td>
                            <td><?= number_format((float) $bill['total_amount'], 0, ',', '.'); ?> đ</td>
                            <td><?= h($bill['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../views/partials/admin_footer.php'; ?>
