<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAdminAuth();

$id = (int) ($_GET['id'] ?? 0);
$contract = ContractRepository::find($id);
if (!$contract) {
    header('HTTP/1.1 404 Not Found');
    echo 'Hợp đồng không tồn tại';
    exit;
}

$student = StudentRepository::find((int) $contract['student_id']);
$room = RoomRepository::find((int) $contract['room_id']);

$pageTitle = 'Chi tiết hợp đồng #' . $contract['contract_id'] . ' - ' . APP_NAME;
$activeMenu = 'contracts';

require_once __DIR__ . '/../../views/partials/admin_header.php';
?>
<div class="container-fluid p-4">
    <div class="d-flex align-items-start gap-4">
        <div class="card card-glass p-3" style="min-width:380px;">
            <h5>Thông tin sinh viên</h5>
            <div><strong><?= Security::e($student['full_name'] ?? '-'); ?></strong></div>
            <div class="text-muted small">MSSV: <?= Security::e($student['student_code'] ?? '-'); ?></div>
            <div class="mt-2">Khoa: <?= Security::e($student['department'] ?? '-'); ?></div>
        </div>
        <div class="card card-glass p-3 flex-fill">
            <h5>Hợp đồng</h5>
            <form id="detailForm" class="row g-3">
                <input type="hidden" name="contract_id" value="<?= Security::e((string)$contract['contract_id']); ?>">
                <div class="col-6"><label class="form-label">Ngày vào</label><input name="start_date" type="date" class="form-control" value="<?= Security::e((string)$contract['start_date']); ?>" readonly></div>
                <div class="col-6"><label class="form-label">Ngày kết thúc</label><input name="end_date" type="date" class="form-control" value="<?= Security::e((string)($contract['end_date'] ?? '')); ?>"></div>
                <div class="col-12 d-flex gap-2">
                    <button id="saveDetailBtn" type="button" class="btn btn-primary">Lưu</button>
                    <button id="extendDetailBtn" type="button" class="btn btn-outline-primary">Gia hạn</button>
                    <button id="terminateDetailBtn" type="button" class="btn btn-danger">Kết thúc</button>
                </div>
            </form>
        </div>
    </div>

    <div class="mt-4 card card-glass p-3">
        <h5>Hóa đơn liên quan</h5>
        <?php
        $stmt = Database::connection()->prepare('SELECT b.* FROM UtilityBill b WHERE b.room_id = :room_id ORDER BY b.billing_year DESC, b.billing_month DESC');
        $stmt->execute([':room_id' => $contract['room_id']]);
        $bills = $stmt->fetchAll();
        ?>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead><tr><th>Tháng</th><th>Số tiền</th><th>Trạng thái</th></tr></thead>
                <tbody>
                <?php foreach ($bills as $b): ?>
                    <tr>
                        <td><?= Security::e((string)$b['billing_month']); ?>/<?= Security::e((string)$b['billing_year']); ?></td>
                        <td><?= number_format((float)$b['total_amount'],0,',','.'); ?> đ</td>
                        <td><?= Security::e((string)$b['status']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../views/partials/admin_footer.php'; ?>

<script>
document.getElementById('saveDetailBtn').addEventListener('click', async () => {
    const form = document.getElementById('detailForm');
    const data = Object.fromEntries(new FormData(form)); data.csrf_token = window.APP_CSRF;
    try {
        const resp = await fetch((window.APP_BASE_URL || '') + '/api/contracts/save.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
        const json = await resp.json(); if (resp.ok && json.ok) { alert('Lưu thành công'); location.reload(); } else { alert('Lỗi: ' + (json.message || 'Không thành công')); }
    } catch (err) { alert('Lỗi kết nối'); }
});

document.getElementById('extendDetailBtn').addEventListener('click', async () => {
    const newDate = prompt('Nhập ngày kết thúc mới (YYYY-MM-DD):'); if (!newDate) return;
    const id = document.querySelector('#detailForm [name=contract_id]').value;
    try {
        const resp = await fetch((window.APP_BASE_URL || '') + '/api/contracts/extend.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ contract_id: id, new_end_date: newDate, csrf_token: window.APP_CSRF }) });
        const json = await resp.json(); if (resp.ok && json.ok) { alert('Gia hạn thành công'); location.reload(); } else { alert('Lỗi: ' + (json.message || 'Không thành công')); }
    } catch (err) { alert('Lỗi kết nối'); }
});

document.getElementById('terminateDetailBtn').addEventListener('click', async () => {
    if (!confirm('Xác nhận kết thúc hợp đồng này?')) return;
    const id = document.querySelector('#detailForm [name=contract_id]').value;
    try {
        const resp = await fetch((window.APP_BASE_URL || '') + '/api/contracts/terminate.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ contract_id: id, end_date: new Date().toISOString().slice(0,10), reason: 'Kết thúc tại chi tiết', csrf_token: window.APP_CSRF }) });
        const json = await resp.json(); if (resp.ok && json.ok) { alert('Kết thúc hợp đồng thành công'); location.href = './contracts.php'; } else { alert('Lỗi: ' + (json.message || 'Không thành công')); }
    } catch (err) { alert('Lỗi kết nối'); }
});
</script>
