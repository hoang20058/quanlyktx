<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAdminAuth();

$pageTitle = 'Quản lý phòng - ' . APP_NAME;
$activeMenu = 'rooms';

$rooms = RoomRepository::all();
$roomFloors = array_values(array_unique(array_map(static fn (array $room): int => (int) $room['floor_number'], $rooms)));
sort($roomFloors, SORT_NUMERIC);
$roomTypes = array_values(array_unique(array_filter(array_map(static fn (array $room): string => (string) ($room['room_type'] ?? ''), $rooms))));
sort($roomTypes);
$roomStatuses = array_values(array_unique(array_filter(array_map(static fn (array $room): string => (string) ($room['status'] ?? ''), $rooms))));
sort($roomStatuses);

require_once __DIR__ . '/../../views/partials/admin_header.php';
?>
<div class="table-panel p-4">
    <div class="datatable-toolbar mb-3">
        <div>
            <div class="section-subtitle text-uppercase fw-semibold small">CRUD chuẩn hóa</div>
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
                    <option value="<?= Security::e((string) $floor); ?>">Tầng <?= Security::e((string) $floor); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-filter-field">
            <label for="roomFilterType">Loại phòng</label>
            <select id="roomFilterType" class="form-select form-select-sm" data-filter-key="roomType">
                <option value="">Tất cả loại</option>
                <?php foreach ($roomTypes as $type): ?>
                    <option value="<?= Security::e($type); ?>"><?= Security::e($type); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-filter-field">
            <label for="roomFilterStatus">Trạng thái</label>
            <select id="roomFilterStatus" class="form-select form-select-sm" data-filter-key="status">
                <option value="">Tất cả trạng thái</option>
                <?php foreach ($roomStatuses as $status): ?>
                    <option value="<?= Security::e($status); ?>"><?= Security::e($status); ?></option>
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
            ?>
            <tr data-floor="<?= Security::e((string) $room['floor_number']); ?>"
                data-room-type="<?= Security::e((string) $room['room_type']); ?>"
                data-status="<?= Security::e($roomStatus); ?>"
                data-occupancy-state="<?= Security::e($occupancyState); ?>"
                data-score-band="<?= Security::e($scoreBand); ?>">
                <td class="fw-semibold">P<?= Security::e((string) $room['room_number']); ?></td>
                <td><?= Security::e((string) $room['floor_number']); ?></td>
                <td><?= Security::e((string) $room['capacity']); ?></td>
                <td><?= Security::e((string) $room['occupied_count']); ?></td>
                <td><?= Security::e((string) $room['room_type']); ?></td>
                <td><span class="status-pill <?= $isActiveRoom ? 'available' : 'maintenance'; ?>"><?= Security::e((string) $room['status']); ?></span></td>
                <td><?= Security::e((string) $room['avg_boarding_score']); ?></td>
                <td>
                    <div class="d-flex gap-2 flex-wrap">
                        <a class="btn btn-sm btn-outline-info" href="room.php?id=<?= Security::e((string) $room['room_id']); ?>">Chi tiết</a>
                        <button class="btn btn-sm btn-outline-primary btn-edit-room"
                                data-bs-toggle="modal"
                                data-bs-target="#roomModal"
                                data-room-id="<?= Security::e((string) $room['room_id']); ?>"
                                data-room-number="<?= Security::e((string) $room['room_number']); ?>"
                                data-room-sequence="<?= Security::e((string) ((int) $room['room_number'] - ((int) $room['floor_number'] * 100))); ?>"
                                data-floor-number="<?= Security::e((string) $room['floor_number']); ?>"
                                data-capacity="<?= Security::e((string) $room['capacity']); ?>"
                                data-room-type="<?= Security::e((string) $room['room_type']); ?>"
                                data-status="<?= Security::e((string) $room['status']); ?>"
                                              data-price="<?= Security::e((string) $room['price']); ?>">
                            Sửa
                        </button>
                        <button class="btn btn-sm btn-outline-danger btn-delete-room"
                                data-room-id="<?= Security::e((string) $room['room_id']); ?>">
                            Xóa
                        </button>
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
            <div class="modal-header border-0 pb-0">
                <div>
                    <div class="section-subtitle text-uppercase fw-semibold small">Form thêm/sửa</div>
                    <h5 class="modal-title">Cập nhật thông tin phòng</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-3">
                <div class="row g-4">
                    <div class="col-lg-8">
                        <form id="roomForm" class="row g-3">
                            <input type="hidden" name="room_id" value="0">
                            <div class="col-md-4"><label class="form-label">Tầng</label><input name="floor_number" class="form-control form-room-input" type="number" inputmode="numeric" min="1" required></div>
                            <div class="col-md-4"><label class="form-label">Số phòng (1-99)</label><input name="room_sequence" class="form-control form-room-input" type="number" inputmode="numeric" min="1" max="99" required></div>
                            <div class="col-md-4"><label class="form-label">Mã phòng (Tự động)</label><input id="room_number_display" class="form-control" type="text" readonly value="P000"></div>
                            <div class="col-md-4"><label class="form-label">Sức chứa</label><input name="capacity" class="form-control" type="number" required></div>
                            <div class="col-md-4"><label class="form-label">Loại phòng</label><select name="room_type" class="form-select"><option>Dịch vụ</option><option selected>Thường</option></select></div>
                            <div class="col-md-4"><label class="form-label">Trạng thái</label><select name="status" class="form-select"><option selected>Hoạt động</option><option>Đang sửa chữa</option></select></div>
                            <div class="col-12"><label class="form-label">Giá phòng</label><input name="price" class="form-control" type="number" step="0.01" required></div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                <button id="saveRoomBtn" type="button" class="btn btn-primary">Lưu phòng</button>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../views/partials/admin_footer.php'; ?>
