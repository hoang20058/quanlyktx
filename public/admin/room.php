<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAdminAuth();

$roomId = (int) ($_GET['id'] ?? 0);
if ($roomId <= 0) {
    header('Location: ' . APP_URL . '/admin/rooms.php');
    exit;
}

$room = RoomRepository::find($roomId);
if (!$room) {
    header('Location: ' . APP_URL . '/admin/rooms.php');
    exit;
}

$students = RoomRepository::studentsByRoom($roomId);
$pageTitle = 'Phòng P' . Security::e((string) $room['room_number']) . ' - ' . APP_NAME;
$activeMenu = 'rooms';

require_once __DIR__ . '/../../views/partials/admin_header.php';
?>
<div class="container-fluid">
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="panel-glass rounded-4 p-4 p-lg-5">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h1 class="h2 mb-2">P<?= Security::e((string) $room['room_number']); ?></h1>
                        <p class="text-secondary mb-0">Tầng <?= Security::e((string) $room['floor_number']); ?> • <?= Security::e((string) $room['room_type']); ?></p>
                    </div>
                    <a class="btn btn-outline-secondary" href="rooms.php">← Quay lại</a>
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="bg-light rounded-3 p-3">
                            <div class="text-secondary small">Sức chứa</div>
                            <div class="h3 mb-0 fw-bold"><?= count($students); ?> / <span class="fw-normal"><?= Security::e((string) $room['capacity']); ?></span></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-light rounded-3 p-3">
                            <div class="text-secondary small">Giá phòng</div>
                            <div class="h3 mb-0"><?= number_format((float) $room['price'], 0, ',', '.'); ?> đ</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-light rounded-3 p-3">
                            <div class="text-secondary small">Trạng thái</div>
                            <div class="h3 mb-0"><span class="badge text-bg-success"><?= Security::e((string) $room['status']); ?></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="panel-glass rounded-4 p-4">
                <h5 class="mb-3">Tùy chọn phòng</h5>
                <div class="d-grid gap-2">
                    <a class="btn btn-outline-secondary" href="rooms.php">Quay lại danh sách</a>
                </div>
            </div>
        </div>
    </div>

    <div class="panel-glass rounded-4 p-4 p-lg-5">
        <h3 class="mb-4">Danh sách sinh viên trong phòng</h3>
        <?php if (count($students) > 0): ?>
            <table class="table table-hover align-middle">
                <thead>
                <tr>
                    <th>Mã SV</th>
                    <th>Họ tên</th>
                    <th>Khoa</th>
                    <th>Ngày vào</th>
                    <th>Điểm</th>
                    <th>Trạng thái HĐ</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($students as $s): ?>
                    <tr>
                        <td class="fw-semibold"><?= Security::e((string) $s['student_code']); ?></td>
                        <td><?= Security::e((string) $s['full_name']); ?></td>
                        <td><?= Security::e((string) $s['department']); ?></td>
                        <td><?= Security::e((string) $s['start_date']); ?></td>
                        <td><?= Security::e((string) $s['boarding_score']); ?></td>
                        <td><span class="badge text-bg-info"><?= Security::e((string) $s['status']); ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info border-0">Hiện không có sinh viên nào ở phòng này.</div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../views/partials/admin_footer.php'; ?>
