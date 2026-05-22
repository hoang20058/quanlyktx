<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAdminAuth();

function fetchDashboardRoomStats(PDO $pdo): array
{
    return [
        'totalRooms' => (int) $pdo->query('SELECT COUNT(*) FROM Room')->fetchColumn(),
        'activeRooms' => (int) $pdo->query("SELECT COUNT(*) FROM Room WHERE status = 'Hoạt động'")->fetchColumn(),
        'totalCapacity' => (int) $pdo->query('SELECT COALESCE(SUM(capacity), 0) FROM Room')->fetchColumn(),
        'occupied' => (int) $pdo->query("SELECT COUNT(*) FROM Contract WHERE status = 'Đang ở'")->fetchColumn(),
    ];
}

function fetchDashboardStudentStats(PDO $pdo): array
{
    return [
        'waiting' => (int) $pdo->query("SELECT COUNT(*) FROM Student WHERE status = 'Chờ duyệt'")->fetchColumn(),
        'living' => (int) $pdo->query("SELECT COUNT(*) FROM Student WHERE status = 'Đang ở'")->fetchColumn(),
        'moved' => (int) $pdo->query("SELECT COUNT(*) FROM Student WHERE status = 'Đã chuyển đi'")->fetchColumn(),
    ];
}

function fetchDashboardTopRooms(PDO $pdo, int $limit): array
{
    $stmt = $pdo->prepare("
        SELECT r.room_id, r.room_number, r.floor_number, r.capacity, r.status,
               COUNT(c.contract_id) AS occupancy,
               ROUND(COALESCE(AVG(s.boarding_score), 0), 2) AS avg_boarding_score
          FROM Room r
     LEFT JOIN Contract c ON c.room_id = r.room_id AND c.status = 'Đang ở'
     LEFT JOIN Student s ON s.student_id = c.student_id
      GROUP BY r.room_id
      ORDER BY occupancy DESC, avg_boarding_score DESC, r.room_number ASC
         LIMIT :limit
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function fetchDashboardUnpaidBills(PDO $pdo, int $limit): array
{
    $stmt = $pdo->prepare("
        SELECT b.bill_id, b.room_id, b.billing_month, b.billing_year, b.total_amount, b.status,
               r.room_number
          FROM UtilityBill b
          JOIN Room r ON r.room_id = b.room_id
         WHERE b.status = 'Chưa thanh toán'
      ORDER BY b.billing_year DESC, b.billing_month DESC, b.bill_id DESC
         LIMIT :limit
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function fetchDashboardUnpaidBillStats(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT COUNT(*) AS bill_count,
               COALESCE(SUM(total_amount), 0) AS total_amount
          FROM UtilityBill
         WHERE status = 'Chưa thanh toán'
    ");
    $stats = $stmt->fetch() ?: [];

    return [
        'count' => (int) ($stats['bill_count'] ?? 0),
        'totalAmount' => (float) ($stats['total_amount'] ?? 0),
    ];
}

function fetchDashboardNoticeCount(PDO $pdo): int
{
    return (int) $pdo->query('SELECT COUNT(*) FROM Notice')->fetchColumn();
}

$pdo = Database::connection();
$pageTitle = 'Dashboard - ' . APP_NAME;
$activeMenu = 'dashboard';

$roomStats = fetchDashboardRoomStats($pdo);
$studentStats = fetchDashboardStudentStats($pdo);
$topRooms = fetchDashboardTopRooms($pdo, 5);
$unpaidBills = fetchDashboardUnpaidBills($pdo, 10);
$unpaidBillStats = fetchDashboardUnpaidBillStats($pdo);
$noticeCount = fetchDashboardNoticeCount($pdo);
$currentUser = Security::admin();

$billRooms = array_values(array_unique(array_filter(array_map(static fn (array $row): string => !empty($row['room_number']) ? 'P' . (string) $row['room_number'] : '', $unpaidBills))));
$billMonths = array_values(array_unique(array_filter(array_map(static fn (array $row): string => (string) ($row['billing_month'] ?? ''), $unpaidBills), static fn (string $value): bool => $value !== '')));
$billYears = array_values(array_unique(array_filter(array_map(static fn (array $row): string => (string) ($row['billing_year'] ?? ''), $unpaidBills), static fn (string $value): bool => $value !== '')));
$topRoomFloors = array_values(array_unique(array_filter(array_map(static fn (array $row): int => (int) ($row['floor_number'] ?? 0), $topRooms), static fn (int $floor): bool => $floor > 0)));
sort($billRooms);
sort($billMonths, SORT_NUMERIC);
sort($billYears, SORT_NUMERIC);
sort($topRoomFloors, SORT_NUMERIC);

require_once __DIR__ . '/../../views/partials/admin_header.php';
?>
<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="admin-stat-card card border-0">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div><div class="text-secondary small mb-2">Tổng phòng</div><div class="stat-value"><?= h($roomStats['totalRooms']); ?></div></div>
                    <div class="icon-badge primary"><i class="bi bi-door-open"></i></div>
                </div>
                <div class="small text-success mt-3 fw-semibold">Phòng hoạt động: <?= h($roomStats['activeRooms']); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="admin-stat-card card border-0">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div><div class="text-secondary small mb-2">Sinh viên nội trú</div><div class="stat-value"><?= h($studentStats['living']); ?></div></div>
                    <div class="icon-badge blue"><i class="bi bi-mortarboard"></i></div>
                </div>
                <div class="small text-success mt-3 fw-semibold">Chờ duyệt: <?= h($studentStats['waiting']); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <a class="admin-stat-card card border-0 text-reset h-100" href="bills.php">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div><div class="text-secondary small mb-2">Hóa đơn chưa thu</div><div class="stat-value"><?= h($unpaidBillStats['count']); ?></div></div>
                    <div class="icon-badge amber"><i class="bi bi-receipt"></i></div>
                </div>
                <div class="small text-danger mt-3 fw-semibold"><?= h(number_format($unpaidBillStats['totalAmount'], 0, ',', '.')); ?> đ</div>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-xl-3">
        <a class="admin-stat-card card border-0 text-reset h-100" href="notices.php">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div><div class="text-secondary small mb-2">Thông báo</div><div class="stat-value"><?= h($noticeCount); ?></div></div>
                    <div class="icon-badge rose"><i class="bi bi-megaphone"></i></div>
                </div>
                <div class="small text-primary mt-3 fw-semibold">Mở trang thông báo</div>
            </div>
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="table-panel p-4">
            <div class="datatable-toolbar mb-3">
                <div><div class="section-subtitle text-uppercase fw-semibold small">Tài chính</div><h2 class="section-title mb-0">Hóa đơn chưa thanh toán</h2></div>
                <a class="btn btn-outline-dark btn-sm" href="bills.php">Mở quản lý hóa đơn</a>
            </div>
            <?php if (empty($unpaidBills)): ?>
                <div class="alert alert-success border-0 mb-0">Không có hóa đơn chưa thanh toán.</div>
            <?php else: ?>
                <div class="admin-filter-bar" data-filter-target="dashboardBillsTable">
                    <div class="admin-filter-field">
                        <label for="dashboardBillRoom">Phòng</label>
                        <select id="dashboardBillRoom" class="form-select form-select-sm" data-filter-key="room">
                            <option value="">Tất cả phòng</option>
                            <?php foreach ($billRooms as $roomNumber): ?>
                                <option value="<?= h($roomNumber); ?>"><?= h($roomNumber); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="admin-filter-field">
                        <label for="dashboardBillMonth">Tháng</label>
                        <select id="dashboardBillMonth" class="form-select form-select-sm" data-filter-key="month">
                            <option value="">Tất cả tháng</option>
                            <?php foreach ($billMonths as $month): ?>
                                <option value="<?= h($month); ?>">Tháng <?= h($month); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="admin-filter-field">
                        <label for="dashboardBillYear">Năm</label>
                        <select id="dashboardBillYear" class="form-select form-select-sm" data-filter-key="year">
                            <option value="">Tất cả năm</option>
                            <?php foreach ($billYears as $year): ?>
                                <option value="<?= h($year); ?>"><?= h($year); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="admin-filter-actions"><button type="button" class="btn btn-sm btn-outline-secondary" data-filter-clear>Đặt lại</button></div>
                </div>
                <table id="dashboardBillsTable" class="table datatable table-hover align-middle">
                    <thead><tr><th>Phòng</th><th>Tháng/Năm</th><th>Số tiền</th><th>Trạng thái</th></tr></thead>
                    <tbody>
                    <?php foreach ($unpaidBills as $bill): ?>
                        <?php $billRoom = !empty($bill['room_number']) ? 'P' . (string) $bill['room_number'] : ''; ?>
                        <tr data-room="<?= h($billRoom); ?>"
                            data-month="<?= h($bill['billing_month']); ?>"
                            data-year="<?= h($bill['billing_year']); ?>">
                            <td class="fw-semibold"><?= h($billRoom); ?></td>
                            <td><?= h($bill['billing_month']); ?>/<?= h($bill['billing_year']); ?></td>
                            <td class="text-danger fw-bold"><?= h(number_format((float) $bill['total_amount'], 0, ',', '.')); ?> đ</td>
                            <td><span class="badge text-bg-warning"><?= h($bill['status']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="table-panel p-4 h-100">
            <div class="datatable-toolbar mb-3">
                <div><div class="section-subtitle text-uppercase fw-semibold small">Hoạt động gần đây</div><h2 class="section-title mb-0">Danh sách phòng nổi bật</h2></div>
            </div>
            <div class="admin-filter-bar" data-filter-target="dashboardTopRoomsTable">
                <div class="admin-filter-field">
                    <label for="dashboardRoomFloor">Tầng</label>
                    <select id="dashboardRoomFloor" class="form-select form-select-sm" data-filter-key="floor">
                        <option value="">Tất cả tầng</option>
                        <?php foreach ($topRoomFloors as $floor): ?>
                            <option value="<?= h($floor); ?>">Tầng <?= h($floor); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-filter-field">
                    <label for="dashboardRoomOccupancy">Mức sử dụng</label>
                    <select id="dashboardRoomOccupancy" class="form-select form-select-sm" data-filter-key="occupancyState">
                        <option value="">Tất cả mức</option>
                        <option value="empty">Còn trống hoàn toàn</option>
                        <option value="occupied">Đang có người</option>
                        <option value="full">Đã đầy</option>
                    </select>
                </div>
                <div class="admin-filter-field">
                    <label for="dashboardRoomScore">Điểm TB</label>
                    <select id="dashboardRoomScore" class="form-select form-select-sm" data-filter-key="scoreBand">
                        <option value="">Tất cả điểm</option>
                        <option value="high">Tốt (>= 80)</option>
                        <option value="medium">Ổn định (60-79)</option>
                        <option value="low">Cần chú ý (&lt; 60)</option>
                        <option value="none">Chưa có điểm</option>
                    </select>
                </div>
                <div class="admin-filter-actions"><button type="button" class="btn btn-sm btn-outline-secondary" data-filter-clear>Đặt lại</button></div>
            </div>
            <table id="dashboardTopRoomsTable" class="table datatable table-hover align-middle">
                <thead><tr><th>Mã phòng</th><th>Tầng</th><th>Sức chứa</th><th>Đang ở</th><th>Điểm TB</th></tr></thead>
                <tbody>
                <?php foreach ($topRooms as $row): ?>
                    <?php
                    $capacity = max(0, (int) ($row['capacity'] ?? 0));
                    $occupancy = max(0, (int) ($row['occupancy'] ?? 0));
                    if ($capacity > 0 && $occupancy >= $capacity) {
                        $occupancyState = 'full';
                    } elseif ($occupancy > 0) {
                        $occupancyState = 'occupied';
                    } else {
                        $occupancyState = 'empty';
                    }
                    $score = (float) ($row['avg_boarding_score'] ?? 0);
                    $scoreBand = $score >= 80 ? 'high' : ($score >= 60 ? 'medium' : ($score > 0 ? 'low' : 'none'));
                    ?>
                    <tr data-floor="<?= h($row['floor_number']); ?>" data-occupancy-state="<?= h($occupancyState); ?>" data-score-band="<?= h($scoreBand); ?>">
                        <td class="fw-semibold">P<?= h($row['room_number']); ?></td>
                        <td><?= h($row['floor_number']); ?></td>
                        <td><?= h($row['capacity']); ?></td>
                        <td><?= h($row['occupancy']); ?>/<?= h($row['capacity']); ?></td>
                        <td><?= h($row['avg_boarding_score']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="panel-glass rounded-5 p-4 h-100">
            <div class="section-subtitle text-uppercase fw-semibold small mb-2">Quản trị</div>
            <h2 class="section-title mb-3">Xin chào, <?= h($currentUser['full_name'] ?? 'Admin'); ?></h2>
            <div class="mb-3 text-secondary small">Sức chứa: <?= h($roomStats['occupied']); ?>/<?= h($roomStats['totalCapacity']); ?> chỗ đang sử dụng</div>
            <div class="d-grid gap-3">
                <a class="btn btn-outline-dark" href="students.php">Xem hồ sơ đăng ký</a>
                <a class="btn btn-outline-dark" href="rooms.php">Quản lý phòng</a>
                <a class="btn btn-outline-dark" href="contracts.php">Quản lý hợp đồng</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../views/partials/admin_footer.php'; ?>
