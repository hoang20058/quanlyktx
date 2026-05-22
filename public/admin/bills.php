<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAdminAuth();

function fetchBills(PDO $pdo): array
{
    $stmt = $pdo->query('
        SELECT b.bill_id, b.room_id, b.billing_month, b.billing_year, b.total_amount, b.status,
               r.room_number, r.floor_number
          FROM UtilityBill b
          JOIN Room r ON b.room_id = r.room_id
      ORDER BY b.billing_year DESC, b.billing_month DESC, b.bill_id DESC
    ');

    return $stmt->fetchAll();
}

function fetchBillRooms(PDO $pdo): array
{
    $stmt = $pdo->query('
        SELECT room_id, room_number, floor_number, capacity, room_type, status, price
          FROM Room
      ORDER BY room_number ASC
    ');

    return $stmt->fetchAll();
}

function fetchBillById(PDO $pdo, int $billId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM UtilityBill WHERE bill_id = :bill_id LIMIT 1');
    $stmt->execute([':bill_id' => $billId]);
    $bill = $stmt->fetch();

    return $bill ?: null;
}

function handleSaveBill(PDO $pdo, array $input): void
{
    $billId = (int) ($input['bill_id'] ?? 0);
    $existing = $billId > 0 ? fetchBillById($pdo, $billId) : null;
    $payload = [
        ':room_id' => (int) ($input['room_id'] ?? 0),
        ':billing_month' => max(1, min(12, (int) ($input['billing_month'] ?? date('n')))),
        ':billing_year' => (int) ($input['billing_year'] ?? date('Y')),
        ':total_amount' => max(0, (float) ($input['total_amount'] ?? 0)),
        ':status' => (string) ($input['status'] ?? 'Chưa thanh toán'),
        ':new_electric_index' => (float) ($existing['new_electric_index'] ?? 0),
        ':new_water_index' => (float) ($existing['new_water_index'] ?? 0),
    ];

    if ($payload[':room_id'] <= 0) {
        throw new InvalidArgumentException('Vui lòng chọn phòng.');
    }

    if ($billId > 0) {
        $payload[':bill_id'] = $billId;
        $stmt = $pdo->prepare('
            UPDATE UtilityBill
               SET room_id = :room_id,
                   billing_month = :billing_month,
                   billing_year = :billing_year,
                   total_amount = :total_amount,
                   status = :status,
                   new_electric_index = :new_electric_index,
                   new_water_index = :new_water_index
             WHERE bill_id = :bill_id
        ');
        $stmt->execute($payload);
        return;
    }

    $stmt = $pdo->prepare('
        INSERT INTO UtilityBill (room_id, billing_month, billing_year, total_amount, status, new_electric_index, new_water_index)
        VALUES (:room_id, :billing_month, :billing_year, :total_amount, :status, :new_electric_index, :new_water_index)
    ');
    $stmt->execute($payload);
}

function handleMarkBillPaid(PDO $pdo, int $billId): void
{
    if ($billId <= 0) {
        throw new InvalidArgumentException('Hóa đơn không hợp lệ.');
    }

    $stmt = $pdo->prepare("UPDATE UtilityBill SET status = 'Đã thanh toán' WHERE bill_id = :bill_id");
    $stmt->execute([':bill_id' => $billId]);
}

function handleDeleteBill(PDO $pdo, int $billId): void
{
    if ($billId <= 0) {
        throw new InvalidArgumentException('Hóa đơn không hợp lệ.');
    }

    $stmt = $pdo->prepare('DELETE FROM UtilityBill WHERE bill_id = :bill_id');
    $stmt->execute([':bill_id' => $billId]);
}

$pdo = Database::connection();
$pageTitle = 'Quản lý hóa đơn - ' . APP_NAME;
$activeMenu = 'bills';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'save') {
            handleSaveBill($pdo, $_POST);
            setFlashMessage('success', 'Lưu hóa đơn thành công.');
            redirectTo(APP_URL . '/admin/bills.php');
        }

        if ($action === 'mark_paid') {
            handleMarkBillPaid($pdo, (int) ($_POST['bill_id'] ?? 0));
            setFlashMessage('success', 'Cập nhật trạng thái thanh toán thành công.');
            redirectTo(APP_URL . '/admin/bills.php');
        }

        if ($action === 'delete') {
            handleDeleteBill($pdo, (int) ($_POST['bill_id'] ?? 0));
            setFlashMessage('success', 'Xóa hóa đơn thành công.');
            redirectTo(APP_URL . '/admin/bills.php');
        }

        throw new InvalidArgumentException('Thao tác không hợp lệ.');
    } catch (Throwable $e) {
        setFlashMessage('danger', $e->getMessage());
        redirectTo(APP_URL . '/admin/bills.php');
    }
}

$bills = fetchBills($pdo);
$rooms = fetchBillRooms($pdo);
$billRooms = array_values(array_unique(array_filter(array_map(static fn (array $bill): string => !empty($bill['room_number']) ? 'P' . (string) $bill['room_number'] : '', $bills))));
$billStatuses = array_values(array_unique(array_filter(array_map(static fn (array $bill): string => (string) ($bill['status'] ?? ''), $bills))));
$billMonths = array_values(array_unique(array_filter(array_map(static fn (array $bill): string => (string) ($bill['billing_month'] ?? ''), $bills), static fn (string $value): bool => $value !== '')));
$billYears = array_values(array_unique(array_filter(array_map(static fn (array $bill): string => (string) ($bill['billing_year'] ?? ''), $bills), static fn (string $value): bool => $value !== '')));
sort($billRooms);
sort($billStatuses);
sort($billMonths, SORT_NUMERIC);
sort($billYears, SORT_NUMERIC);

require_once __DIR__ . '/../../views/partials/admin_header.php';
?>
<div class="table-panel p-4">
    <div class="datatable-toolbar mb-3">
        <div>
            <div class="section-subtitle text-uppercase fw-semibold small">Page Controller</div>
            <h2 class="section-title mb-0">Bảng dữ liệu hóa đơn điện nước</h2>
        </div>
        <div class="table-actions d-flex gap-2"></div>
    </div>

    <div class="admin-filter-bar" data-filter-target="billsTable">
        <div class="admin-filter-field">
            <label for="billFilterRoom">Phòng</label>
            <select id="billFilterRoom" class="form-select form-select-sm" data-filter-key="room">
                <option value="">Tất cả phòng</option>
                <?php foreach ($billRooms as $roomNumber): ?>
                    <option value="<?= h($roomNumber); ?>"><?= h($roomNumber); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-filter-field">
            <label for="billFilterMonth">Tháng</label>
            <select id="billFilterMonth" class="form-select form-select-sm" data-filter-key="month">
                <option value="">Tất cả tháng</option>
                <?php foreach ($billMonths as $month): ?>
                    <option value="<?= h($month); ?>">Tháng <?= h($month); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-filter-field">
            <label for="billFilterYear">Năm</label>
            <select id="billFilterYear" class="form-select form-select-sm" data-filter-key="year">
                <option value="">Tất cả năm</option>
                <?php foreach ($billYears as $year): ?>
                    <option value="<?= h($year); ?>"><?= h($year); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-filter-field">
            <label for="billFilterStatus">Trạng thái</label>
            <select id="billFilterStatus" class="form-select form-select-sm" data-filter-key="status">
                <option value="">Tất cả trạng thái</option>
                <?php foreach ($billStatuses as $status): ?>
                    <option value="<?= h($status); ?>"><?= h($status); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-filter-actions">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-filter-clear>Đặt lại</button>
        </div>
    </div>

    <table id="billsTable" class="table datatable table-hover align-middle">
        <thead>
        <tr>
            <th>Phòng</th>
            <th>Tháng/Năm</th>
            <th>Số tiền</th>
            <th>Trạng thái</th>
            <th>Thao tác</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($bills as $bill): ?>
            <?php
            $billRoomNumber = !empty($bill['room_number']) ? 'P' . (string) $bill['room_number'] : '';
            $billStatus = (string) ($bill['status'] ?? '');
            ?>
            <tr data-room="<?= h($billRoomNumber); ?>"
                data-month="<?= h($bill['billing_month']); ?>"
                data-year="<?= h($bill['billing_year']); ?>"
                data-status="<?= h($billStatus); ?>">
                <td class="fw-semibold">P<?= h($bill['room_number']); ?></td>
                <td><?= h($bill['billing_month']); ?>/<?= h($bill['billing_year']); ?></td>
                <td><?= number_format((float) $bill['total_amount'], 0, ',', '.'); ?> đ</td>
                <td><span class="badge <?= $billStatus === 'Đã thanh toán' ? 'text-bg-success' : 'text-bg-warning'; ?>"><?= h($billStatus); ?></span></td>
                <td>
                    <div class="d-flex gap-2 flex-wrap">
                        <?php if ($billStatus !== 'Đã thanh toán'): ?>
                            <form method="post" onsubmit="return confirm('Xác nhận đã thu tiền cho hóa đơn này?');">
                                <input type="hidden" name="action" value="mark_paid">
                                <input type="hidden" name="bill_id" value="<?= h($bill['bill_id']); ?>">
                                <button class="btn btn-sm btn-success" type="submit">Đã thu tiền</button>
                            </form>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-outline-primary btn-edit-bill"
                                data-bs-toggle="modal"
                                data-bs-target="#billModal"
                                data-bill-id="<?= h($bill['bill_id']); ?>"
                                data-room-id="<?= h($bill['room_id']); ?>"
                                data-billing-month="<?= h($bill['billing_month']); ?>"
                                data-billing-year="<?= h($bill['billing_year']); ?>"
                                data-total-amount="<?= h($bill['total_amount']); ?>"
                                data-status="<?= h($billStatus); ?>">
                            Sửa
                        </button>
                        <form method="post" onsubmit="return confirm('Bạn chắc chắn muốn xóa hóa đơn này?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="bill_id" value="<?= h($bill['bill_id']); ?>">
                            <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="billModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="billForm" method="post">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="bill_id" value="0">
                <div class="modal-header"><h5 class="modal-title">Sửa hóa đơn</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label">Phòng</label>
                            <select name="room_id" class="form-select" required>
                                <option value="">-- Chọn phòng --</option>
                                <?php foreach ($rooms as $room): ?>
                                    <option value="<?= h($room['room_id']); ?>">P<?= h($room['room_number']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6"><label class="form-label">Tháng</label><input name="billing_month" type="number" min="1" max="12" class="form-control" required></div>
                        <div class="col-6"><label class="form-label">Năm</label><input name="billing_year" type="number" min="2020" class="form-control" required></div>
                        <div class="col-12"><label class="form-label">Số tiền (VND)</label><input name="total_amount" type="number" step="0.01" min="0" class="form-control" required></div>
                        <div class="col-12"><label class="form-label">Trạng thái</label>
                            <select name="status" class="form-select">
                                <option>Chưa thanh toán</option>
                                <option>Đã thanh toán</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button><button type="submit" class="btn btn-primary">Lưu</button></div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../views/partials/admin_footer.php'; ?>

<script>
(function () {
    const form = document.getElementById('billForm');

    const fillForm = (data) => {
        Object.entries(data).forEach(([key, value]) => {
            const field = form?.querySelector(`[name="${key}"]`);
            if (field) field.value = value ?? '';
        });
    };

    document.querySelectorAll('.btn-edit-bill').forEach((button) => {
        button.addEventListener('click', () => {
            fillForm({
                bill_id: button.dataset.billId,
                room_id: button.dataset.roomId,
                billing_month: button.dataset.billingMonth,
                billing_year: button.dataset.billingYear,
                total_amount: button.dataset.totalAmount,
                status: button.dataset.status || 'Chưa thanh toán'
            });
        });
    });
})();
</script>
