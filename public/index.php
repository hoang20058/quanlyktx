<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

function fetchHomeRooms(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT r.*,
               COUNT(c.contract_id) AS occupied_count,
               ROUND(COALESCE(AVG(s.boarding_score), 0), 2) AS avg_boarding_score
          FROM Room r
     LEFT JOIN Contract c ON c.room_id = r.room_id AND c.status = 'Đang ở'
     LEFT JOIN Student s ON s.student_id = c.student_id
      GROUP BY r.room_id
      ORDER BY r.room_number ASC
    ");

    return $stmt->fetchAll();
}

function fetchHomeNotices(PDO $pdo): array
{
    $stmt = $pdo->query('
        SELECT n.*, r.room_number, s.full_name AS student_name
          FROM Notice n
     LEFT JOIN Room r ON r.room_id = n.room_id
     LEFT JOIN Student s ON s.student_id = n.student_id
      ORDER BY n.date DESC, n.notice_id DESC
    ');

    return $stmt->fetchAll();
}

function fetchHomeRoomStats(PDO $pdo): array
{
    return [
        'totalRooms' => (int) $pdo->query('SELECT COUNT(*) FROM Room')->fetchColumn(),
        'activeRooms' => (int) $pdo->query("SELECT COUNT(*) FROM Room WHERE status = 'Hoạt động'")->fetchColumn(),
        'totalCapacity' => (int) $pdo->query('SELECT COALESCE(SUM(capacity), 0) FROM Room')->fetchColumn(),
        'occupied' => (int) $pdo->query("SELECT COUNT(*) FROM Contract WHERE status = 'Đang ở'")->fetchColumn(),
    ];
}

function fetchHomeStudentStats(PDO $pdo): array
{
    return [
        'waiting' => (int) $pdo->query("SELECT COUNT(*) FROM Student WHERE status = 'Chờ duyệt'")->fetchColumn(),
        'living' => (int) $pdo->query("SELECT COUNT(*) FROM Student WHERE status = 'Đang ở'")->fetchColumn(),
    ];
}

function fetchHomeTopRooms(PDO $pdo, int $limit): array
{
    $stmt = $pdo->prepare("
        SELECT r.room_id, r.room_number, r.floor_number, r.capacity, r.price, r.status,
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

function fetchHomeTopStudents(PDO $pdo, int $limit): array
{
    $stmt = $pdo->prepare('
        SELECT student_id, full_name, student_code, department, boarding_score
          FROM Student
      ORDER BY boarding_score DESC, student_id ASC
         LIMIT :limit
    ');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function fetchHomeUnpaidBills(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT b.*, r.room_number
          FROM UtilityBill b
          JOIN Room r ON r.room_id = b.room_id
         WHERE b.status = 'Chưa thanh toán'
      ORDER BY b.billing_year DESC, b.billing_month DESC
    ");

    return $stmt->fetchAll();
}

$pdo = Database::connection();
$pageTitle = 'Trang chủ - ' . APP_NAME;

$rooms = fetchHomeRooms($pdo);
$notices = fetchHomeNotices($pdo);
$roomStats = fetchHomeRoomStats($pdo);
$studentStats = fetchHomeStudentStats($pdo);
$topRooms = fetchHomeTopRooms($pdo, 5);
$topStudents = fetchHomeTopStudents($pdo, 5);
$unpaidBills = fetchHomeUnpaidBills($pdo);

$totalCapacity = max(1, (int) $roomStats['totalCapacity']);
$occupiedCount = (int) $roomStats['occupied'];
$occupancyRate = min(100, (int) round(($occupiedCount / $totalCapacity) * 100));
$activeRooms = array_values(array_filter($rooms, static fn (array $room): bool => (string) ($room['status'] ?? '') === 'Hoạt động'));
$availableRooms = array_values(array_filter($activeRooms, static fn (array $room): bool => (int) ($room['occupied_count'] ?? 0) < (int) ($room['capacity'] ?? 0)));
$latestNotices = array_slice($notices, 0, 5);
$highlightRooms = array_slice($rooms, 0, 8);
$visibleBills = array_slice($unpaidBills, 0, 6);
$visibleTopStudents = array_slice($topStudents, 0, 5);

require_once __DIR__ . '/../views/partials/public_header.php';
?>
<section class="public-home">
    <div class="public-hero" id="top">
        <div class="public-hero-media">
            <img src="https://images.unsplash.com/photo-1600585154340-be6161a56a0c?q=80&w=1800&auto=format&fit=crop" alt="Khu ký túc xá hiện đại">
        </div>
        <div class="container public-hero-content">
            <div class="public-hero-copy">
                <div class="public-kicker">Cổng thông tin nội trú sinh viên</div>
                <h1>Ký túc xá minh bạch, dễ tra cứu và sẵn sàng đăng ký.</h1>
                <p>Sinh viên có thể xem tình trạng phòng, theo dõi thông báo, tra cứu hóa đơn và gửi hồ sơ đăng ký nội trú trên một giao diện thống nhất.</p>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-primary btn-lg" href="<?= Security::e(APP_URL); ?>/register.php">
                        <i class="bi bi-pencil-square me-2"></i>Đăng ký nội trú
                    </a>
                    <a class="btn btn-light btn-lg" href="#rooms">
                        <i class="bi bi-door-open me-2"></i>Xem phòng
                    </a>
                    <a class="btn btn-outline-light btn-lg" href="<?= Security::e(APP_URL); ?>/bill-inquiry.php">
                        <i class="bi bi-receipt me-2"></i>Tra cứu hóa đơn
                    </a>
                </div>
            </div>
            <div class="public-hero-summary" aria-label="Tổng quan nhanh">
                <div>
                    <span>Phòng hoạt động</span>
                    <strong><?= Security::e((string) $roomStats['activeRooms']); ?></strong>
                </div>
                <div>
                    <span>Chỗ còn có thể đăng ký</span>
                    <strong><?= Security::e((string) max(0, $totalCapacity - $occupiedCount)); ?></strong>
                </div>
                <div>
                    <span>Hồ sơ chờ duyệt</span>
                    <strong><?= Security::e((string) $studentStats['waiting']); ?></strong>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <section class="home-metric-strip" aria-label="Chỉ số ký túc xá">
            <div class="home-metric">
                <span>Phòng còn nhận hồ sơ</span>
                <strong><?= Security::e((string) count($availableRooms)); ?></strong>
            </div>
            <div class="home-metric">
                <span>Sinh viên đang ở</span>
                <strong><?= Security::e((string) $studentStats['living']); ?></strong>
            </div>
            <div class="home-metric">
                <span>Tỷ lệ lấp đầy</span>
                <strong><?= Security::e((string) $occupancyRate); ?>%</strong>
            </div>
            <div class="home-metric">
                <span>Thông báo đang hiển thị</span>
                <strong><?= Security::e((string) count($notices)); ?></strong>
            </div>
        </section>
    </div>

    <section class="home-section" id="rooms">
        <div class="container">
            <div class="home-section-head">
                <div>
                    <span>Danh sách phòng</span>
                    <h2>Không gian đang vận hành</h2>
                </div>
                <a class="btn btn-outline-dark" href="<?= Security::e(APP_URL); ?>/register.php">Gửi hồ sơ đăng ký</a>
            </div>

            <div class="home-room-list">
                <?php if (empty($highlightRooms)): ?>
                    <div class="home-empty">Chưa có dữ liệu phòng để hiển thị.</div>
                <?php endif; ?>

                <?php foreach ($highlightRooms as $room): ?>
                    <?php
                    $capacity = max(1, (int) ($room['capacity'] ?? 0));
                    $occupied = max(0, (int) ($room['occupied_count'] ?? 0));
                    $usage = min(100, (int) round(($occupied / $capacity) * 100));
                    $roomStatus = (string) ($room['status'] ?? '');
                    $isAvailable = $roomStatus === 'Hoạt động' && $occupied < $capacity;
                    ?>
                    <article class="home-room-row">
                        <div class="home-room-id">
                            <span>Tầng <?= Security::e((string) $room['floor_number']); ?></span>
                            <strong>P<?= Security::e((string) $room['room_number']); ?></strong>
                        </div>
                        <div class="home-room-main">
                            <div class="home-room-title">
                                <strong><?= Security::e((string) $room['room_type']); ?></strong>
                                <span class="status-pill <?= $isAvailable ? 'available' : 'maintenance'; ?>">
                                    <?= $isAvailable ? 'Còn chỗ' : Security::e($roomStatus); ?>
                                </span>
                            </div>
                            <div class="home-meter" aria-label="Tỷ lệ sử dụng phòng">
                                <span style="width: <?= $usage; ?>%"></span>
                            </div>
                        </div>
                        <div class="home-room-data">
                            <span><?= Security::e((string) $occupied); ?>/<?= Security::e((string) $capacity); ?> người</span>
                            <span><?= number_format((float) ($room['price'] ?? 0), 0, ',', '.'); ?> đ/tháng</span>
                            <span>Điểm TB <?= Security::e((string) ($room['avg_boarding_score'] ?? 0)); ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="home-section home-section-soft" id="notices">
        <div class="container">
            <div class="home-two-column">
                <div>
                    <div class="home-section-head compact">
                        <div>
                            <span>Thông báo</span>
                            <h2>Cập nhật mới cho sinh viên</h2>
                        </div>
                    </div>
                    <div class="home-notice-list">
                        <?php if (empty($latestNotices)): ?>
                            <div class="home-empty">Chưa có thông báo mới.</div>
                        <?php endif; ?>

                        <?php foreach ($latestNotices as $notice): ?>
                            <article class="home-notice-item">
                                <time><?= Security::e((string) $notice['date']); ?></time>
                                <div>
                                    <strong><?= Security::e((string) $notice['category']); ?></strong>
                                    <p><?= Security::e((string) $notice['description']); ?></p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>

                <aside class="home-process-panel">
                    <span>Quy trình đăng ký</span>
                    <h2>Từ hồ sơ đến phân phòng</h2>
                    <ol class="home-process-list">
                        <li>
                            <strong>Gửi hồ sơ</strong>
                            <p>Sinh viên nhập thông tin cá nhân, ngành/khoa và đối tượng ưu tiên.</p>
                        </li>
                        <li>
                            <strong>Ban quản lý xét duyệt</strong>
                            <p>Hồ sơ được đưa vào danh sách chờ duyệt để admin kiểm tra và gán phòng.</p>
                        </li>
                        <li>
                            <strong>Tạo hợp đồng</strong>
                            <p>Khi duyệt thành công, hệ thống ghi nhận sinh viên đang ở và tạo hợp đồng nội trú.</p>
                        </li>
                    </ol>
                </aside>
            </div>
        </div>
    </section>

    <section class="home-section" id="leaderboard">
        <div class="container">
            <div class="home-grid-dashboard">
                <div>
                    <div class="home-section-head compact">
                        <div>
                            <span>Xếp hạng phòng</span>
                            <h2>Phòng nổi bật theo sức chứa và điểm nội trú</h2>
                        </div>
                    </div>
                    <div class="home-ranking-table">
                        <table>
                            <thead>
                            <tr>
                                <th>Phòng</th>
                                <th>Đang ở</th>
                                <th>Điểm TB</th>
                                <th>Giá</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($topRooms as $room): ?>
                                <tr>
                                    <td>P<?= Security::e((string) $room['room_number']); ?></td>
                                    <td><?= Security::e((string) $room['occupancy']); ?>/<?= Security::e((string) $room['capacity']); ?></td>
                                    <td><?= Security::e((string) $room['avg_boarding_score']); ?></td>
                                    <td><?= number_format((float) ($room['price'] ?? 0), 0, ',', '.'); ?> đ</td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div>
                    <div class="home-section-head compact">
                        <div>
                            <span>Sinh viên tiêu biểu</span>
                            <h2>Điểm nội trú cao</h2>
                        </div>
                    </div>
                    <div class="home-student-list">
                        <?php if (empty($visibleTopStudents)): ?>
                            <div class="home-empty">Chưa có dữ liệu sinh viên.</div>
                        <?php endif; ?>

                        <?php foreach ($visibleTopStudents as $index => $student): ?>
                            <div class="home-student-row">
                                <span><?= $index + 1; ?></span>
                                <div>
                                    <strong><?= Security::e((string) $student['full_name']); ?></strong>
                                    <small><?= Security::e((string) ($student['student_code'] ?: 'Chưa có mã')); ?> · <?= Security::e((string) ($student['department'] ?: 'Chưa cập nhật khoa')); ?></small>
                                </div>
                                <b><?= Security::e((string) $student['boarding_score']); ?></b>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="home-section home-section-soft">
        <div class="container">
            <div class="home-bill-panel">
                <div>
                    <span>Tra cứu tài chính</span>
                    <h2>Hóa đơn điện nước chưa thanh toán</h2>
                    <p>Danh sách này giúp sinh viên nhanh chóng nhận biết các phòng còn hóa đơn cần xử lý.</p>
                    <a class="btn btn-primary" href="<?= Security::e(APP_URL); ?>/bill-inquiry.php">Tra cứu theo phòng</a>
                </div>
                <div class="home-bill-list">
                    <?php if (empty($visibleBills)): ?>
                        <div class="home-empty">Hiện không có hóa đơn chưa thanh toán.</div>
                    <?php endif; ?>

                    <?php foreach ($visibleBills as $bill): ?>
                        <div class="home-bill-row">
                            <strong>P<?= Security::e((string) $bill['room_number']); ?></strong>
                            <span><?= Security::e((string) $bill['billing_month']); ?>/<?= Security::e((string) $bill['billing_year']); ?></span>
                            <b><?= number_format((float) $bill['total_amount'], 0, ',', '.'); ?> đ</b>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="home-final-cta">
        <div class="container">
            <div class="home-final-inner">
                <div>
                    <span>Sẵn sàng nộp hồ sơ?</span>
                    <h2>Đăng ký nội trú và chờ ban quản lý xét duyệt.</h2>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-light btn-lg" href="<?= Security::e(APP_URL); ?>/register.php">Mở form đăng ký</a>
                    <a class="btn btn-outline-light btn-lg" href="<?= Security::e(APP_URL); ?>/contact.php">Liên hệ hỗ trợ</a>
                </div>
            </div>
        </div>
    </section>
</section>

<?php require_once __DIR__ . '/../views/partials/public_footer.php'; ?>
