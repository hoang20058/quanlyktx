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

require_once __DIR__ . '/../../views/partials/admin_header.php';
?>
<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="admin-stat-card card border-0"><div class="card-body p-4"><div class="d-flex justify-content-between align-items-start"><div><div class="text-secondary small mb-2">Tổng phòng</div><div class="stat-value"><?= Security::e((string) $roomStats['totalRooms']); ?></div></div><div class="icon-badge primary"><i class="bi bi-door-open"></i></div></div><div class="small text-success mt-3 fw-semibold"><i class="bi bi-arrow-up-right"></i> Phòng hoạt động: <?= Security::e((string) $roomStats['activeRooms']); ?></div></div></div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="admin-stat-card card border-0"><div class="card-body p-4"><div class="d-flex justify-content-between align-items-start"><div><div class="text-secondary small mb-2">Sinh viên nội trú</div><div class="stat-value"><?= Security::e((string) $studentStats['living']); ?></div></div><div class="icon-badge blue"><i class="bi bi-mortarboard"></i></div></div><div class="small text-success mt-3 fw-semibold"><i class="bi bi-arrow-up-right"></i> Chờ duyệt: <?= Security::e((string) $studentStats['waiting']); ?></div>`</div></div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="admin-stat-card card border-0"><div class="card-body p-4"><div class="d-flex justify-content-between align-items-start"><div><div class="text-secondary small mb-2">Sức chứa tổng</div><div class="stat-value"><?= Security::e((string) $roomStats['totalCapacity']); ?></div></div><div class="icon-badge amber"><i class="bi bi-receipt"></i></div></div><div class="small text-warning mt-3 fw-semibold"></div></div></div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="admin-stat-card card border-0"><div class="card-body p-4"><div class="d-flex justify-content-between align-items-start"><div><div class="text-secondary small mb-2">Thông báo mới</div><div class="stat-value"><?= Security::e((string) $noticeCount); ?></div></div><div class="icon-badge rose"><i class="bi bi-megaphone"></i></div></div><div class="small text-primary mt-3 fw-semibold"><i class="bi bi-bell"></i> Sinh viên cao điểm: <?= Security::e((string) $studentStats['topScore']); ?></div></div></div>
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
            <table class="table datatable table-hover align-middle">
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
                    <?php foreach (array_slice($studentsWithDebt, 0, 10) as $row): ?>
                        <tr>
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
                <div class="table-actions d-flex gap-2"><a class="btn btn-outline-dark" href="rooms.php"><i class="bi bi-grid-3x3-gap me-1"></i>Toàn bộ phòng</a><button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#roomModal"><i class="bi bi-plus-lg me-1"></i>Thêm phòng</button></div>
            </div>
            <table class="table datatable table-hover align-middle">
                <thead><tr><th>Mã phòng</th><th>Tầng</th><th>Sức chứa</th><th>Đang ở</th><th>Điểm TB</th></tr></thead>
                <tbody>
                    <?php foreach ($topRooms as $row): ?>
                        <tr>
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
                <div class="d-flex gap-3"><div class="icon-badge blue flex-shrink-0"><i class="bi bi-window-stack"></i></div><div><div class="fw-semibold">Topbar linh hoạt</div><div class="text-secondary">Ô tìm kiếm và truy cập site public ngay trong header.</div></div></div>
                <div class="d-flex gap-3"><div class="icon-badge amber flex-shrink-0"><i class="bi bi-table"></i></div><div><div class="fw-semibold">DataTables sẵn sàng</div><div class="text-secondary">Chuẩn hóa cho các màn CRUD sau này.</div></div></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="roomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0"><div><div class="section-subtitle text-uppercase fw-semibold small">Form CRUD</div><h5 class="modal-title">Thêm / Sửa phòng</h5></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body pt-3">
                <div class="row g-4">
                    <div class="col-lg-8">
                        <form class="row g-3">
                            <input type="hidden" name="room_id" value="0">
                            <div class="col-md-4"><label class="form-label">Số phòng</label><input name="room_number" class="form-control" type="number" placeholder="101"></div>
                            <div class="col-md-4"><label class="form-label">Tầng</label><input name="floor_number" class="form-control" type="number" placeholder="1"></div>
                            <div class="col-md-4"><label class="form-label">Sức chứa</label><input name="capacity" class="form-control" type="number" placeholder="6"></div>
                            <div class="col-md-4"><label class="form-label">Loại phòng</label><select name="room_type" class="form-select"><option>Dịch vụ</option><option selected>Thường</option></select></div>
                            <div class="col-md-4"><label class="form-label">Giá phòng</label><input name="price" class="form-control" type="number" placeholder="650000"></div>
                            <div class="col-md-4"><label class="form-label">Trạng thái</label><select name="status" class="form-select"><option selected>Hoạt động</option><option>Đang sửa chữa</option></select></div>
                        </form>
                    </div>
                    <div class="col-lg-4"><div class="panel-glass rounded-4 p-4 h-100"><div class="fw-semibold mb-2">Mẫu thao tác</div><p class="text-secondary mb-3">Khi bước sang giai đoạn 4, form này sẽ được nối với logic tạo/sửa phòng thật bằng AJAX hoặc submit chuẩn.</p><div class="alert alert-warning border-0 mb-0">Phần này hiện chỉ là giao diện chuẩn hóa, chưa gắn xử lý dữ liệu.</div></div></div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button><button type="button" class="btn btn-dark">Lưu phòng</button></div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../views/partials/admin_footer.php'; ?>
