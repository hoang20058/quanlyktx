<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

$pageTitle = 'Tra cứu hóa đơn - ' . APP_NAME;
$bills = [];
$room = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roomId = (int) ($_POST['room_id'] ?? 0);
    
    if ($roomId > 0) {
        $room = RoomRepository::find($roomId);
        if ($room) {
            $bills = UtilityBillRepository::billsByRoom($roomId);
        }
    }
}

$rooms = RoomRepository::selectOptions();

require_once __DIR__ . '/../views/partials/public_header.php';
?>
<section class="container my-5">
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="panel-glass rounded-4 p-4">
                <h2 class="section-title mb-3">Tra cứu hóa đơn</h2>
                <p class="text-muted mb-4">Chọn phòng để xem danh sách hóa đơn điện nước.</p>
                
                <form method="post" action="" class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Chọn phòng</label>
                        <select name="room_id" class="form-select form-select-lg" required>
                            <option value="">-- Chọn phòng --</option>
                            <?php foreach ($rooms as $r): ?>
                                <option value="<?= Security::e((string)$r['room_id']); ?>" <?= isset($_POST['room_id']) && (int)$_POST['room_id'] === (int)$r['room_id'] ? 'selected' : ''; ?>>
                                    P<?= Security::e((string)$r['room_number']); ?> (<?= number_format((float)$r['price'], 0, ',', '.'); ?> đ)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-search me-2"></i>Tra cứu
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($room && count($bills) > 0): ?>
            <div class="col-lg-7">
                <div class="panel-glass rounded-4 p-4">
                    <div class="mb-4">
                        <div class="section-subtitle text-uppercase fw-semibold small mb-2">Thông tin phòng</div>
                        <h3 class="h4 mb-2">Phòng <?= Security::e((string)$room['room_number']); ?></h3>
                        <div class="text-muted">
                            <div>Tầng: <?= Security::e((string)$room['floor_number']); ?></div>
                            <div>Sức chứa: <?= Security::e((string)$room['capacity']); ?> người</div>
                            <div>Giá phòng: <?= number_format((float)$room['price'], 0, ',', '.'); ?> đ</div>
                        </div>
                    </div>

                    <div class="section-subtitle text-uppercase fw-semibold small mb-2">Danh sách hóa đơn</div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Tháng/Năm</th>
                                    <th>Số tiền</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bills as $bill): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= Security::e((string)$bill['billing_month']); ?>/<?= Security::e((string)$bill['billing_year']); ?></td>
                                        <td><?= number_format((float)$bill['total_amount'], 0, ',', '.'); ?> đ</td>
                                        <td>
                                            <?php if ($bill['status'] === 'Đã thanh toán'): ?>
                                                <span class="badge text-bg-success">✓ Đã thanh toán</span>
                                            <?php else: ?>
                                                <span class="badge text-bg-warning">⚠ Chưa thanh toán</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php
                    $unpaid = array_filter($bills, fn($b) => $b['status'] === 'Chưa thanh toán');
                    $totalUnpaid = array_sum(array_map(fn($b) => (float)$b['total_amount'], $unpaid));
                    if (count($unpaid) > 0):
                    ?>
                        <div class="alert alert-warning mt-3">
                            <strong>⚠️ Phòng này có <?= count($unpaid); ?> hóa đơn chưa thanh toán</strong>
                            <div class="h5 mt-2">Tổng nợ: <span class="text-danger"><?= number_format($totalUnpaid, 0, ',', '.'); ?> đ</span></div>
                        </div>
                    <?php elseif (count($bills) > 0): ?>
                        <div class="alert alert-success mt-3">
                            <strong>✓ Tất cả hóa đơn đã thanh toán</strong>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif (!empty($_POST) && count($bills) === 0 && $room): ?>
            <div class="col-lg-7">
                <div class="alert alert-info">
                    <strong>Phòng <?= Security::e((string)$room['room_number']); ?></strong> hiện chưa có hóa đơn nào.
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../views/partials/public_footer.php'; ?>

