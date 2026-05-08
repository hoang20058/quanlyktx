<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAdminAuth();

$pageTitle = 'Quản lý sinh viên - ' . APP_NAME;
$activeMenu = 'students';

$students = StudentRepository::all();
$rooms = RoomRepository::selectOptions();

require_once __DIR__ . '/../../views/partials/admin_header.php';
?>
<div class="table-panel p-4">
    <div class="datatable-toolbar mb-3">
        <div>
            <div class="section-subtitle text-uppercase fw-semibold small">CRUD chuẩn hóa</div>
            <h2 class="section-title mb-0">Bảng dữ liệu sinh viên</h2>
        </div>
        <div class="table-actions d-flex gap-2">
            <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#studentModal" data-student-id="0">Thêm sinh viên</button>
        </div>
    </div>

    <table class="table datatable table-hover align-middle">
        <thead>
        <tr>
            <th>Mã SV</th>
            <th>Họ tên</th>
            <th>Ngành / Khoa</th>
            <th>Phòng</th>
            <th>Trạng thái</th>
            <th>Điểm</th>
            <th>Thao tác</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($students as $row): ?>
            <tr>
                <td class="fw-semibold"><?= Security::e((string) $row['student_code']); ?></td>
                <td><?= Security::e((string) $row['full_name']); ?></td>
                <td><?= Security::e((string) $row['department']); ?></td>
                <td><?= $row['room_number'] ? 'P' . Security::e((string) $row['room_number']) : '-'; ?></td>
                <td><span class="badge text-bg-light border"><?= Security::e((string) ($row['display_status'] ?? $row['status'])); ?></span></td>
                <td><?= Security::e((string) $row['boarding_score']); ?></td>
                <td>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-sm btn-outline-primary btn-edit-student"
                                data-bs-toggle="modal"
                                data-bs-target="#studentModal"
                                data-student-id="<?= Security::e((string) $row['student_id']); ?>"
                                data-full-name="<?= Security::e((string) $row['full_name']); ?>"
                                data-student-code="<?= Security::e((string) $row['student_code']); ?>"
                                data-dob="<?= Security::e((string) $row['dob']); ?>"
                                data-phone="<?= Security::e((string) $row['phone']); ?>"
                                data-email="<?= Security::e((string) $row['email']); ?>"
                                data-department="<?= Security::e((string) $row['department']); ?>"
                                data-status="<?= Security::e((string) $row['status']); ?>"
                                data-priority-level="<?= Security::e((string) $row['priority_level']); ?>"
                                data-boarding-score="<?= Security::e((string) $row['boarding_score']); ?>">
                            Sửa
                        </button>
                        <button class="btn btn-sm btn-outline-danger btn-delete-student" data-student-id="<?= Security::e((string) $row['student_id']); ?>">Xóa</button>
                        <button class="btn btn-sm btn-outline-success btn-switch-room"
                                data-bs-toggle="modal"
                                data-bs-target="#switchRoomModal"
                                data-student-id="<?= Security::e((string) $row['student_id']); ?>"
                                data-student-name="<?= Security::e((string) $row['full_name']); ?>"
                                data-current-room-id="<?= Security::e((string) ($row['room_id'] ?? 0)); ?>">
                            Chuyển phòng
                        </button>
                        <?php if (($row['status'] ?? '') === 'Chờ duyệt'): ?>
                            <button class="btn btn-sm btn-success btn-approve-student"
                                    data-student-id="<?= Security::e((string) $row['student_id']); ?>"
                                    data-student-name="<?= Security::e((string) $row['full_name']); ?>">Duyệt</button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="studentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div><div class="section-subtitle text-uppercase fw-semibold small">Form thêm/sửa</div><h5 class="modal-title">Thông tin sinh viên</h5></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-3">
                <div class="row g-4">
                    <div class="col-lg-8">
                        <form id="studentForm" class="row g-3">
                            <input type="hidden" name="student_id" value="0">
                            <div class="col-md-4"><label class="form-label">Mã sinh viên</label><input name="student_code" class="form-control" type="text" placeholder="SV001"></div>
                            <div class="col-md-8"><label class="form-label">Họ và tên</label><input name="full_name" class="form-control" type="text" placeholder="Nguyễn Văn An" required></div>
                            <div class="col-md-4"><label class="form-label">Ngày sinh</label><input name="dob" class="form-control" type="date"></div>
                            <div class="col-md-4"><label class="form-label">Số điện thoại</label><input name="phone" class="form-control" type="text" placeholder="09xxxxxxxx"></div>
                            <div class="col-md-4"><label class="form-label">Email</label><input name="email" class="form-control" type="email" placeholder="student@example.com"></div>
                            <div class="col-md-6"><label class="form-label">Ngành / Khoa</label><input name="department" class="form-control" type="text" placeholder="Công nghệ thông tin"></div>
                            <div class="col-md-3"><label class="form-label">Trạng thái</label><select name="status" class="form-select"><option>Chờ duyệt</option><option selected>Đang ở</option><option>Đã chuyển đi</option></select></div>
                            <div class="col-md-3"><label class="form-label">Ưu tiên</label><input name="priority_level" class="form-control" type="number" value="8"></div>
                            <div class="col-md-3"><label class="form-label">Điểm nội trú</label><input name="boarding_score" class="form-control" type="number" value="100"></div>
                        </form>
                    </div>
                    <div class="col-lg-4">
                        <div class="panel-glass rounded-4 p-4 h-100">
                            <div class="fw-semibold mb-2">Màn hình chuẩn</div>
                            <p class="text-secondary mb-0">Dữ liệu sinh viên được lưu vào đúng 5 bảng của đề bài và hỗ trợ chuyển phòng.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button><button id="saveStudentBtn" type="button" class="btn btn-primary">Lưu sinh viên</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="switchRoomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Chuyển phòng</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <form id="switchRoomForm">
                    <input type="hidden" name="student_id" id="switch_student_id">
                    <div class="mb-3"><label class="form-label">Sinh viên</label><input id="switch_student_name" class="form-control" readonly></div>
                    <div class="mb-3"><label class="form-label">Chọn phòng mới</label>
                        <select id="switch_new_room_id" name="new_room_id" class="form-select">
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?= Security::e((string) $room['room_id']); ?>">P<?= Security::e((string) $room['room_number']); ?> - Tầng <?= Security::e((string) $room['floor_number']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button><button type="button" id="confirmSwitchRoom" class="btn btn-success">Chuyển</button></div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../views/partials/admin_footer.php'; ?>

<!-- Approve modal -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Duyệt hồ sơ sinh viên</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="approveForm">
                    <input type="hidden" name="student_id" id="approve_student_id" value="0">
                    <div class="mb-3"><label class="form-label">Sinh viên</label><input id="approve_student_name" class="form-control" readonly></div>
                    <div class="mb-3"><label class="form-label">Chọn phòng</label>
                        <select id="approve_room_id" name="room_id" class="form-select">
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?= Security::e((string) $room['room_id']); ?>">P<?= Security::e((string) $room['room_number']); ?> - Tầng <?= Security::e((string) $room['floor_number']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?= Security::csrfField(); ?>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button><button type="button" id="confirmApproveBtn" class="btn btn-success">Duyệt</button></div>
        </div>
    </div>
</div>

<script>
    (function () {
        const approveModalEl = document.getElementById('approveModal');
        const approveModal = approveModalEl ? new bootstrap.Modal(approveModalEl) : null;
        document.querySelectorAll('.btn-approve-student').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-student-id');
                const name = this.getAttribute('data-student-name');
                document.getElementById('approve_student_id').value = id;
                document.getElementById('approve_student_name').value = name;
                if (approveModal) approveModal.show();
            });
        });

        document.getElementById('confirmApproveBtn').addEventListener('click', function () {
            const form = document.getElementById('approveForm');
            const data = new FormData(form);
            fetch('<?= Security::e(APP_BASE_URL); ?>/api/students/approve.php', {
                method: 'POST',
                body: data,
            }).then(r => r.json()).then(function (j) {
                if (j.ok) {
                    location.reload();
                } else {
                    alert('Lỗi: ' + j.message);
                }
            }).catch(function (err) { alert('Lỗi mạng'); });
        });
    })();
</script>
