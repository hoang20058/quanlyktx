<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAdminAuth();

$pageTitle = 'Nhập chỉ số điện nước - ' . APP_NAME;
$activeMenu = 'bills';

$rooms = RoomRepository::occupiedSelectOptions();

require_once __DIR__ . '/../../views/partials/admin_header.php';
?>
<div class="table-panel p-4">
    <div class="datatable-toolbar mb-3">
        <div>
            <div class="section-subtitle text-uppercase fw-semibold small">Nhập liệu</div>
            <h2 class="section-title mb-0">Nhập chỉ số điện nước theo phòng</h2>
        </div>
    </div>

    <form id="meterForm" class="row g-4 mb-4 p-4 border-top">
        <div class="col-md-6">
            <label class="form-label">Chọn phòng</label>
            <select name="room_id" id="roomSelect" class="form-select" required <?= empty($rooms) ? 'disabled' : ''; ?>>
                <?php if (empty($rooms)): ?>
                    <option value="">-- Không có phòng đang ở --</option>
                <?php else: ?>
                    <option value="">-- Chọn phòng --</option>
                    <?php foreach ($rooms as $r): ?>
                        <option value="<?= Security::e((string) $r['room_id']); ?>">P<?= Security::e((string) $r['room_number']); ?> (<?= number_format((float) $r['price'], 0, ',', '.'); ?> đ)</option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <?php if (empty($rooms)): ?>
                <div class="form-text text-warning">Hiện chưa có phòng nào đang có sinh viên ở để nhập điện nước.</div>
            <?php endif; ?>
        </div>
        <div class="col-md-3">
            <label class="form-label">Tháng</label>
            <select name="month" class="form-select">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m; ?>" <?= $m === (int)date('n') ? 'selected' : ''; ?>><?= $m; ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Năm</label>
            <select name="year" class="form-select">
                <?php $y = (int)date('Y'); for ($i = $y - 1; $i <= $y + 1; $i++): ?>
                    <option value="<?= $i; ?>" <?= $i === $y ? 'selected' : ''; ?>><?= $i; ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Chỉ số cũ (kWh / m³)</label>
            <input name="old_meter" type="number" step="0.01" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Chỉ số mới (kWh / m³)</label>
            <input name="new_meter" type="number" step="0.01" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Giá tính / đơn vị (đ)</label>
            <input name="unit_price" type="number" step="0.01" class="form-control" value="50000" required>
        </div>
        <div class="col-12">
            <div class="p-3 rounded-4 bg-light">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="small text-muted">Lượng sử dụng</div>
                        <div id="usageDisplay" class="h5 mb-0">-</div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Giá tính</div>
                        <div id="priceDisplay" class="h5 mb-0">-</div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Tổng tiền</div>
                        <div id="totalDisplay" class="h5 mb-0 text-primary fw-bold">-</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <button type="button" id="submitMeterBtn" class="btn btn-primary btn-lg" <?= empty($rooms) ? 'disabled' : ''; ?>>
                <i class="bi bi-check-circle me-2"></i>Tạo hóa đơn
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../views/partials/admin_footer.php'; ?>

<script>
(function () {
    const form = document.getElementById('meterForm');
    const usageDisplay = document.getElementById('usageDisplay');
    const priceDisplay = document.getElementById('priceDisplay');
    const totalDisplay = document.getElementById('totalDisplay');

    const updateCalculation = () => {
        const oldMeter = parseFloat(form.old_meter.value) || 0;
        const newMeter = parseFloat(form.new_meter.value) || 0;
        const unitPrice = parseFloat(form.unit_price.value) || 50000;
        
        const usage = Math.max(0, newMeter - oldMeter);
        const total = usage * unitPrice;

        usageDisplay.textContent = usage.toFixed(2);
        priceDisplay.textContent = unitPrice.toLocaleString('vi-VN') + ' đ';
        totalDisplay.textContent = total.toLocaleString('vi-VN') + ' đ';
    };

    form.old_meter.addEventListener('input', updateCalculation);
    form.new_meter.addEventListener('input', updateCalculation);
    form.unit_price.addEventListener('input', updateCalculation);

    document.getElementById('submitMeterBtn').addEventListener('click', async () => {
        if (!form.room_id.value) { alert('Vui lòng chọn phòng'); return; }
        if (parseFloat(form.new_meter.value) < parseFloat(form.old_meter.value)) { alert('Chỉ số mới phải >= chỉ số cũ'); return; }

        const data = Object.fromEntries(new FormData(form));
        data.csrf_token = window.APP_CSRF;
        
        try {
            const resp = await fetch((window.APP_BASE_URL || '') + '/api/bills/meter-reading.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const json = await resp.json();
            if (resp.ok && json.ok) {
                alert('Tạo hóa đơn thành công! Lượng: ' + json.usage.toFixed(2) + ' đơn vị. Tổng: ' + (json.total_amount || 0).toLocaleString('vi-VN') + ' đ');
                form.reset();
                updateCalculation();
            } else {
                alert('Lỗi: ' + (json.message || 'Không thành công'));
            }
        } catch (err) {
            alert('Lỗi kết nối');
        }
    });

    updateCalculation();
})();
</script>
