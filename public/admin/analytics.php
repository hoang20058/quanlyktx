<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAdminAuth();

$pageTitle = 'Analytics - ' . APP_NAME;
$activeMenu = 'dashboard';

$roomDist = RoomRepository::roomStatusDistribution();
$priority = StudentRepository::priorityDistribution();
$topRooms = RoomRepository::topRooms(5);
$lowStudents = StudentRepository::lowScoringStudents(50);

require_once __DIR__ . '/../../views/partials/admin_header.php';
?>
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="panel-glass rounded-4 p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div><h3 class="mb-0">Trạng thái phòng</h3><div class="small text-muted">Tỷ lệ % theo tình trạng phòng</div></div>
            </div>
            <canvas id="roomPie"></canvas>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel-glass rounded-4 p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div><h3 class="mb-0">Phân bổ ưu tiên</h3><div class="small text-muted">Số lượng sinh viên theo mức ưu tiên</div></div>
            </div>
            <canvas id="priorityBar"></canvas>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="table-panel p-4 h-100">
            <h4 class="mb-3">Top 5 phòng</h4>
            <table class="table table-sm table-hover">
                <thead><tr><th>Phòng</th><th>Đang ở</th><th>Điểm TB</th></tr></thead>
                <tbody>
                <?php foreach ($topRooms as $r): ?>
                    <tr>
                        <td>P<?= Security::e((string)$r['room_number']); ?></td>
                        <td><?= Security::e((string)$r['occupancy']); ?>/<?= Security::e((string)$r['capacity']); ?></td>
                        <td><?= Security::e((string)$r['avg_boarding_score']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel-glass rounded-4 p-4 h-100">
            <h4 class="mb-3">Danh sách cảnh báo (score < 50)</h4>
            <table class="table table-sm table-hover">
                <thead><tr><th>Sinh viên</th><th>Phòng</th><th>Điểm</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($lowStudents as $s): ?>
                    <tr>
                        <td><?= Security::e((string)$s['full_name']); ?> <div class="small text-muted"><?= Security::e((string)$s['student_code']); ?></div></td>
                        <td><?= Security::e((string)$s['room_number']); ?></td>
                        <td><?= Security::e((string)$s['boarding_score']); ?></td>
                        <td><a href="../notices.php?target=student&amp;id=<?= Security::e((string)$s['student_id']); ?>" class="btn btn-sm btn-outline-danger">Gửi Notice</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../views/partials/admin_footer.php'; ?>

<script>
    (function () {
        const roomData = <?= json_encode(array_values($roomDist)); ?>;
        const roomLabels = ['Còn trống hoàn toàn','Đang có người ở','Đã đầy','Đang sửa chữa'];
        const ctxPie = document.getElementById('roomPie').getContext('2d');
        new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: roomLabels,
                datasets: [{
                    data: roomData,
                    backgroundColor: ['#10b981','#3b82f6','#ef4444','#f59e0b']
                }]
            },
            options: { responsive: true }
        });

        const priorityData = <?= json_encode(array_values($priority)); ?>;
        const priorityLabels = ['1','2','3','4','5','6','7','8'];
        const ctxBar = document.getElementById('priorityBar').getContext('2d');
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: priorityLabels,
                datasets: [{ label: 'Số lượng sinh viên', data: priorityData, backgroundColor: '#2563eb' }]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true, precision:0 } } }
        });
    })();
</script>
