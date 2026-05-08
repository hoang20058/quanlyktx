<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

$pageTitle = 'Trang chủ - ' . APP_NAME;

$rooms = RoomRepository::all();
$notices = NoticeRepository::all();
$roomStats = RoomRepository::stats();
$studentStats = StudentRepository::registrationStats();
$topRooms = RoomRepository::topRooms(5);
$topStudents = StudentRepository::topStudents(5);
$unpaidBills = UtilityBillRepository::unpaidBills();

$stats = [
    ['label' => 'Phòng đang hoạt động', 'value' => (string) $roomStats['activeRooms'], 'icon' => 'bi-door-open', 'class' => 'primary'],
    ['label' => 'Sinh viên nội trú', 'value' => (string) $studentStats['living'], 'icon' => 'bi-mortarboard', 'class' => 'blue'],
    ['label' => 'Hồ sơ chờ duyệt', 'value' => (string) $studentStats['waiting'], 'icon' => 'bi-file-earmark-text', 'class' => 'amber'],
    ['label' => 'Thông báo', 'value' => (string) count($notices), 'icon' => 'bi-megaphone', 'class' => 'rose'],
];

require_once __DIR__ . '/../views/partials/public_header.php';
?>
<div class="hero-full-screen">
    <div class="hero-background">
        <img src="https://images.unsplash.com/photo-1580587771525-78b9dba3b914?q=80&w=2874&auto=format&fit=crop" alt="Ký túc xá hiện đại">
    </div>
    <div class="hero-content container">
        <div class="row">
            <div class="col-lg-8">
                <div class="hero-kicker mb-3"><i class="bi bi-stars"></i> Nền tảng quản lý ký túc xá tập trung</div>
                <h1 class="hero-title fw-bold mb-3">Quản lý phòng ở, hợp đồng, hóa đơn và thông báo.</h1>
                <p class="hero-lead mb-4">Giao diện public cho sinh viên xem phòng trống, thông báo mới, bảng xếp hạng và đăng ký nội trú ngay trên một trang.</p>
                <div class="d-flex flex-wrap gap-3">
                    <a class="btn btn-light btn-lg rounded-pill px-4" href="#rooms"><i class="bi bi-search me-2"></i>Xem phòng trống</a>
                    <a class="btn btn-outline-light btn-lg rounded-pill px-4" href="register.php">Đăng ký ở KTX</a>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="container my-5">
    <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner panel-glass rounded-4">
            <div class="carousel-item active">
                <div class="p-4 p-lg-5">
                    <div class="text-muted mb-1">Cập nhật hôm nay</div>
                    <div class="fs-3 fw-bold"><?= date('d/m/Y'); ?></div>
                    <div class="mt-4 panel-glass rounded-4 p-3 bg-light">
                        <div class="small text-muted mb-1">Phòng hoạt động</div>
                        <div class="h3 mb-0"><?= Security::e((string) $roomStats['activeRooms']); ?></div>
                    </div>
                </div>
            </div>
            <div class="carousel-item">
                <div class="p-4 p-lg-5">
                    <div class="text-muted mb-1">Hồ sơ chờ duyệt</div>
                    <div class="fs-3 fw-bold"><?= Security::e((string) $studentStats['waiting']); ?></div>
                    <div class="mt-4 panel-glass rounded-4 p-3 bg-light">
                        <div class="small text-muted mb-1">Điểm cao nhất</div>
                        <div class="h3 mb-0"><?= Security::e((string) $studentStats['topScore']); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
    </div>
</section>

<section class="container my-5">
    <div class="row g-4">
        <?php foreach ($stats as $stat): ?>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="stat-card h-100 p-4">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <div class="text-muted mb-2"><?= Security::e($stat['label']); ?></div>
                            <div class="stat-value"><?= Security::e($stat['value']); ?></div>
                        </div>
                        <div class="icon-badge <?= Security::e($stat['class']); ?>"><i class="bi <?= Security::e($stat['icon']); ?>"></i></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="container my-5" id="rooms">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
        <div>
            <div class="section-subtitle text-uppercase fw-semibold small">Danh sách phòng</div>
            <h2 class="section-title mb-0">Phòng đang sẵn sàng cho sinh viên</h2>
        </div>
        <a class="btn btn-outline-dark rounded-pill px-4" href="admin/rooms.php">Xem bảng quản lý</a>
    </div>
    <div class="row g-4">
        <?php foreach ($rooms as $room): ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="room-card p-4 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <div class="text-uppercase small text-muted fw-semibold">Tầng <?= Security::e((string) $room['floor_number']); ?></div>
                            <h3 class="h4 mb-1">P<?= Security::e((string) $room['room_number']); ?></h3>
                        </div>
                        <span class="status-pill <?= $room['status'] === 'Hoạt động' ? 'available' : 'maintenance'; ?>"><?= Security::e((string) $room['status']); ?></span>
                    </div>
                    <div class="room-meta mb-3">Loại phòng: <?= Security::e((string) $room['room_type']); ?></div>
                    <div class="d-flex justify-content-between mb-3">
                        <div><div class="small text-muted">Sức chứa</div><div class="fw-semibold"><?= Security::e((string) $room['capacity']); ?> người</div></div>
                        <div><div class="small text-muted">Đang ở</div><div class="fw-semibold"><?= Security::e((string) $room['occupied_count']); ?> người</div></div>
                        <div><div class="small text-muted">Giá phòng</div><div class="fw-semibold"><?= number_format((float) $room['price'], 0, ',', '.'); ?> đ</div></div>
                    </div>
                    <div class="progress mb-2" style="height:10px; border-radius:999px;">
                        <?php $usage = (int) ($room['capacity'] > 0 ? round(($room['occupied_count'] / $room['capacity']) * 100) : 0); ?>
                        <div class="progress-bar bg-primary" style="width: <?= $usage; ?>%"></div>
                    </div>
                    <div class="small text-muted">Tỷ lệ sử dụng: <?= $usage; ?>%</div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="container my-5" id="notices">
    <div class="row g-4 align-items-stretch">
        <div class="col-lg-7">
            <div class="notice-card p-4 p-lg-5 h-100">
                <div class="section-subtitle text-uppercase fw-semibold small mb-2">Thông báo mới nhất</div>
                <h2 class="section-title mb-4">Các cập nhật quan trọng cho sinh viên</h2>
                <div class="d-grid gap-4">
                    <?php foreach ($notices as $notice): ?>
                        <div class="timeline-step">
                            <div class="d-flex flex-wrap justify-content-between gap-2">
                                <h3 class="h5 mb-1"><?= Security::e((string) $notice['category']); ?></h3>
                                <span class="badge text-bg-light text-muted rounded-pill"><?= Security::e((string) $notice['date']); ?></span>
                            </div>
                            <p class="mb-0 text-muted"><?= Security::e((string) $notice['description']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="feature-card p-4 p-lg-5 h-100">
                <div class="section-subtitle text-uppercase fw-semibold small mb-2">Trải nghiệm sinh viên</div>
                <h2 class="section-title mb-4">Từ xem phòng đến ở nội trú</h2>
                <div class="d-grid gap-3">
                    <div class="d-flex gap-3 align-items-start"><div class="icon-badge primary flex-shrink-0"><i class="bi bi-search"></i></div><div><div class="fw-semibold">Tra cứu nhanh</div><div class="text-muted">Sinh viên xem phòng trống, sức chứa và mức phí ngay trên trang chủ.</div></div></div>
                    <div class="d-flex gap-3 align-items-start"><div class="icon-badge blue flex-shrink-0"><i class="bi bi-file-earmark-text"></i></div><div><div class="fw-semibold">Đăng ký lưu trú</div><div class="text-muted">Hồ sơ đăng ký được lưu vào bảng Student với trạng thái Chờ duyệt.</div></div></div>
                    <div class="d-flex gap-3 align-items-start"><div class="icon-badge amber flex-shrink-0"><i class="bi bi-receipt"></i></div><div><div class="fw-semibold">Thanh toán minh bạch</div><div class="text-muted">Hóa đơn điện nước, phí phòng và công nợ được chuẩn hóa theo tháng.</div></div></div>
                </div>
                <div class="mt-4 p-3 rounded-4 bg-light border">
                    <div class="small text-secondary mb-1">Admin demo</div>
                    <div class="fw-semibold">Mở khu vực quản trị để xem dashboard, CRUD và bảng xếp hạng.</div>
                    <a class="btn btn-dark rounded-pill px-4 mt-3" href="admin/">Vào Admin</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="container mb-5">
    <div class="alert alert-danger d-flex align-items-center" role="alert">
        <i class="bi bi-receipt-cutoff me-2" style="font-size:1.25rem"></i>
        <div>
            <strong>💳 Hóa đơn chưa thanh toán</strong>
            <div class="small">Danh sách các hóa đơn điện nước tháng này đang chờ thanh toán.</div>
        </div>
    </div>
    <div class="table-panel p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-0">Danh sách chưa thanh toán</h4>
                <div class="small text-muted">Hóa đơn điện nước có status = "Chưa thanh toán"</div>
            </div>
            <div><a href="bill-inquiry.php" class="btn btn-outline-primary">Tra cứu hóa đơn của bạn</a></div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead><tr><th>Phòng</th><th>Tháng/Năm</th><th>Số tiền</th><th>Trạng thái</th></tr></thead>
                <tbody>
                <?php foreach (array_slice($unpaidBills, 0, 20) as $bill): ?>
                    <tr>
                        <td class="fw-semibold">P<?= Security::e((string)$bill['room_number']); ?></td>
                        <td><?= Security::e((string)$bill['billing_month']); ?>/<?= Security::e((string)$bill['billing_year']); ?></td>
                        <td><?= number_format((float)$bill['total_amount'], 0, ',', '.'); ?> đ</td>
                        <td><span class="badge text-bg-warning">⚠ Chưa thanh toán</span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="section-subtitle text-uppercase fw-semibold small mb-2">Leaderboard</div>
    <h2 class="section-title mb-3">Top phòng nổi bật</h2>
    <div class="row g-4">
        <?php foreach ($topRooms as $room): ?>
            <?php $studentsInRoom = RoomRepository::studentsByRoom((int)$room['room_id']); ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="room-card p-4 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <div class="text-uppercase small text-muted fw-semibold">Tầng <?= Security::e((string) $room['floor_number']); ?></div>
                            <h3 class="h4 mb-1">P<?= Security::e((string) $room['room_number']); ?></h3>
                        </div>
                        <span class="status-pill <?= $room['status'] === 'Hoạt động' ? 'available' : 'maintenance'; ?>">P<?= Security::e((string) $room['room_number']); ?></span>
                    </div>
                    <div class="room-meta mb-3">Giá: <?= number_format((float) $room['price'], 0, ',', '.'); ?> đ · Sức chứa <?= Security::e((string)$room['capacity']); ?></div>
                    <div class="d-flex gap-3 mb-3">
                        <?php foreach (array_slice($studentsInRoom, 0, 5) as $s): ?>
                            <div class="text-center small">
                                <img src="https://i.pravatar.cc/40?u=<?= Security::e((string)$s['student_id']); ?>" class="rounded-circle mb-1" alt="avatar"><br>
                                <?= Security::e((string)$s['full_name']); ?><br>
                                <span class="text-muted"><?= Security::e((string)$s['boarding_score']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="small text-muted">Đang ở: <?= Security::e((string)$room['occupancy']); ?>/<?= Security::e((string)$room['capacity']); ?></div>
                        <div class="fw-semibold">Điểm TB: <?= Security::e((string)$room['avg_boarding_score']); ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="container mb-5" id="cta">
    <div class="row g-4">
        <div class="col-md-6">
            <div class="feature-card p-5 h-100 text-center" style="background: linear-gradient(135deg, var(--app-primary), var(--app-accent)); color: #fff;">
                <div class="mb-3" style="font-size: 3rem;"><i class="bi bi-pencil-square"></i></div>
                <h3 class="fw-bold mb-3">Đăng ký nội trú</h3>
                <p class="mb-4">Gửi hồ sơ của bạn để ban quản lý duyệt và phân phòng</p>
                <a href="register.php" class="btn btn-light rounded-pill px-5 fw-semibold">Đi đến form đăng ký</a>
            </div>
        </div>
        <div class="col-md-6">
            <div class="feature-card p-5 h-100 text-center" style="background: linear-gradient(135deg, #16a34a, #4ade80); color: #fff;">
                <div class="mb-3" style="font-size: 3rem;"><i class="bi bi-chat-left-dots"></i></div>
                <h3 class="fw-bold mb-3">Liên hệ với chúng tôi</h3>
                <p class="mb-4">Có câu hỏi hoặc cần hỗ trợ? Gửi tin nhắn cho chúng tôi ngay</p>
                <a href="contact.php" class="btn btn-light rounded-pill px-5 fw-semibold">Mở trang liên hệ</a>
            </div>
        </div>
    </div>
</section>

<section class="container mb-5" id="process">
    <div class="panel-glass rounded-5 p-4 p-lg-5">
        <div class="row g-4 align-items-center">
            <div class="col-lg-5">
                <div class="section-subtitle text-uppercase fw-semibold small mb-2">Quy trình sơ bộ</div>
                <h2 class="section-title mb-3">Luồng vận hành khi hệ thống hoàn chỉnh</h2>
                <p class="section-subtitle mb-0">Trang chủ đã có dữ liệu thật, form đăng ký, leaderboard và liên kết đến khu vực quản trị.</p>
            </div>
            <div class="col-lg-7">
                <div class="row g-3">
                    <div class="col-md-4"><div class="feature-card p-4 h-100"><div class="fw-bold mb-2">1. Xem phòng</div><div class="text-secondary">Sinh viên duyệt danh sách phòng trống và lựa chọn phòng phù hợp.</div></div></div>
                    <div class="col-md-4"><div class="feature-card p-4 h-100"><div class="fw-bold mb-2">2. Gửi hồ sơ</div><div class="text-secondary">Form đăng ký tạo hồ sơ sinh viên với trạng thái Chờ duyệt.</div></div></div>
                    <div class="col-md-4"><div class="feature-card p-4 h-100"><div class="fw-bold mb-2">3. Xử lý nội trú</div><div class="text-secondary">Admin quản lý sinh viên, phòng và thông báo ngay trong dashboard.</div></div></div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/../views/partials/public_footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const mapping = <?= json_encode(array_combine(range(1,8), array_map('strval', array_map('getPriorityDescription', range(1,8))))); ?>;
    const sel = document.getElementById('priority_level');
    const desc = document.getElementById('priority_desc');
    if (sel && desc) {
        sel.addEventListener('change', function () {
            const v = parseInt(this.value, 10) || 8;
            desc.textContent = mapping[v] || mapping[8];
        });
    }
});
</script>
