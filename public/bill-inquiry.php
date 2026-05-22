<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

function fetchPublicRooms(PDO $pdo): array
{
    $stmt = $pdo->query('
        SELECT room_id, room_number, floor_number, capacity, room_type, status, price
          FROM Room
      ORDER BY room_number ASC
    ');

    return $stmt->fetchAll();
}

function fetchPublicRoom(PDO $pdo, int $roomId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM Room WHERE room_id = :room_id LIMIT 1');
    $stmt->execute([':room_id' => $roomId]);
    $room = $stmt->fetch();

    return $room ?: null;
}

function fetchBillsByRoom(PDO $pdo, int $roomId): array
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

$pdo = Database::connection();
$pageTitle = 'Tra cứu hóa đơn - ' . APP_NAME;
$rooms = fetchPublicRooms($pdo);
$bills = [];
$room = null;
$selectedRoomId = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'search');

    if ($action === 'search') {
        $selectedRoomId = (int) ($_POST['room_id'] ?? 0);
        $room = $selectedRoomId > 0 ? fetchPublicRoom($pdo, $selectedRoomId) : null;
        $bills = $room ? fetchBillsByRoom($pdo, $selectedRoomId) : [];
    }
}

$unpaidBills = array_values(array_filter($bills, static fn (array $bill): bool => (string) $bill['status'] === 'Chưa thanh toán'));
$totalUnpaid = array_sum(array_map(static fn (array $bill): float => (float) $bill['total_amount'], $unpaidBills));

require_once __DIR__ . '/../views/partials/public_header.php';
?>
<section class="container my-5">
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="panel-glass rounded-4 p-4">
                <h2 class="section-title mb-3">Tra cứu hóa đơn</h2>
                <p class="text-muted mb-4">Chọn phòng để xem danh sách hóa đơn điện nước.</p>

                <form method="post" action="" class="row g-3">
                    <input type="hidden" name="action" value="search">
                    <div class="col-12">
                        <label class="form-label">Chọn phòng</label>
                        <select name="room_id" class="form-select form-select-lg" required>
                            <option value="">-- Chọn phòng --</option>
                            <?php foreach ($rooms as $availableRoom): ?>
                                <option value="<?= h($availableRoom['room_id']); ?>" <?= $selectedRoomId === (int) $availableRoom['room_id'] ? 'selected' : ''; ?>>
                                    P<?= h($availableRoom['room_number']); ?> (<?= number_format((float) $availableRoom['price'], 0, ',', '.'); ?> đ)
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
                        <h3 class="h4 mb-2">Phòng <?= h($room['room_number']); ?></h3>
                        <div class="text-muted">
                            <div>Tầng: <?= h($room['floor_number']); ?></div>
                            <div>Sức chứa: <?= h($room['capacity']); ?> người</div>
                            <div>Giá phòng: <?= number_format((float) $room['price'], 0, ',', '.'); ?> đ</div>
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
                                        <td class="fw-semibold"><?= h($bill['billing_month']); ?>/<?= h($bill['billing_year']); ?></td>
                                        <td><?= number_format((float) $bill['total_amount'], 0, ',', '.'); ?> đ</td>
                                        <td>
                                            <?php if ($bill['status'] === 'Đã thanh toán'): ?>
                                                <span class="badge text-bg-success">Đã thanh toán</span>
                                            <?php else: ?>
                                                <span class="badge text-bg-warning">Chưa thanh toán</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (count($unpaidBills) > 0): ?>
                        <div class="alert alert-warning mt-3">
                            <strong>Phòng này có <?= h(count($unpaidBills)); ?> hóa đơn chưa thanh toán</strong>
                            <div class="h5 mt-2">Tổng nợ: <span class="text-danger"><?= number_format($totalUnpaid, 0, ',', '.'); ?> đ</span></div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success mt-3">
                            <strong>Tất cả hóa đơn đã thanh toán</strong>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $room): ?>
            <div class="col-lg-7">
                <div class="alert alert-info">
                    <strong>Phòng <?= h($room['room_number']); ?></strong> hiện chưa có hóa đơn nào.
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../views/partials/public_footer.php'; ?>
