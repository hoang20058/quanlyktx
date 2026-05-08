<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAdminAuth();

$pageTitle = 'Quản lý phòng - ' . APP_NAME;
$activeMenu = 'rooms';

$rooms = RoomRepository::all();

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

    <table class="table datatable table-striped table-hover align-middle">
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
            <tr>
                <td class="fw-semibold">P<?= Security::e((string) $room['room_number']); ?></td>
                <td><?= Security::e((string) $room['floor_number']); ?></td>
                <td><?= Security::e((string) $room['capacity']); ?></td>
                <td><?= Security::e((string) $room['occupied_count']); ?></td>
                <td><?= Security::e((string) $room['room_type']); ?></td>
                <td><span class="status-pill <?= $room['status'] === 'Hoạt động' ? 'available' : 'maintenance'; ?>"><?= Security::e((string) $room['status']); ?></span></td>
                <td><?= Security::e((string) $room['avg_boarding_score']); ?></td>
                <td>
                    <div class="d-flex gap-2 flex-wrap">
                        <a class="btn btn-sm btn-outline-info" href="room.php?id=<?= Security::e((string) $room['room_id']); ?>">Chi tiết</a>
                        <button class="btn btn-sm btn-outline-primary btn-edit-room"
                                data-bs-toggle="modal"
                                data-bs-target="#roomModal"
                                data-room-id="<?= Security::e((string) $room['room_id']); ?>"
                                data-room-number="<?= Security::e((string) $room['room_number']); ?>"
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
                            <div class="col-md-4"><label class="form-label">Tầng</label><input name="floor_number" class="form-control form-room-input" type="number" min="1" required></div>
                            <div class="col-md-4"><label class="form-label">Số phòng (1-99)</label><input name="room_sequence" class="form-control form-room-input" type="number" min="1" max="99" required></div>
                            <div class="col-md-4"><label class="form-label">Mã phòng (Tự động)</label><input id="room_number_display" class="form-control" type="text" readonly value="P000"></div>
                            <div class="col-md-4"><label class="form-label">Sức chứa</label><input name="capacity" class="form-control" type="number" required></div>
                            <div class="col-md-4"><label class="form-label">Loại phòng</label><select name="room_type" class="form-select"><option>Dịch vụ</option><option selected>Thường</option></select></div>
                            <div class="col-md-4"><label class="form-label">Trạng thái</label><select name="status" class="form-select"><option selected>Hoạt động</option><option>Đang sửa chữa</option></select></div>
                            <div class="col-12"><label class="form-label">Giá phòng</label><input name="price" class="form-control" type="number" step="0.01" required></div>
                        </form>
                    </div>
                    <div class="col-lg-4">
                        <div class="panel-glass rounded-4 p-4 h-100">
                            <div class="fw-semibold mb-2">Chuẩn giao diện CRUD</div>
                            <p class="text-secondary mb-3">Nhập tầng và số phòng, mã phòng sẽ được tự động tính toán. Ví dụ: Tầng 1, Số 03 → P103</p>
                            <div class="alert alert-info border-0 small mb-0">Mã phòng được tạo từ công thức: Tầng × 100 + Số phòng</div>
                        </div>
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

<script>
(function () {
    const updateRoomNumber = () => {
        const floor = parseInt(document.querySelector('[name="floor_number"]')?.value || '0', 10);
        const seq = parseInt(document.querySelector('[name="room_sequence"]')?.value || '0', 10);
        const roomNum = floor * 100 + seq;
        document.getElementById('room_number_display').value = roomNum > 0 ? 'P' + roomNum : 'P000';
    };
    
    document.querySelectorAll('.form-room-input').forEach(el => {
        el.addEventListener('input', updateRoomNumber);
    });
    
    document.querySelectorAll('.btn-edit-room').forEach(btn => {
        btn.addEventListener('click', () => {
            const roomNum = parseInt(btn.dataset.roomNumber, 10) || 0;
            const floor = Math.floor(roomNum / 100);
            const seq = roomNum % 100;
            document.querySelector('[name="floor_number"]').value = floor;
            document.querySelector('[name="room_sequence"]').value = seq;
            updateRoomNumber();
        });
    });
    
    document.getElementById('roomModal')?.addEventListener('show.bs.modal', (e) => {
        if (e.relatedTarget?.getAttribute('data-room-id') === '0') {
            document.getElementById('roomForm').reset();
            document.querySelector('[name="room_id"]').value = '0';
            updateRoomNumber();
        }
    });
})();
</script>
