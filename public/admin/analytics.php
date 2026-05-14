<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAdminAuth();

$pageTitle = 'Analytics - ' . APP_NAME;
$activeMenu = 'analytics';

$roomStats = RoomRepository::stats();
$roomDist = RoomRepository::roomStatusDistribution();
$priority = StudentRepository::priorityDistribution();
$rooms = RoomRepository::all();
$students = StudentRepository::all();
$contracts = ContractRepository::all();
$debtRows = ContractRepository::studentsWithDebt();
$bills = UtilityBillRepository::all();

$totalDebt = array_sum(array_map(static fn (array $row): float => (float) ($row['debt'] ?? 0), $debtRows));
$totalContractValue = array_sum(array_map(static fn (array $row): float => (float) ($row['price'] ?? 0), $contracts));
$totalPaid = array_sum(array_map(static fn (array $row): float => (float) ($row['deposit'] ?? 0), $contracts));
$unpaidBills = array_values(array_filter($bills, static fn (array $bill): bool => (string) $bill['status'] !== 'Đã thanh toán'));
$unpaidBillTotal = array_sum(array_map(static fn (array $bill): float => (float) ($bill['total_amount'] ?? 0), $unpaidBills));
$totalBillAmount = array_sum(array_map(static fn (array $bill): float => (float) ($bill['total_amount'] ?? 0), $bills));
$paidBillTotal = max(0, $totalBillAmount - $unpaidBillTotal);

$capacity = max(0, (int) ($roomStats['totalCapacity'] ?? 0));
$occupied = max(0, (int) ($roomStats['occupied'] ?? 0));
$occupancyRate = $capacity > 0 ? round(($occupied / $capacity) * 100, 1) : 0.0;
$collectionRate = $totalBillAmount > 0 ? round(($paidBillTotal / $totalBillAmount) * 100, 1) : 0.0;
$avgLivingScoreRows = array_values(array_filter($students, static fn (array $student): bool => (string) ($student['display_status'] ?? $student['status'] ?? '') === 'Đang ở'));
$avgLivingScore = count($avgLivingScoreRows) > 0
    ? round(array_sum(array_map(static fn (array $student): int => (int) ($student['boarding_score'] ?? 0), $avgLivingScoreRows)) / count($avgLivingScoreRows), 1)
    : 0.0;

$today = new DateTimeImmutable('today');
$expiryLimit = $today->modify('+30 days');
$expiringContracts = array_values(array_filter($contracts, static function (array $contract) use ($today, $expiryLimit): bool {
    if ((string) ($contract['status'] ?? '') !== 'Đang ở' || empty($contract['end_date'])) {
        return false;
    }

    $endDate = new DateTimeImmutable((string) $contract['end_date']);
    return $endDate >= $today && $endDate <= $expiryLimit;
}));
usort($expiringContracts, static fn (array $a, array $b): int => strcmp((string) $a['end_date'], (string) $b['end_date']));

$lowStudents = StudentRepository::lowScoringStudents(60);
$topDebtRows = array_slice($debtRows, 0, 6);
$topUnpaidBills = array_slice($unpaidBills, 0, 6);

$debtByRoomNumber = [];
foreach ($debtRows as $row) {
    $roomNumber = (string) ($row['room_number'] ?? '');
    if ($roomNumber === '') {
        continue;
    }
    $debtByRoomNumber[$roomNumber] = ($debtByRoomNumber[$roomNumber] ?? 0) + (float) ($row['debt'] ?? 0);
}

$roomAttention = [];
foreach ($rooms as $room) {
    $roomNumber = (string) ($room['room_number'] ?? '');
    $capacityRoom = max(0, (int) ($room['capacity'] ?? 0));
    $occupiedRoom = max(0, (int) ($room['occupied_count'] ?? 0));
    $ratio = $capacityRoom > 0 ? $occupiedRoom / $capacityRoom : 0;
    $avgScore = (float) ($room['avg_boarding_score'] ?? 0);
    $status = (string) ($room['status'] ?? '');
    $debt = $debtByRoomNumber[$roomNumber] ?? 0;
    $signals = [];
    $severity = 0;

    if ($status === 'Đang sửa chữa') {
        $signals[] = 'Bảo trì';
        $severity += 30;
    }
    if ($status === 'Hoạt động' && $capacityRoom > 0 && $occupiedRoom >= $capacityRoom) {
        $signals[] = 'Đã đầy';
        $severity += 25;
    } elseif ($status === 'Hoạt động' && $ratio >= 0.85) {
        $signals[] = 'Gần đầy';
        $severity += 15;
    }
    if ($status === 'Hoạt động' && $occupiedRoom === 0) {
        $signals[] = 'Chưa khai thác';
        $severity += 10;
    }
    if ($avgScore > 0 && $avgScore < 60) {
        $signals[] = 'Điểm thấp';
        $severity += 25;
    }
    if ($debt > 0) {
        $signals[] = 'Có công nợ';
        $severity += min(30, (int) floor($debt / 100000));
    }

    if ($severity > 0) {
        $roomAttention[] = [
            'room_id' => (int) $room['room_id'],
            'room_number' => $roomNumber,
            'floor_number' => (int) ($room['floor_number'] ?? 0),
            'occupancy' => $occupiedRoom . '/' . $capacityRoom,
            'avg_score' => $avgScore,
            'debt' => $debt,
            'signals' => implode(', ', $signals),
            'severity' => $severity,
        ];
    }
}
usort($roomAttention, static fn (array $a, array $b): int => $b['severity'] <=> $a['severity']);
$roomAttention = array_slice($roomAttention, 0, 8);

$floorRows = Database::connection()->query("
    SELECT r.floor_number,
           COUNT(r.room_id) AS rooms,
           COALESCE(SUM(r.capacity), 0) AS capacity,
           COUNT(c.contract_id) AS occupied
      FROM Room r
 LEFT JOIN Contract c ON c.room_id = r.room_id AND c.status = 'Đang ở'
  GROUP BY r.floor_number
  ORDER BY r.floor_number ASC
")->fetchAll();

$billTrend = Database::connection()->query("
    SELECT billing_year,
           billing_month,
           COALESCE(SUM(total_amount), 0) AS total_amount,
           COALESCE(SUM(CASE WHEN status = 'Đã thanh toán' THEN total_amount ELSE 0 END), 0) AS paid_amount,
           COALESCE(SUM(CASE WHEN status <> 'Đã thanh toán' THEN total_amount ELSE 0 END), 0) AS unpaid_amount
      FROM UtilityBill
  GROUP BY billing_year, billing_month
  ORDER BY billing_year DESC, billing_month DESC
     LIMIT 6
")->fetchAll();
$billTrend = array_reverse($billTrend);

$noticeMix = Database::connection()->query("
    SELECT category,
           COUNT(*) AS total,
           COALESCE(SUM(point_change), 0) AS point_total
      FROM Notice
  GROUP BY category
  ORDER BY total DESC
")->fetchAll();

$currency = static fn (float $amount): string => number_format($amount, 0, ',', '.') . ' đ';
$percent = static fn (float $value): string => number_format($value, 1, ',', '.') . '%';

require_once __DIR__ . '/../../views/partials/admin_header.php';
?>
<div class="analysis-page">
    <section class="analysis-header">
        <div>
            <div class="section-subtitle text-uppercase fw-semibold small">Operational analytics</div>
            <h2 class="section-title mb-1">Tình hình ký túc xá</h2>
            <div class="analysis-muted">Cập nhật <?= date('d/m/Y H:i'); ?></div>
        </div>
        <div class="analysis-summary">
            <span><?= Security::e((string) count($rooms)); ?> phòng</span>
            <span><?= Security::e((string) count($avgLivingScoreRows)); ?> sinh viên đang ở</span>
            <span><?= Security::e((string) count($contracts)); ?> hợp đồng</span>
        </div>
    </section>

    <section class="analysis-metrics" aria-label="Tổng quan">
        <div class="analysis-metric">
            <span class="metric-label">Lấp đầy</span>
            <strong><?= Security::e($percent($occupancyRate)); ?></strong>
            <span><?= Security::e((string) $occupied); ?>/<?= Security::e((string) $capacity); ?> chỗ</span>
        </div>
        <div class="analysis-metric">
            <span class="metric-label">Công nợ phòng</span>
            <strong><?= Security::e($currency($totalDebt)); ?></strong>
            <span><?= Security::e((string) count($debtRows)); ?> sinh viên còn nợ</span>
        </div>
        <div class="analysis-metric">
            <span class="metric-label">Thu hóa đơn</span>
            <strong><?= Security::e($percent($collectionRate)); ?></strong>
            <span>Chưa thu <?= Security::e($currency($unpaidBillTotal)); ?></span>
        </div>
        <div class="analysis-metric">
            <span class="metric-label">Sắp hết hạn</span>
            <strong><?= Security::e((string) count($expiringContracts)); ?></strong>
            <span>Trong 30 ngày</span>
        </div>
        <div class="analysis-metric">
            <span class="metric-label">Điểm nội trú TB</span>
            <strong><?= Security::e(number_format($avgLivingScore, 1, ',', '.')); ?></strong>
            <span><?= Security::e((string) count($lowStudents)); ?> sinh viên dưới 60</span>
        </div>
    </section>

    <div class="analysis-grid analysis-grid-primary">
        <section class="analysis-section">
            <div class="analysis-section-head">
                <div>
                    <h3>Sức chứa theo tầng</h3>
                    <span>Mức sử dụng thực tế trên từng tầng</span>
                </div>
            </div>
            <div class="analysis-floor-list">
                <?php foreach ($floorRows as $row): ?>
                    <?php
                    $floorCapacity = max(0, (int) $row['capacity']);
                    $floorOccupied = max(0, (int) $row['occupied']);
                    $floorRate = $floorCapacity > 0 ? min(100, round(($floorOccupied / $floorCapacity) * 100, 1)) : 0;
                    ?>
                    <div class="analysis-floor-row">
                        <div>
                            <strong>Tầng <?= Security::e((string) $row['floor_number']); ?></strong>
                            <span><?= Security::e((string) $row['rooms']); ?> phòng</span>
                        </div>
                        <div class="analysis-meter" aria-hidden="true"><span style="width: <?= Security::e((string) $floorRate); ?>%"></span></div>
                        <div class="text-end">
                            <strong><?= Security::e((string) $floorOccupied); ?>/<?= Security::e((string) $floorCapacity); ?></strong>
                            <span><?= Security::e($percent((float) $floorRate)); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="analysis-section">
            <div class="analysis-section-head">
                <div>
                    <h3>Trạng thái phòng</h3>
                    <span>Trống, còn chỗ, đầy và bảo trì</span>
                </div>
            </div>
            <div class="analysis-chart-split">
                <canvas id="roomStatusChart" height="220"></canvas>
                <div class="analysis-legend">
                    <div><span class="legend-dot empty"></span>Trống hoàn toàn <strong><?= Security::e((string) $roomDist['empty']); ?></strong></div>
                    <div><span class="legend-dot occupied"></span>Còn chỗ <strong><?= Security::e((string) $roomDist['occupied']); ?></strong></div>
                    <div><span class="legend-dot full"></span>Đã đầy <strong><?= Security::e((string) $roomDist['full']); ?></strong></div>
                    <div><span class="legend-dot maintenance"></span>Bảo trì <strong><?= Security::e((string) $roomDist['maintenance']); ?></strong></div>
                </div>
            </div>
        </section>
    </div>

    <div class="analysis-grid">
        <section class="analysis-section">
            <div class="analysis-section-head">
                <div>
                    <h3>Dòng tiền hóa đơn</h3>
                    <span>6 kỳ hóa đơn gần nhất</span>
                </div>
                <strong><?= Security::e($currency($totalBillAmount)); ?></strong>
            </div>
            <canvas id="billTrendChart" height="165"></canvas>
        </section>

        <section class="analysis-section">
            <div class="analysis-section-head">
                <div>
                    <h3>Công nợ cần thu</h3>
                    <span>Sinh viên còn nợ tiền phòng</span>
                </div>
                <a href="./contracts.php">Hợp đồng</a>
            </div>
            <div class="analysis-table-wrap">
                <table class="analysis-table">
                    <thead><tr><th>Sinh viên</th><th>Phòng</th><th class="text-end">Còn nợ</th></tr></thead>
                    <tbody>
                    <?php foreach ($topDebtRows as $row): ?>
                        <tr>
                            <td><?= Security::e((string) $row['full_name']); ?><span><?= Security::e((string) $row['student_code']); ?></span></td>
                            <td>P<?= Security::e((string) $row['room_number']); ?></td>
                            <td class="text-end analysis-danger"><?= Security::e($currency((float) $row['debt'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($topDebtRows)): ?>
                        <tr><td colspan="3" class="analysis-muted">Không có công nợ.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="analysis-grid">
        <section class="analysis-section">
            <div class="analysis-section-head">
                <div>
                    <h3>Rủi ro sinh viên</h3>
                    <span>Điểm thấp và hợp đồng sắp hết hạn</span>
                </div>
            </div>
            <div class="analysis-two-col">
                <div class="analysis-table-wrap">
                    <table class="analysis-table">
                        <thead><tr><th>Điểm thấp</th><th>Phòng</th><th class="text-end">Điểm</th></tr></thead>
                        <tbody>
                        <?php foreach (array_slice($lowStudents, 0, 6) as $student): ?>
                            <tr>
                                <td><?= Security::e((string) $student['full_name']); ?><span><?= Security::e((string) $student['student_code']); ?></span></td>
                                <td><?= !empty($student['room_number']) ? 'P' . Security::e((string) $student['room_number']) : '-'; ?></td>
                                <td class="text-end analysis-danger"><?= Security::e((string) $student['boarding_score']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($lowStudents)): ?>
                            <tr><td colspan="3" class="analysis-muted">Không có sinh viên dưới ngưỡng.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="analysis-table-wrap">
                    <table class="analysis-table">
                        <thead><tr><th>Hết hạn</th><th>Phòng</th><th class="text-end">Ngày ra</th></tr></thead>
                        <tbody>
                        <?php foreach (array_slice($expiringContracts, 0, 6) as $contract): ?>
                            <tr>
                                <td><?= Security::e((string) $contract['full_name']); ?><span><?= Security::e((string) $contract['student_code']); ?></span></td>
                                <td>P<?= Security::e((string) $contract['room_number']); ?></td>
                                <td class="text-end"><?= Security::e((string) $contract['end_date']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($expiringContracts)): ?>
                            <tr><td colspan="3" class="analysis-muted">Không có hợp đồng sắp hết hạn.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="analysis-section">
            <div class="analysis-section-head">
                <div>
                    <h3>Phân bổ ưu tiên</h3>
                    <span>Sinh viên đang ở theo mức ưu tiên</span>
                </div>
            </div>
            <canvas id="priorityChart" height="180"></canvas>
        </section>
    </div>

    <div class="analysis-grid">
        <section class="analysis-section">
            <div class="analysis-section-head">
                <div>
                    <h3>Phòng cần chú ý</h3>
                    <span>Tổng hợp theo sức chứa, điểm và công nợ</span>
                </div>
                <a href="./rooms.php">Quản lý phòng</a>
            </div>
            <div class="analysis-table-wrap">
                <table class="analysis-table">
                    <thead><tr><th>Phòng</th><th>Tầng</th><th>Ở</th><th>Điểm TB</th><th class="text-end">Công nợ</th><th>Tín hiệu</th></tr></thead>
                    <tbody>
                    <?php foreach ($roomAttention as $room): ?>
                        <tr>
                            <td><a href="./room.php?id=<?= Security::e((string) $room['room_id']); ?>">P<?= Security::e((string) $room['room_number']); ?></a></td>
                            <td><?= Security::e((string) $room['floor_number']); ?></td>
                            <td><?= Security::e((string) $room['occupancy']); ?></td>
                            <td><?= Security::e(number_format((float) $room['avg_score'], 1, ',', '.')); ?></td>
                            <td class="text-end"><?= Security::e($currency((float) $room['debt'])); ?></td>
                            <td><span class="analysis-tag"><?= Security::e((string) $room['signals']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($roomAttention)): ?>
                        <tr><td colspan="6" class="analysis-muted">Chưa có phòng cần chú ý.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="analysis-section">
            <div class="analysis-section-head">
                <div>
                    <h3>Thông báo và hóa đơn</h3>
                    <span>Điểm nội trú, hóa đơn chưa thu</span>
                </div>
            </div>
            <div class="analysis-two-col compact">
                <div class="analysis-table-wrap">
                    <table class="analysis-table">
                        <thead><tr><th>Thông báo</th><th class="text-end">SL</th><th class="text-end">Điểm</th></tr></thead>
                        <tbody>
                        <?php foreach ($noticeMix as $row): ?>
                            <tr>
                                <td><?= Security::e((string) $row['category']); ?></td>
                                <td class="text-end"><?= Security::e((string) $row['total']); ?></td>
                                <td class="text-end"><?= Security::e((string) $row['point_total']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($noticeMix)): ?>
                            <tr><td colspan="3" class="analysis-muted">Chưa có thông báo.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="analysis-table-wrap">
                    <table class="analysis-table">
                        <thead><tr><th>Hóa đơn chưa thu</th><th class="text-end">Số tiền</th></tr></thead>
                        <tbody>
                        <?php foreach ($topUnpaidBills as $bill): ?>
                            <tr>
                                <td>P<?= Security::e((string) $bill['room_number']); ?><span><?= Security::e((string) $bill['billing_month']); ?>/<?= Security::e((string) $bill['billing_year']); ?></span></td>
                                <td class="text-end analysis-warning"><?= Security::e($currency((float) $bill['total_amount'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($topUnpaidBills)): ?>
                            <tr><td colspan="2" class="analysis-muted">Không có hóa đơn chưa thu.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<?php require_once __DIR__ . '/../../views/partials/admin_footer.php'; ?>

<script>
    (function () {
        const chartDefaults = {
            color: '#64748b',
            font: { family: "'Be Vietnam Pro', sans-serif" }
        };
        Chart.defaults.color = chartDefaults.color;
        Chart.defaults.font = chartDefaults.font;

        new Chart(document.getElementById('roomStatusChart'), {
            type: 'doughnut',
            data: {
                labels: ['Trống', 'Còn chỗ', 'Đầy', 'Bảo trì'],
                datasets: [{
                    data: <?= json_encode(array_values($roomDist), JSON_UNESCAPED_UNICODE); ?>,
                    backgroundColor: ['#14b8a6', '#2563eb', '#e11d48', '#f59e0b'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                cutout: '64%',
                plugins: { legend: { display: false } }
            }
        });

        new Chart(document.getElementById('billTrendChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_map(static fn (array $row): string => $row['billing_month'] . '/' . $row['billing_year'], $billTrend), JSON_UNESCAPED_UNICODE); ?>,
                datasets: [
                    {
                        label: 'Đã thu',
                        data: <?= json_encode(array_map(static fn (array $row): float => (float) $row['paid_amount'], $billTrend)); ?>,
                        backgroundColor: '#16a34a',
                        borderRadius: 4
                    },
                    {
                        label: 'Chưa thu',
                        data: <?= json_encode(array_map(static fn (array $row): float => (float) $row['unpaid_amount'], $billTrend)); ?>,
                        backgroundColor: '#f59e0b',
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    x: { stacked: true, grid: { display: false } },
                    y: { stacked: true, beginAtZero: true, ticks: { callback: (value) => Number(value).toLocaleString('vi-VN') } }
                },
                plugins: { legend: { position: 'bottom' } }
            }
        });

        new Chart(document.getElementById('priorityChart'), {
            type: 'bar',
            data: {
                labels: ['1', '2', '3', '4', '5', '6', '7', '8'],
                datasets: [{
                    label: 'Sinh viên',
                    data: <?= json_encode(array_values($priority)); ?>,
                    backgroundColor: ['#0f766e', '#0d9488', '#2563eb', '#3b82f6', '#7c3aed', '#9333ea', '#f59e0b', '#ea580c'],
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                },
                plugins: { legend: { display: false } }
            }
        });
    })();
</script>
