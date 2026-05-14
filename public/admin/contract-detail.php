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
    
    <div class="mt-4 card card-glass p-4 border-0 shadow-sm">
        <div class="d-flex align-items-center gap-3 mb-4">
            <div class="display-6" style="color: #0d6efd;">💰</div>
            <h5 class="mb-0">Quản lý thanh toán hợp đồng</h5>
        </div>

        <?php 
            $totalPrice = (float)($contract['price'] ?? 0);
            $paidAmount = (float)($contract['deposit'] ?? 0);
            $debt = max(0, $totalPrice - $paidAmount);
            $paymentPercent = $totalPrice > 0 ? min(100, ($paidAmount / $totalPrice) * 100) : 0;
        ?>

        <!-- Payment Status Summary -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="p-3 rounded-3" style="background: #f0f7ff; border-left: 4px solid #0d6efd;">
                    <div class="small text-muted mb-1">Tổng tiền hợp đồng</div>
                    <div class="h5 mb-0 text-primary fw-bold"><?= number_format($totalPrice, 0, ',', '.'); ?> đ</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 rounded-3" style="background: #e7f5e7; border-left: 4px solid #198754;">
                    <div class="small text-muted mb-1">Đã thanh toán</div>
                    <div class="h5 mb-0 text-success fw-bold"><?= number_format($paidAmount, 0, ',', '.'); ?> đ</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 rounded-3" style="background: <?= $debt > 0 ? '#ffe7e7' : '#e7f5e7'; ?>; border-left: 4px solid <?= $debt > 0 ? '#dc3545' : '#198754'; ?>;">
                    <div class="small text-muted mb-1">Còn nợ</div>
                    <div class="h5 mb-0 fw-bold" style="color: <?= $debt > 0 ? '#dc3545' : '#198754'; ?>" id="contractDebt"><?= number_format($debt, 0, ',', '.'); ?> đ</div>
                </div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small fw-semibold">Tiến độ thanh toán</span>
                <span class="badge bg-<?= $debt > 0 ? 'warning' : 'success'; ?>"><?= number_format($paymentPercent, 1, '.', ''); ?>%</span>
            </div>
            <div class="progress" style="height: 24px; border-radius: 12px;">
                <div class="progress-bar <?= $debt > 0 ? 'bg-warning' : 'bg-success'; ?>" role="progressbar" style="width: <?= $paymentPercent; ?>%;" aria-valuenow="<?= $paymentPercent; ?>" aria-valuemin="0" aria-valuemax="100">
                    <?php if ($paymentPercent > 10): ?><span class="fw-bold small" style="color: white;"><?= number_format($paymentPercent, 0); ?>%</span><?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Payment Form -->
        <?php if ($debt > 0): ?>
            <div class="alert alert-warning d-flex align-items-center gap-3 mb-4" role="alert" style="background: #fff8e7; border: none;">
                <i class="bi bi-exclamation-triangle-fill" style="font-size: 24px; color: #ff9800;"></i>
                <div>
                    <strong>Còn nợ <?= number_format($debt, 0, ',', '.'); ?> đ</strong><br>
                    <small class="text-muted">Vui lòng thanh toán để hoàn tất hợp đồng</small>
                </div>
            </div>

            <form id="payForm" class="row g-3">
                <input type="hidden" name="contract_id" value="<?= Security::e((string)$contract['contract_id']); ?>">
                
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Số tiền thanh toán</label>
                    <div class="input-group input-group-lg">
                        <input name="amount" type="number" min="1" step="1" class="form-control" placeholder="Nhập số tiền" required style="font-size: 18px;">
                        <span class="input-group-text" style="background: #f8f9fa; font-weight: bold;">đ</span>
                    </div>
                    <small class="text-muted d-block mt-2">Nhập 0 để thanh toán toàn bộ <?= number_format($debt, 0, ',', '.'); ?> đ</small>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Thao tác</label>
                    <div class="d-flex gap-2" style="height: 44px;">
                        <button id="payBtn" type="button" class="btn btn-success btn-lg flex-grow-1" style="border-radius: 12px;">
                            <i class="bi bi-check-circle me-2"></i>Thanh toán
                        </button>
                        <button id="payMaxBtn" type="button" class="btn btn-outline-primary btn-lg" style="border-radius: 12px;" title="Thanh toán hết nợ">
                            <i class="bi bi-lightning-fill me-1"></i>Hết nợ
                        </button>
                    </div>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-success d-flex align-items-center gap-3 mb-0" role="alert" style="background: #e7f5e7; border: none;">
                <i class="bi bi-check-circle-fill" style="font-size: 32px; color: #198754;"></i>
                <div>
                    <strong>✓ Đã thanh toán đầy đủ</strong><br>
                    <small class="text-muted">Hợp đồng không còn công nợ</small>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../views/partials/admin_footer.php'; ?>

<script>
document.getElementById('saveDetailBtn').addEventListener('click', async () => {
    const form = document.getElementById('detailForm');
    const data = Object.fromEntries(new FormData(form));
    try {
        const resp = await fetch((window.APP_BASE_URL || '') + '/api/contracts/save.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
        const json = await resp.json(); if (resp.ok && json.ok) { alert('Lưu thành công'); location.reload(); } else { alert('Lỗi: ' + (json.message || 'Không thành công')); }
    } catch (err) { alert('Lỗi kết nối'); }
});

document.getElementById('extendDetailBtn').addEventListener('click', async () => {
    const newDate = prompt('Nhập ngày kết thúc mới (YYYY-MM-DD):'); if (!newDate) return;
    const id = document.querySelector('#detailForm [name=contract_id]').value;
    try {
        const resp = await fetch((window.APP_BASE_URL || '') + '/api/contracts/extend.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ contract_id: id, new_end_date: newDate }) });
        const json = await resp.json(); if (resp.ok && json.ok) { alert('Gia hạn thành công'); location.reload(); } else { alert('Lỗi: ' + (json.message || 'Không thành công')); }
    } catch (err) { alert('Lỗi kết nối'); }
});

document.getElementById('terminateDetailBtn').addEventListener('click', async () => {
    if (!confirm('Xác nhận kết thúc hợp đồng này?')) return;
    const id = document.querySelector('#detailForm [name=contract_id]').value;
    try {
        const resp = await fetch((window.APP_BASE_URL || '') + '/api/contracts/terminate.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ contract_id: id, end_date: new Date().toISOString().slice(0,10), reason: 'Kết thúc tại chi tiết' }) });
        const json = await resp.json(); if (resp.ok && json.ok) { alert('Kết thúc hợp đồng thành công'); location.href = './contracts.php'; } else { alert('Lỗi: ' + (json.message || 'Không thành công')); }
    } catch (err) { alert('Lỗi kết nối'); }
});

// Payment handlers
(() => {
    const payBtn = document.getElementById('payBtn');
    const payMaxBtn = document.getElementById('payMaxBtn');
    const payForm = document.getElementById('payForm');
    const amountInput = payForm ? payForm.querySelector('input[name="amount"]') : null;
    
    if (!payForm) return;

    const submitPayment = async (amount) => {
        if (amount <= 0) return alert('Vui lòng nhập số tiền hợp lệ');
        
        const formData = new FormData(payForm);
        formData.set('amount', amount.toString());
        
        try {
            const resp = await fetch((window.APP_BASE_URL || '') + '/api/contracts/pay.php', { 
                method: 'POST', 
                body: formData 
            });
            const json = await resp.json();
            if (resp.ok && json.ok) { 
                alert('✓ Thanh toán thành công'); 
                location.reload(); 
            } else { 
                alert('Lỗi: ' + (json.message || 'Không thành công')); 
            }
        } catch (err) { 
            alert('Lỗi kết nối'); 
        }
    };

    payBtn && payBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        const amount = parseFloat(amountInput?.value || 0);
        submitPayment(amount);
    });

    payMaxBtn && payMaxBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        const debtText = document.getElementById('contractDebt')?.textContent || '0';
        const raw = debtText.replace(/[^0-9.-]+/g, '');
        const amount = parseFloat(raw) || 0;
        if (amount <= 0) return alert('Không có khoản nợ để thanh toán');
        submitPayment(amount);
    });
})();
</script>
