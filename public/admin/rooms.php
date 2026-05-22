<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAdminAuth();

function fetchRooms(PDO $pdo): array
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

function resolveRoomSequence(array $input, int $floor): int
{
    if (($input['room_sequence'] ?? '') !== '') {
        return (int) $input['room_sequence'];
    }

    $roomNumber = (int) ($input['room_number'] ?? 0);
    if ($roomNumber <= 99) {
        return $roomNumber;
    }

    $numberFloor = intdiv($roomNumber, 100);
    if ($floor > 0 && $numberFloor !== $floor) {
        throw new InvalidArgumentException('Số phòng không khớp với tầng.');
    }

    return $roomNumber % 100;
}

function handleSaveRoom(PDO $pdo, array $input): void
{
    $roomId = (int) ($input['room_id'] ?? 0);
    $floor = (int) ($input['floor_number'] ?? 0);
    $sequence = resolveRoomSequence($input, $floor);
    $roomNumber = ($floor * 100) + $sequence;

    if ($floor < 1 || $sequence < 1 || $sequence > 99) {
        throw new InvalidArgumentException('Tầng hoặc số phòng không hợp lệ.');
    }

    $payload = [
        ':room_number' => $roomNumber,
        ':floor_number' => $floor,
        ':capacity' => max(1, (int) ($input['capacity'] ?? 1)),
        ':room_type' => (string) ($input['room_type'] ?? 'Thường'),
        ':status' => (string) ($input['status'] ?? 'Hoạt động'),
        ':price' => max(0, (float) ($input['price'] ?? 0)),
    ];

    if ($roomId > 0) {
        $payload[':room_id'] = $roomId;
        $stmt = $pdo->prepare('
            UPDATE Room
               SET room_number = :room_number,
                   floor_number = :floor_number,
                   capacity = :capacity,
                   room_type = :room_type,
                   status = :status,
                   price = :price
             WHERE room_id = :room_id
        ');
        $stmt->execute($payload);
        return;
    }

    $stmt = $pdo->prepare('
        INSERT INTO Room (room_number, floor_number, capacity, room_type, status, price)
        VALUES (:room_number, :floor_number, :capacity, :room_type, :status, :price)
    ');
    $stmt->execute($payload);
}

function handleDeleteRoom(PDO $pdo, int $roomId): void
{
    if ($roomId <= 0) {
        throw new InvalidArgumentException('Phòng không hợp lệ.');
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('DELETE FROM Room WHERE room_id = :room_id');
        $stmt->execute([':room_id' => $roomId]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

$pdo = Database::connection();
$pageTitle = 'Quản lý phòng - ' . APP_NAME;
$activeMenu = 'rooms';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'save') {
            handleSaveRoom($pdo, $_POST);
            setFlashMessage('success', 'Lưu phòng thành công.');
            redirectTo(APP_URL . '/admin/rooms.php');
        }

        if ($action === 'delete') {
            handleDeleteRoom($pdo, (int) ($_POST['room_id'] ?? 0));
            setFlashMessage('success', 'Xóa phòng thành công.');
            redirectTo(APP_URL . '/admin/rooms.php');
        }

        throw new InvalidArgumentException('Thao tác không hợp lệ.');
    } catch (Throwable $e) {
        setFlashMessage('danger', $e->getMessage());
        redirectTo(APP_URL . '/admin/rooms.php');
    }
}

$rooms = fetchRooms($pdo);
$roomFloors = array_values(array_unique(array_map(static fn (array $room): int => (int) $room['floor_number'], $rooms)));
$roomTypes = array_values(array_unique(array_filter(array_map(static fn (array $room): string => (string) ($room['room_type'] ?? ''), $rooms))));
$roomStatuses = array_values(array_unique(array_filter(array_map(static fn (array $room): string => (string) ($room['status'] ?? ''), $rooms))));
sort($roomFloors, SORT_NUMERIC);
sort($roomTypes);
sort($roomStatuses);

require_once __DIR__ . '/../../views/partials/admin_header.php';
?>
<div class="table-panel p-4">
    <div class="datatable-toolbar mb-3">
        <div>
            <div class="section-subtitle text-uppercase fw-semibold small">Page Controller</div>
            <h2 class="section-title mb-0">Bảng dữ liệu phòng</h2>
        </div>
        <div class="table-actions d-flex gap-2">
            <button class="btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="#roomModal" data-room-id="0">
                <i class="bi bi-plus-lg me-1"></i>Thêm phòng
            </button>
        </div>
    </div>

    <div class="admin-filter-bar" data-filter-target="roomsTable">
        <div class="admin-filter-field">
            <label for="roomFilterFloor">Tầng</label>
            <select id="roomFilterFloor" class="form-select form-select-sm" data-filter-key="floor">
                <option value="">Tất cả tầng</option>
                <?php foreach ($roomFloors as $floor): ?>
                    <option value="<?= h($floor); ?>">Tầng <?= h($floor); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-filter-field">
            <label for="roomFilterType">Loại phòng</label>
            <select id="roomFilterType" class="form-select form-select-sm" data-filter-key="roomType">
                <option value="">Tất cả loại</option>
                <?php foreach ($roomTypes as $type): ?>
                    <option value="<?= h($type); ?>"><?= h($type); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-filter-field">
            <label for="roomFilterStatus">Trạng thái</label>
            <select id="roomFilterStatus" class="form-select form-select-sm" data-filter-key="status">
                <option value="">Tất cả trạng thái</option>
                <?php foreach ($roomStatuses as $status): ?>
                    <option value="<?= h($status); ?>"><?= h($status); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-filter-field">
            <label for="roomFilterOccupancy">Mức sử dụng</label>
            <select id="roomFilterOccupancy" class="form-select form-select-sm" data-filter-key="occupancyState">
                <option value="">Tất cả mức</option>
                <option value="empty">Còn trống hoàn toàn</option>
                <option value="occupied">Đang có người</option>
                <option value="full">Đã đầy</option>
                <option value="maintenance">Bảo trì</option>
            </select>
        </div>
        <div class="admin-filter-field">
            <label for="roomFilterScore">Điểm TB</label>
            <select id="roomFilterScore" class="form-select form-select-sm" data-filter-key="scoreBand">
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

    <table id="roomsTable" class="table datatable table-striped table-hover align-middle">
        <thead>
        <tr>
            <th>Số phòng</th>
            <th>Tầng</th>
            <th>Sức chứa</th>
            <th>Đang ở</th>
            <th>Loại phòng</th>
            <th>Trạng thái</th>
            <th>Điểm TB</th>
            <th>Thao tác</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($rooms as $room): ?>
            <?php
            $roomStatus = (string) ($room['status'] ?? '');
            $isActiveRoom = $roomStatus === 'Hoạt động';
            $capacity = max(0, (int) ($room['capacity'] ?? 0));
            $occupiedCount = max(0, (int) ($room['occupied_count'] ?? 0));
            if (!$isActiveRoom) {
                $occupancyState = 'maintenance';
            } elseif ($capacity > 0 && $occupiedCount >= $capacity) {
                $occupancyState = 'full';
            } elseif ($occupiedCount > 0) {
                $occupancyState = 'occupied';
            } else {
                $occupancyState = 'empty';
            }
            $avgScore = (float) ($room['avg_boarding_score'] ?? 0);
            $scoreBand = $avgScore >= 80 ? 'high' : ($avgScore >= 60 ? 'medium' : ($avgScore > 0 ? 'low' : 'none'));
            $roomSequence = (int) $room['room_number'] - ((int) $room['floor_number'] * 100);
            ?>
            <tr data-floor="<?= h($room['floor_number']); ?>"
                data-room-type="<?= h($room['room_type']); ?>"
                data-status="<?= h($roomStatus); ?>"
                data-occupancy-state="<?= h($occupancyState); ?>"
                data-score-band="<?= h($scoreBand); ?>">
                <td class="fw-semibold">P<?= h($room['room_number']); ?></td>
                <td><?= h($room['floor_number']); ?></td>
                <td><?= h($room['capacity']); ?></td>
                <td><?= h($room['occupied_count']); ?></td>
                <td><?= h($room['room_type']); ?></td>
                <td><span class="status-pill <?= $isActiveRoom ? 'available' : 'maintenance'; ?>"><?= h($roomStatus); ?></span></td>
                <td><?= h($room['avg_boarding_score']); ?></td>
                <td>
                    <div class="d-flex gap-2 flex-wrap">
                        <a class="btn btn-sm btn-outline-info" href="room.php?id=<?= h($room['room_id']); ?>">Chi tiết</a>
                        <button class="btn btn-sm btn-outline-primary btn-edit-room"
                                data-bs-toggle="modal"
                                data-bs-target="#roomModal"
                                data-room-id="<?= h($room['room_id']); ?>"
                                data-room-number="<?= h($room['room_number']); ?>"
                                data-room-sequence="<?= h($roomSequence); ?>"
                                data-floor-number="<?= h($room['floor_number']); ?>"
                                data-capacity="<?= h($room['capacity']); ?>"
                                data-room-type="<?= h($room['room_type']); ?>"
                                data-status="<?= h($room['status']); ?>"
                                data-price="<?= h($room['price']); ?>">
                            Sửa
                        </button>
                        <form method="post" onsubmit="return confirm('Bạn chắc chắn muốn xóa phòng này?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="room_id" value="<?= h($room['room_id']); ?>">
                            <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="roomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form id="roomForm" method="post">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="room_id" value="0">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <div class="section-subtitle text-uppercase fw-semibold small">Form thêm/sửa</div>
                        <h5 class="modal-title">Cập nhật thông tin phòng</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Tầng</label><input name="floor_number" class="form-control form-room-input" type="number" min="1" required></div>
                        <div class="col-md-4"><label class="form-label">Số phòng (1-99)</label><input name="room_sequence" class="form-control form-room-input" type="number" min="1" max="99" required></div>
                        <div class="col-md-4"><label class="form-label">Mã phòng</label><input id="room_number_display" class="form-control" type="text" readonly value="P000"></div>
                        <div class="col-md-4"><label class="form-label">Sức chứa</label><input name="capacity" class="form-control" type="number" min="1" required></div>
                        <div class="col-md-4"><label class="form-label">Loại phòng</label><select name="room_type" class="form-select"><option>Dịch vụ</option><option selected>Thường</option></select></div>
                        <div class="col-md-4"><label class="form-label">Trạng thái</label><select name="status" class="form-select"><option selected>Hoạt động</option><option>Đang sửa chữa</option></select></div>
                        <div class="col-12"><label class="form-label">Giá phòng</label><input name="price" class="form-control" type="number" step="0.01" min="0" required></div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu phòng</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../views/partials/admin_footer.php'; ?>

<script>
(function () {
    const form = document.getElementById('roomForm');
    const modal = document.getElementById('roomModal');
    const display = document.getElementById('room_number_display');

    const fillForm = (data) => {
        Object.entries(data).forEach(([key, value]) => {
            const field = form?.querySelector(`[name="${key}"]`);
            if (field) field.value = value ?? '';
        });
    };

    const updateRoomNumber = () => {
        const floor = parseInt(form?.querySelector('[name="floor_number"]')?.value || '0', 10);
        const sequence = parseInt(form?.querySelector('[name="room_sequence"]')?.value || '0', 10);
        const roomNumber = floor > 0 && sequence > 0 ? (floor * 100 + sequence) : 0;
        if (display) display.value = roomNumber > 0 ? `P${roomNumber}` : 'P000';
    };

    document.querySelectorAll('.form-room-input').forEach((input) => {
        input.addEventListener('input', updateRoomNumber);
    });

    document.querySelectorAll('.btn-edit-room').forEach((button) => {
        button.addEventListener('click', () => {
            fillForm({
                room_id: button.dataset.roomId,
                floor_number: button.dataset.floorNumber,
                room_sequence: button.dataset.roomSequence,
                capacity: button.dataset.capacity,
                room_type: button.dataset.roomType,
                status: button.dataset.status,
                price: button.dataset.price
            });
            updateRoomNumber();
        });
    });

    modal?.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;
        if (trigger && trigger.getAttribute('data-room-id') === '0') {
            form?.reset();
            fillForm({ room_id: '0' });
        }
        updateRoomNumber();
    });

    updateRoomNumber();
})();
</script>
