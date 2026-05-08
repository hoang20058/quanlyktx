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
            <h6>Điện (kWh)</h6>
            <label class="form-label">Chỉ số điện cũ</label>
            <input name="old_electric" type="number" step="0.01" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Chỉ số điện mới</label>
            <input name="new_electric" type="number" step="0.01" class="form-control" required>
        </div>
        <div class="col-md-6">
            <h6>Nước (m³)</h6>
            <label class="form-label">Chỉ số nước cũ</label>
            <input name="old_water" type="number" step="0.01" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Chỉ số nước mới</label>
            <input name="new_water" type="number" step="0.01" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Giá điện (đ/kWh)</label>
            <input name="unit_price_electric" type="number" step="0.01" class="form-control" value="4000" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Giá nước (đ/m³)</label>
            <input name="unit_price_water" type="number" step="0.01" class="form-control" value="50000" required>
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
        const oldE = parseFloat(form.old_electric.value) || 0;
        const newE = parseFloat(form.new_electric.value) || 0;
        const unitE = parseFloat(form.unit_price_electric.value) || 4000;

        const oldW = parseFloat(form.old_water.value) || 0;
        const newW = parseFloat(form.new_water.value) || 0;
        const unitW = parseFloat(form.unit_price_water.value) || 50000;

        const usageE = Math.max(0, newE - oldE);
        const usageW = Math.max(0, newW - oldW);
        const totalE = usageE * unitE;
        const totalW = usageW * unitW;
        const total = totalE + totalW;

        usageDisplay.textContent = usageE.toFixed(2) + ' kWh / ' + usageW.toFixed(2) + ' m³';
        priceDisplay.textContent = unitE.toLocaleString('vi-VN') + ' đ (điện) / ' + unitW.toLocaleString('vi-VN') + ' đ (nước)';
        totalDisplay.textContent = total.toLocaleString('vi-VN') + ' đ';
    };

    form.old_electric.addEventListener('input', updateCalculation);
    form.new_electric.addEventListener('input', updateCalculation);
    form.unit_price_electric.addEventListener('input', updateCalculation);
    form.old_water.addEventListener('input', updateCalculation);
    form.new_water.addEventListener('input', updateCalculation);
    form.unit_price_water.addEventListener('input', updateCalculation);

    document.getElementById('submitMeterBtn').addEventListener('click', async () => {
        if (!form.room_id.value) { alert('Vui lòng chọn phòng'); return; }
        if (parseFloat(form.new_electric.value) < parseFloat(form.old_electric.value)) { alert('Chỉ số điện mới phải >= chỉ số điện cũ'); return; }
        if (parseFloat(form.new_water.value) < parseFloat(form.old_water.value)) { alert('Chỉ số nước mới phải >= chỉ số nước cũ'); return; }

        const formData = new FormData(form);
        formData.append('csrf_token', window.APP_CSRF);

        try {
            const resp = await fetch((window.APP_BASE_URL || '') + '/api/bills/meter-reading.php', {
                method: 'POST',
                body: formData
            });
            const json = await resp.json();
            if (resp.ok && json.ok) {
                alert('Tạo hóa đơn thành công! Lượng điện: ' + (json.usage_e || 0).toFixed(2) + ' kWh, Lượng nước: ' + (json.usage_w || 0).toFixed(2) + ' m³. Tổng: ' + (json.total_amount || 0).toLocaleString('vi-VN') + ' đ');
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
