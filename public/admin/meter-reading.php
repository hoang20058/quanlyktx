<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAdminAuth();

function fetchMeterRooms(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT r.room_id, r.room_number, r.floor_number, r.capacity, r.room_type, r.status,
               COALESCE((
                   SELECT b.new_electric_index
                     FROM UtilityBill b
                    WHERE b.room_id = r.room_id
                 ORDER BY b.billing_year DESC, b.billing_month DESC, b.bill_id DESC
                    LIMIT 1
               ), 0) AS latest_electric_index,
               COALESCE((
                   SELECT b.new_water_index
                     FROM UtilityBill b
                    WHERE b.room_id = r.room_id
                 ORDER BY b.billing_year DESC, b.billing_month DESC, b.bill_id DESC
                    LIMIT 1
               ), 0) AS latest_water_index
          FROM Room r
         WHERE r.status = 'Hoạt động'
           AND EXISTS (
               SELECT 1
                 FROM Contract c
                WHERE c.room_id = r.room_id
                  AND c.status = 'Đang ở'
           )
      ORDER BY r.room_number ASC
    ");

    return $stmt->fetchAll();
}

function billExistsForMonth(PDO $pdo, int $roomId, int $month, int $year): bool
{
    $stmt = $pdo->prepare('
        SELECT COUNT(*)
          FROM UtilityBill
         WHERE room_id = :room_id
           AND billing_month = :billing_month
           AND billing_year = :billing_year
    ');
    $stmt->execute([
        ':room_id' => $roomId,
        ':billing_month' => $month,
        ':billing_year' => $year,
    ]);

    return (int) $stmt->fetchColumn() > 0;
}

function handleCreateMeterBill(PDO $pdo, array $input): array
{
    $roomId = (int) ($input['room_id'] ?? 0);
    $oldElectric = (float) ($input['old_electric'] ?? 0);
    $newElectric = (float) ($input['new_electric'] ?? 0);
    $unitElectric = (float) ($input['unit_price_electric'] ?? 4000);
    $oldWater = (float) ($input['old_water'] ?? 0);
    $newWater = (float) ($input['new_water'] ?? 0);
    $unitWater = (float) ($input['unit_price_water'] ?? 50000);
    $month = max(1, min(12, (int) ($input['month'] ?? date('n'))));
    $year = (int) ($input['year'] ?? date('Y'));

    if ($roomId <= 0) {
        throw new InvalidArgumentException('Vui lòng chọn phòng.');
    }

    if ($newElectric < $oldElectric || $newWater < $oldWater) {
        throw new InvalidArgumentException('Chỉ số mới phải lớn hơn hoặc bằng chỉ số cũ.');
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Contract WHERE room_id = :room_id AND status = 'Đang ở'");
    $stmt->execute([':room_id' => $roomId]);
    if ((int) $stmt->fetchColumn() === 0) {
        throw new RuntimeException('Phòng này hiện không có sinh viên ở.');
    }

    $usageElectric = max(0, $newElectric - $oldElectric);
    $usageWater = max(0, $newWater - $oldWater);
    $totalAmount = ($usageElectric * $unitElectric) + ($usageWater * $unitWater);

    $pdo->beginTransaction();
    try {
        if (billExistsForMonth($pdo, $roomId, $month, $year)) {
            throw new RuntimeException('Hóa đơn tháng này đã tồn tại.');
        }

        $stmt = $pdo->prepare("
            INSERT INTO UtilityBill (room_id, billing_month, billing_year, total_amount, new_electric_index, new_water_index, status)
            VALUES (:room_id, :billing_month, :billing_year, :total_amount, :new_electric_index, :new_water_index, 'Chưa thanh toán')
        ");
        $stmt->execute([
            ':room_id' => $roomId,
            ':billing_month' => $month,
            ':billing_year' => $year,
            ':total_amount' => $totalAmount,
            ':new_electric_index' => $newElectric,
            ':new_water_index' => $newWater,
        ]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return [
        'usage_electric' => $usageElectric,
        'usage_water' => $usageWater,
        'total_amount' => $totalAmount,
    ];
}

$pdo = Database::connection();
$pageTitle = 'Nhập chỉ số điện nước - ' . APP_NAME;
$activeMenu = 'bills';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'create_bill') {
            $result = handleCreateMeterBill($pdo, $_POST);
            setFlashMessage(
                'success',
                'Tạo hóa đơn thành công. Điện: ' . number_format($result['usage_electric'], 2, ',', '.') .
                ' kWh, nước: ' . number_format($result['usage_water'], 2, ',', '.') .
                ' m³, tổng: ' . number_format($result['total_amount'], 0, ',', '.') . ' đ.'
            );
            redirectTo(APP_URL . '/admin/meter-reading.php');
        }

        throw new InvalidArgumentException('Thao tác không hợp lệ.');
    } catch (Throwable $e) {
        setFlashMessage('danger', $e->getMessage());
        redirectTo(APP_URL . '/admin/meter-reading.php');
    }
}

$rooms = fetchMeterRooms($pdo);

require_once __DIR__ . '/../../views/partials/admin_header.php';
?>
<div class="table-panel p-4">
    <div class="datatable-toolbar mb-3">
        <div>
            <div class="section-subtitle text-uppercase fw-semibold small">Nhập liệu</div>
            <h2 class="section-title mb-0">Nhập chỉ số điện nước theo phòng</h2>
        </div>
    </div>

    <form id="meterForm" method="post" class="row g-4 mb-4 p-4 border-top">
        <input type="hidden" name="action" value="create_bill">
        <div class="col-md-6">
            <label class="form-label">Chọn phòng</label>
            <select name="room_id" id="roomSelect" class="form-select" required <?= empty($rooms) ? 'disabled' : ''; ?>>
                <?php if (empty($rooms)): ?>
                    <option value="">-- Không có phòng đang ở --</option>
                <?php else: ?>
                    <option value="">-- Chọn phòng --</option>
                    <?php foreach ($rooms as $room): ?>
                        <option value="<?= h($room['room_id']); ?>"
                                data-electric="<?= h($room['latest_electric_index']); ?>"
                                data-water="<?= h($room['latest_water_index']); ?>">
                            P<?= h($room['room_number']); ?> - <?= h($room['room_type']); ?>, tầng <?= h($room['floor_number']); ?>
                        </option>
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
                <?php for ($month = 1; $month <= 12; $month++): ?>
                    <option value="<?= $month; ?>" <?= $month === (int) date('n') ? 'selected' : ''; ?>><?= $month; ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Năm</label>
            <select name="year" class="form-select">
                <?php $year = (int) date('Y'); ?>
                <?php for ($itemYear = $year - 1; $itemYear <= $year + 1; $itemYear++): ?>
                    <option value="<?= $itemYear; ?>" <?= $itemYear === $year ? 'selected' : ''; ?>><?= $itemYear; ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-6">
            <h6>Điện (kWh)</h6>
            <label class="form-label">Chỉ số điện cũ</label>
            <input id="oldElectricInput" name="old_electric" type="number" step="0.01" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Chỉ số điện mới</label>
            <input id="newElectricInput" name="new_electric" type="number" step="0.01" class="form-control" required>
        </div>
        <div class="col-md-6">
            <h6>Nước (m³)</h6>
            <label class="form-label">Chỉ số nước cũ</label>
            <input id="oldWaterInput" name="old_water" type="number" step="0.01" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Chỉ số nước mới</label>
            <input id="newWaterInput" name="new_water" type="number" step="0.01" class="form-control" required>
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
            <button type="submit" class="btn btn-primary btn-lg" <?= empty($rooms) ? 'disabled' : ''; ?>>
                <i class="bi bi-check-circle me-2"></i>Tạo hóa đơn
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../views/partials/admin_footer.php'; ?>

<script>
(function () {
    const form = document.getElementById('meterForm');
    const roomSelect = document.getElementById('roomSelect');
    const usageDisplay = document.getElementById('usageDisplay');
    const priceDisplay = document.getElementById('priceDisplay');
    const totalDisplay = document.getElementById('totalDisplay');
    if (!form) return;

    const updateCalculation = () => {
        const oldE = parseFloat(form.old_electric.value) || 0;
        const newE = parseFloat(form.new_electric.value) || 0;
        const unitE = parseFloat(form.unit_price_electric.value) || 4000;
        const oldW = parseFloat(form.old_water.value) || 0;
        const newW = parseFloat(form.new_water.value) || 0;
        const unitW = parseFloat(form.unit_price_water.value) || 50000;
        const usageE = Math.max(0, newE - oldE);
        const usageW = Math.max(0, newW - oldW);
        const total = (usageE * unitE) + (usageW * unitW);

        usageDisplay.textContent = usageE.toFixed(2) + ' kWh / ' + usageW.toFixed(2) + ' m³';
        priceDisplay.textContent = unitE.toLocaleString('vi-VN') + ' đ (điện) / ' + unitW.toLocaleString('vi-VN') + ' đ (nước)';
        totalDisplay.textContent = total.toLocaleString('vi-VN') + ' đ';
    };

    const syncLatestIndices = () => {
        const option = roomSelect?.options[roomSelect.selectedIndex];
        form.old_electric.value = option?.dataset.electric || '';
        form.old_water.value = option?.dataset.water || '';
        form.new_electric.focus();
        updateCalculation();
    };

    roomSelect?.addEventListener('change', syncLatestIndices);
    form.querySelectorAll('input, select').forEach((field) => {
        field.addEventListener('input', updateCalculation);
        field.addEventListener('change', updateCalculation);
    });
    updateCalculation();
})();
</script>
