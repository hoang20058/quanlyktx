<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';

Security::requireAdminAuth();

$pageTitle = 'Dashboard - ' . APP_NAME;
$activeMenu = 'dashboard';

$roomStats = RoomRepository::stats();
$studentStats = StudentRepository::registrationStats();
$topRooms = RoomRepository::topRooms(5);
$topStudents = StudentRepository::topStudents(5);
$noticeCount = count(NoticeRepository::all());
$studentsWithDebt = ContractRepository::studentsWithDebt();

$currentUser = Security::admin();
$dashboardDebtRows = array_slice($studentsWithDebt, 0, 10);
$debtRooms = array_values(array_unique(array_filter(array_map(static fn (array $row): string => !empty($row['room_number']) ? 'P' . (string) $row['room_number'] : '', $dashboardDebtRows))));
sort($debtRooms);
$topRoomFloors = array_values(array_unique(array_map(static fn (array $row): int => (int) ($row['floor_number'] ?? 0), $topRooms)));
$topRoomFloors = array_values(array_filter($topRoomFloors, static fn (int $floor): bool => $floor > 0));
sort($topRoomFloors, SORT_NUMERIC);
$dashboardToday = new DateTimeImmutable('today');
$dashboardSoonLimit = $dashboardToday->modify('+30 days');

require_once __DIR__ . '/../../views/partials/admin_header.php';
?>
<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="admin-stat-card card border-0"><div class="card-body p-4"><div class="d-flex justify-content-between align-items-start"><div><div class="text-secondary small mb-2">Tổng phòng</div><div class="stat-value"><?= Security::e((string) $roomStats['totalRooms']); ?></div></div><div class="icon-badge primary"><i class="bi bi-door-open"></i></div></div><div class="small text-success mt-3 fw-semibold"><i class="bi bi-arrow-up-right"></i> Phòng hoạt động: <?= Security::e((string) $roomStats['activeRooms']); ?></div></div></div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="admin-stat-card card border-0"><div class="card-body p-4"><div class="d-flex justify-content-between align-items-start"><div><div class="text-secondary small mb-2">Sinh viên nội trú</div><div class="stat-value"><?= Security::e((string) $studentStats['living']); ?></div></div><div class="icon-badge blue"><i class="bi bi-mortarboard"></i></div></div><div class="small text-success mt-3 fw-semibold"><i class="bi bi-arrow-up-right"></i> Chờ duyệt: <?= Security::e((string) $studentStats['waiting']); ?></div></div></div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="admin-stat-card card border-0"><div class="card-body p-4"><div class="d-flex justify-content-between align-items-start"><div><div class="text-secondary small mb-2">Sức chứa tổng</div><div class="stat-value"><?= Security::e((string) $roomStats['totalCapacity']); ?></div></div><div class="icon-badge amber"><i class="bi bi-receipt"></i></div></div><div class="small text-warning mt-3 fw-semibold"></div></div></div>
    </div>
    <div class="col-md-6 col-xl-3">
        <a class="admin-stat-card card border-0 text-reset h-100" href="notices.php">
            <div class="card-body p-4"><div class="d-flex justify-content-between align-items-start"><div><div class="text-secondary small mb-2">Thông báo mới</div><div class="stat-value"><?= Security::e((string) $noticeCount); ?></div></div><div class="icon-badge rose"><i class="bi bi-megaphone"></i></div></div><div class="small text-primary mt-3 fw-semibold"><i class="bi bi-arrow-right"></i> Mở trang thông báo</div></div>
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="table-panel p-4">
            <div class="datatable-toolbar mb-3">
                <div><div class="section-subtitle text-uppercase fw-semibold small">Tài chính</div><h2 class="section-title mb-0">Sinh viên nợ tiền phòng</h2></div>
            </div>
            <?php if (empty($studentsWithDebt)): ?>
                <div class="alert alert-success border-0 mb-0">
                    <i class="bi bi-check-circle me-2"></i> Tất cả sinh viên đã thanh toán tiền phòng!
                </div>
            <?php else: ?>
            <div class="admin-filter-bar" data-filter-target="dashboardDebtTable">
                <div class="admin-filter-field">
                    <label for="dashboardDebtRoom">Phòng</label>
                    <select id="dashboardDebtRoom" class="form-select form-select-sm" data-filter-key="room">
                        <option value="">Tất cả phòng</option>
                        <?php foreach ($debtRooms as $roomNumber): ?>
                            <option value="<?= Security::e($roomNumber); ?>"><?= Security::e($roomNumber); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-filter-field">
                    <label for="dashboardDebtLevel">Mức nợ</label>
                    <select id="dashboardDebtLevel" class="form-select form-select-sm" data-filter-key="debtLevel">
                        <option value="">Tất cả mức</option>
                        <option value="high">Từ 3.000.000 đ</option>
                        <option value="medium">1.000.000 - 2.999.999 đ</option>
                        <option value="low">Dưới 1.000.000 đ</option>
                    </select>
                </div>
                <div class="admin-filter-field">
                    <label for="dashboardDebtDue">Thời hạn</label>
                    <select id="dashboardDebtDue" class="form-select form-select-sm" data-filter-key="dueState">
                        <option value="">Tất cả thời hạn</option>
                        <option value="open">Không ngày ra</option>
                        <option value="active">Còn hạn</option>
                        <option value="soon">Sắp hết hạn 30 ngày</option>
                        <option value="overdue">Quá hạn</option>
                    </select>
                </div>
                <div class="admin-filter-actions">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-filter-clear>Đặt lại</button>
                </div>
            </div>
            <table id="dashboardDebtTable" class="table datatable table-hover align-middle">
                <thead>
                <tr>
                    <th>Sinh viên</th>
                    <th>Mã SV</th>
                    <th>Phòng</th>
                    <th class="text-danger">Công nợ</th>
                    <th>Thời hạn</th>
                </tr>
                </thead>
                <tbody>
                    <?php foreach ($dashboardDebtRows as $row): ?>
                        <?php
                        $dashboardDebt = (float) ($row['debt'] ?? 0);
                        $dashboardDebtLevel = $dashboardDebt >= 3000000 ? 'high' : ($dashboardDebt >= 1000000 ? 'medium' : 'low');
                        $dashboardDueState = 'open';
                        if (!empty($row['end_date'])) {
                            try {
                                $dashboardEndDate = new DateTimeImmutable((string) $row['end_date']);
                                if ($dashboardEndDate < $dashboardToday) {
                                    $dashboardDueState = 'overdue';
                                } elseif ($dashboardEndDate <= $dashboardSoonLimit) {
                                    $dashboardDueState = 'soon';
                                } else {
                                    $dashboardDueState = 'active';
                                }
                            } catch (Exception) {
                                $dashboardDueState = 'open';
                            }
                        }
                        $dashboardDebtRoom = !empty($row['room_number']) ? 'P' . (string) $row['room_number'] : '';
                        ?>
                        <tr data-room="<?= Security::e($dashboardDebtRoom); ?>"
                            data-debt-level="<?= Security::e($dashboardDebtLevel); ?>"
                            data-due-state="<?= Security::e($dashboardDueState); ?>">
                            <td><?= Security::e((string) $row['full_name']); ?></td>
                            <td><?= Security::e((string) $row['student_code']); ?></td>
                            <td>P<?= Security::e((string) $row['room_number']); ?></td>
                            <td class="text-danger fw-bold"><?= number_format((float) $row['debt'], 0, ',', '.'); ?> đ</td>
                            <td><?= $row['end_date'] ? Security::e((string) $row['end_date']) : '-'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (count($studentsWithDebt) > 10): ?>
                <div class="mt-3 text-center text-muted small">
                    ... và <?= count($studentsWithDebt) - 10; ?> sinh viên nữa nợ tiền phòng
                </div>
            <?php endif; ?>
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
                            <option value="<?= Security::e((string) $floor); ?>">Tầng <?= Security::e((string) $floor); ?></option>
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
                <div class="admin-filter-actions">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-filter-clear>Đặt lại</button>
                </div>
            </div>
            <table id="dashboardTopRoomsTable" class="table datatable table-hover align-middle">
                <thead><tr><th>Mã phòng</th><th>Tầng</th><th>Sức chứa</th><th>Đang ở</th><th>Điểm TB</th></tr></thead>
                <tbody>
                    <?php foreach ($topRooms as $row): ?>
                        <?php
                        $dashboardRoomCapacity = max(0, (int) ($row['capacity'] ?? 0));
                        $dashboardRoomOccupancy = max(0, (int) ($row['occupancy'] ?? 0));
                        if ($dashboardRoomCapacity > 0 && $dashboardRoomOccupancy >= $dashboardRoomCapacity) {
                            $dashboardRoomOccupancyState = 'full';
                        } elseif ($dashboardRoomOccupancy > 0) {
                            $dashboardRoomOccupancyState = 'occupied';
                        } else {
                            $dashboardRoomOccupancyState = 'empty';
                        }
                        $dashboardRoomScore = (float) ($row['avg_boarding_score'] ?? 0);
                        $dashboardRoomScoreBand = $dashboardRoomScore >= 80 ? 'high' : ($dashboardRoomScore >= 60 ? 'medium' : ($dashboardRoomScore > 0 ? 'low' : 'none'));
                        ?>
                        <tr data-floor="<?= Security::e((string) $row['floor_number']); ?>"
                            data-occupancy-state="<?= Security::e($dashboardRoomOccupancyState); ?>"
                            data-score-band="<?= Security::e($dashboardRoomScoreBand); ?>">
                            <td class="fw-semibold">P<?= Security::e((string) $row['room_number']); ?></td>
                            <td><?= Security::e((string) $row['floor_number']); ?></td>
                            <td><?= Security::e((string) $row['capacity']); ?></td>
                            <td><?= Security::e((string) $row['occupancy']); ?>/<?= Security::e((string) $row['capacity']); ?></td>
                            <td><?= Security::e((string) $row['avg_boarding_score']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="panel-glass rounded-5 p-4 h-100">
            <div class="section-subtitle text-uppercase fw-semibold small mb-2">Điểm nhấn giao diện</div>
            <h2 class="section-title mb-3">Xin chào, <?= Security::e($currentUser['full_name'] ?? 'Admin'); ?></h2>
            <div class="d-grid gap-3">
                <div class="d-flex gap-3"><div class="icon-badge primary flex-shrink-0"><i class="bi bi-layout-sidebar-inset"></i></div><div><div class="fw-semibold">Sidebar cố định</div><div class="text-secondary">Menu chính luôn hiển thị để điều hướng nhanh.</div></div></div>
<?php require_once __DIR__ . '/../../views/partials/admin_footer.php'; ?>
