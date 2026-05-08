<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAdminAuth();

$pageTitle = 'Quản lý hóa đơn - ' . APP_NAME;
$activeMenu = 'bills';

$bills = UtilityBillRepository::all();
$rooms = RoomRepository::selectOptions();

require_once __DIR__ . '/../../views/partials/admin_header.php';
?>
<div class="table-panel p-4">
    <div class="datatable-toolbar mb-3">
        <div>
            <div class="section-subtitle text-uppercase fw-semibold small">CRUD chuẩn hóa</div>
            <h2 class="section-title mb-0">Bảng dữ liệu hóa đơn điện nước</h2>
        </div>
        <div class="table-actions d-flex gap-2">
            <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#billModal" data-bill-id="0">
                <i class="bi bi-plus-lg me-1"></i>Thêm hóa đơn
            </button>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#finalizeModal">
                <i class="bi bi-calendar-check me-1"></i>Chốt hóa đơn
            </button>
        </div>
    </div>

    <table class="table datatable table-hover align-middle">
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
            <tr>
                <td class="fw-semibold">P<?= Security::e((string) $bill['room_number']); ?></td>
                <td><?= Security::e((string) $bill['billing_month']); ?>/<?= Security::e((string) $bill['billing_year']); ?></td>
                <td><?= number_format((float) $bill['total_amount'], 0, ',', '.'); ?> đ</td>
                <td><span class="badge <?= $bill['status'] === 'Đã thanh toán' ? 'text-bg-success' : 'text-bg-warning'; ?>"><?= Security::e((string) $bill['status']); ?></span></td>
                <td>
                    <div class="d-flex gap-2 flex-wrap">
                        <?php if ($bill['status'] !== 'Đã thanh toán'): ?>
                            <button class="btn btn-sm btn-success btn-mark-paid" data-bill-id="<?= Security::e((string) $bill['bill_id']); ?>">Đã thu tiền</button>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-outline-primary btn-edit-bill"
                                data-bs-toggle="modal"
                                data-bs-target="#billModal"
                                data-bill-id="<?= Security::e((string) $bill['bill_id']); ?>"
                                data-room-id="<?= Security::e((string) $bill['room_id']); ?>"
                                data-billing-month="<?= Security::e((string) $bill['billing_month']); ?>"
                                data-billing-year="<?= Security::e((string) $bill['billing_year']); ?>"
                                data-total-amount="<?= Security::e((string) $bill['total_amount']); ?>"
                                data-status="<?= Security::e((string) $bill['status']); ?>">
                            Sửa
                        </button>
                        <button class="btn btn-sm btn-outline-danger btn-delete-bill" data-bill-id="<?= Security::e((string) $bill['bill_id']); ?>">Xóa</button>
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
            <div class="modal-header"><h5 class="modal-title">Thêm/Sửa hóa đơn</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="billForm" class="row g-3">
                    <input type="hidden" name="bill_id" value="0">
                    <div class="col-12"><label class="form-label">Phòng</label>
                        <select name="room_id" class="form-select" required>
                            <option value="">-- Chọn phòng --</option>
                            <?php foreach ($rooms as $r): ?>
                                <option value="<?= Security::e((string) $r['room_id']); ?>">P<?= Security::e((string) $r['room_number']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6"><label class="form-label">Tháng</label><input name="billing_month" type="number" min="1" max="12" class="form-control" required></div>
                    <div class="col-6"><label class="form-label">Năm</label><input name="billing_year" type="number" min="2020" class="form-control" required></div>
                    <div class="col-12"><label class="form-label">Số tiền (VND)</label><input name="total_amount" type="number" step="0.01" class="form-control" required></div>
                    <div class="col-12"><label class="form-label">Trạng thái</label>
                        <select name="status" class="form-select">
                            <option>Chưa thanh toán</option>
                            <option>Đã thanh toán</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button><button id="saveBillBtn" type="button" class="btn btn-primary">Lưu</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="finalizeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Chốt hóa đơn tháng</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="finalizeForm" class="row g-3">
                    <div class="col-6"><label class="form-label">Tháng</label>
                        <select name="month" class="form-select">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m; ?>" <?= $m === (int)date('n') ? 'selected' : ''; ?>><?= $m; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-6"><label class="form-label">Năm</label>
                        <select name="year" class="form-select">
                            <?php $y = (int)date('Y'); for ($i = $y - 1; $i <= $y + 1; $i++): ?>
                                <option value="<?= $i; ?>" <?= $i === $y ? 'selected' : ''; ?>><?= $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-12"><label class="form-label">Xem trước</label>
                        <div id="finalizePreview" class="table-responsive small"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button><button id="confirmFinalizeBtn" type="button" class="btn btn-primary">Xác nhận chốt</button></div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../views/partials/admin_footer.php'; ?>

<script>
(function () {
    const bindDelete = (selector, endpoint, idKey, payloadKey) => {
        document.querySelectorAll(selector).forEach((button) => {
            button.addEventListener('click', async () => {
                const id = button.getAttribute(idKey) || '0';
                if (!id || id === '0') return;
                if (!confirm('Bạn chắc chắn muốn xóa bản ghi này?')) return;

                try {
                    const resp = await fetch((window.APP_BASE_URL || '') + endpoint, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ [payloadKey]: id, csrf_token: window.APP_CSRF })
                    });
                    const json = await resp.json();
                    if (resp.ok && json.ok) {
                        location.reload();
                    } else {
                        alert('Lỗi: ' + (json.message || 'Xóa thất bại'));
                    }
                } catch (err) {
                    alert('Lỗi kết nối');
                }
            });
        });
    };

    bindDelete('.btn-delete-bill', '/api/bills/delete.php', 'data-bill-id', 'bill_id');

    document.querySelectorAll('.btn-mark-paid').forEach((button) => {
        button.addEventListener('click', async () => {
            const billId = button.getAttribute('data-bill-id');
            if (!confirm('Xác nhận đã thu tiền cho hóa đơn này?')) return;

            try {
                const resp = await fetch((window.APP_BASE_URL || '') + '/api/bills/mark-paid.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ bill_id: billId, csrf_token: window.APP_CSRF })
                });
                const json = await resp.json();
                if (resp.ok && json.ok) {
                    location.reload();
                } else {
                    alert('Lỗi: ' + (json.message || 'Không thành công'));
                }
            } catch (err) {
                alert('Lỗi kết nối');
            }
        });
    });

    const saveBillBtn = document.getElementById('saveBillBtn');
    if (saveBillBtn) {
        saveBillBtn.addEventListener('click', async () => {
            const form = document.getElementById('billForm');
            const data = new FormData(form);
            data.set('csrf_token', window.APP_CSRF);
            
            try {
                const resp = await fetch((window.APP_BASE_URL || '') + '/api/bills/save.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(Object.fromEntries(data))
                });
                const json = await resp.json();
                if (json.ok) {
                    location.reload();
                } else {
                    alert('Lỗi: ' + json.message);
                }
            } catch (err) {
                alert('Lỗi kết nối');
            }
        });
    }
})();
</script>

<script>
(function () {
    const previewEl = document.getElementById('finalizePreview');
    const form = document.getElementById('finalizeForm');

    const renderPreview = async () => {
        const data = new FormData(form);
        // For preview we'll fetch a lightweight endpoint or just show counts by calling finalize but with dry-run not implemented; show selected month/year
        previewEl.innerHTML = `<div class="p-2">Chọn tháng <strong>${data.get('month')}</strong> / <strong>${data.get('year')}</strong>. Khi xác nhận, hệ thống sẽ tạo hóa đơn cho các phòng trống (không có ai ở) nếu chưa có.</div>`;
    };

    form.addEventListener('change', renderPreview);
    document.getElementById('finalizeModal').addEventListener('shown.bs.modal', renderPreview);

    document.getElementById('confirmFinalizeBtn').addEventListener('click', async () => {
        const data = new FormData(form);
        const payload = { month: parseInt(data.get('month')), year: parseInt(data.get('year')), csrf_token: window.APP_CSRF };
        if (!confirm('Bạn chắc chắn muốn chốt hóa đơn cho tháng này? Hành động không thể hoàn tác.')) return;
        try {
            const resp = await fetch((window.APP_BASE_URL || '') + '/api/bills/finalize-month.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
            });
            const json = await resp.json();
            if (resp.ok && json.ok) {
                alert('Chốt hóa đơn hoàn tất. Tạo: ' + json.created_count + ' hóa đơn. Tổng: ' + (json.total_revenue || 0) + ' VND');
                location.reload();
            } else {
                alert('Lỗi: ' + (json.message || 'Không thành công'));
            }
        } catch (err) {
            alert('Lỗi kết nối');
        }
    });
})();
</script>
