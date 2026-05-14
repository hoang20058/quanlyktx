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

    <h4>Sinh viên đang ở</h4>
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
        <?php foreach ($students as $row): if (($row['display_status'] ?? $row['status']) !== 'Đang ở') continue; ?>
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
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="mt-5">
        <h4>Hồ sơ chờ duyệt</h4>
        <table class="table table-sm table-hover align-middle">
            <thead><tr><th>Mã SV</th><th>Họ tên</th><th>Ngành</th><th>Ưu tiên</th><th>Thao tác</th></tr></thead>
            <tbody>
            <?php foreach ($students as $row): if (($row['display_status'] ?? $row['status']) !== 'Chờ duyệt') continue; ?>
                <tr>
                    <td class="fw-semibold"><?= Security::e((string) $row['student_code']); ?></td>
                    <td><?= Security::e((string) $row['full_name']); ?></td>
                    <td><?= Security::e((string) $row['department']); ?></td>
                    <td>
                        <span class="badge <?php if ($row['priority_level'] <= 3) { ?>bg-danger<?php } elseif ($row['priority_level'] <= 6) { ?>bg-warning<?php } else { ?>bg-secondary<?php } ?>">
                            <?= Security::e((string) $row['priority_level']); ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-success btn-approve-student" data-student-id="<?= Security::e((string) $row['student_id']); ?>" data-student-name="<?= Security::e((string) $row['full_name']); ?>">Duyệt</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
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
                            <input type="hidden" name="boarding_score" value="100">
                            <div class="col-md-4"><label class="form-label">Mã sinh viên</label><input name="student_code" class="form-control" type="text" placeholder="SV001"></div>
                            <div class="col-md-8"><label class="form-label">Họ và tên</label><input name="full_name" class="form-control" type="text" placeholder="Nguyễn Văn An" required></div>
                            <div class="col-md-4"><label class="form-label">Ngày sinh</label><input name="dob" class="form-control" type="date"></div>
                            <div class="col-md-4"><label class="form-label">Số điện thoại</label><input name="phone" class="form-control" type="text" placeholder="09xxxxxxxx"></div>
                            <div class="col-md-4"><label class="form-label">Email</label><input name="email" class="form-control" type="email" placeholder="student@example.com"></div>
                            <div class="col-md-6"><label class="form-label">Ngành / Khoa</label><input name="department" class="form-control" type="text" placeholder="Công nghệ thông tin"></div>
                            <div class="col-md-3"><label class="form-label">Trạng thái</label><select name="status" class="form-select"><option>Chờ duyệt</option><option selected>Đang ở</option><option>Đã chuyển đi</option></select></div>
                            <div class="col-md-3"><label class="form-label">Ưu tiên</label><input name="priority_level" class="form-control" type="number" value="8"></div>
                            
                            <!-- Phần quản lý điểm nội trú -->
                            <div class="col-12">
                                <div class="p-3 rounded-3" style="background: #f0f7ff; border-left: 4px solid #0d6efd;">
                                    <div class="row align-items-end g-3">
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Điểm nội trú hiện tại</label>
                                            <div style="font-size: 24px; font-weight: bold; color: #0d6efd;" id="displayBoardingScore">100</div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Cộng điểm</label>
                                            <div class="input-group">
                                                <button type="button" class="btn btn-outline-success" id="btnAddScore">+</button>
                                                <input type="number" id="addScoreInput" class="form-control text-center" value="0" min="0" max="100">
                                                <button type="button" id="confirmAddScore" class="btn btn-outline-info btn-sm" title="Thêm điểm">OK</button>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Trừ điểm</label>
                                            <div class="input-group">
                                                <button type="button" class="btn btn-outline-danger" id="btnSubScore">-</button>
                                                <input type="number" id="subScoreInput" class="form-control text-center" value="0" min="0" max="100">
                                                <button type="button" id="confirmSubScore" class="btn btn-outline-info btn-sm" title="Trừ điểm">OK</button>
                                            </div>
                                        </div>
                                    </div>
                                    <small class="text-muted d-block mt-2">💡 Điểm nội trú sẽ được cập nhật khi bạn nhấn OK hoặc lưu</small>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="col-lg-4">
                        <div class="panel-glass rounded-4 p-4 h-100">
                            <div class="fw-semibold mb-2">ℹ️ Hướng dẫn</div>
                            <ul class="small text-secondary mb-0" style="padding-left: 1.5rem;">
                                <li>Điểm nội trú khởi đầu là 100</li>
                                <li>Sử dụng <strong>Cộng</strong> để tăng điểm</li>
                                <li>Sử dụng <strong>Trừ</strong> để giảm điểm</li>
                                <li>Điểm sẽ được lưu khi bạn nhấn "Lưu sinh viên"</li>
                                <li>Giá trị của điểm được tính từ các thao tác cộng/trừ, không được nhập trực tiếp</li>
                            </ul>
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

    // ===== QUẢN LÝ ĐIỂM NỘI TRÚ =====
    (function () {
        const form = document.getElementById('studentForm');
        const boardingScoreField = form.querySelector('input[name="boarding_score"]');
        const displayScore = document.getElementById('displayBoardingScore');
        const addScoreInput = document.getElementById('addScoreInput');
        const subScoreInput = document.getElementById('subScoreInput');
        const addScoreBtn = document.getElementById('confirmAddScore');
        const subScoreBtn = document.getElementById('confirmSubScore');
        
        // Hàm cập nhật hiển thị điểm
        const updateScoreDisplay = () => {
            const current = parseInt(boardingScoreField.value) || 100;
            displayScore.textContent = current;
            // Cập nhật màu sắc
            if (current >= 80) {
                displayScore.style.color = '#198754'; // Xanh
            } else if (current >= 60) {
                displayScore.style.color = '#0d6efd'; // Xanh dương
            } else if (current >= 40) {
                displayScore.style.color = '#ff9800'; // Cam
            } else {
                displayScore.style.color = '#dc3545'; // Đỏ
            }
        };

        // Khôi phục điểm khi mở modal để edit
        const studentModal = document.getElementById('studentModal');
        if (studentModal) {
            studentModal.addEventListener('show.bs.modal', (e) => {
                // Reset input
                addScoreInput.value = 0;
                subScoreInput.value = 0;
                updateScoreDisplay();
            });
        }

        // Nút Cộng điểm
        if (addScoreBtn) {
            addScoreBtn.addEventListener('click', () => {
                const add = parseInt(addScoreInput.value) || 0;
                if (add > 0) {
                    const current = parseInt(boardingScoreField.value) || 100;
                    const newScore = current + add;
                    boardingScoreField.value = newScore;
                    addScoreInput.value = 0;
                    updateScoreDisplay();
                }
            });
        }

        // Nút Trừ điểm
        if (subScoreBtn) {
            subScoreBtn.addEventListener('click', () => {
                const sub = parseInt(subScoreInput.value) || 0;
                if (sub > 0) {
                    const current = parseInt(boardingScoreField.value) || 100;
                    const newScore = Math.max(0, current - sub);
                    boardingScoreField.value = newScore;
                    subScoreInput.value = 0;
                    updateScoreDisplay();
                }
            });
        }

        // Nút +/- cho phép nhập nhanh
        const btnAddScore = document.getElementById('btnAddScore');
        const btnSubScore = document.getElementById('btnSubScore');

        if (btnAddScore) {
            btnAddScore.addEventListener('click', () => {
                const val = parseInt(addScoreInput.value) || 0;
                addScoreInput.value = val + 1;
            });
        }

        if (btnSubScore) {
            btnSubScore.addEventListener('click', () => {
                const val = parseInt(subScoreInput.value) || 0;
                if (val > 0) subScoreInput.value = val - 1;
            });
        }

        // Khôi phục điểm ban đầu khi load modal
        document.querySelectorAll('.btn-edit-student').forEach((btn) => {
            btn.addEventListener('click', () => {
                const score = parseInt(btn.getAttribute('data-boarding-score')) || 100;
                boardingScoreField.value = score;
                setTimeout(updateScoreDisplay, 100);
            });
        });

        // Validation email và student_code trước submit
        document.getElementById('saveStudentBtn').addEventListener('click', async () => {
            const formData = new FormData(form);
            const email = formData.get('email');
            const studentCode = formData.get('student_code');
            const studentId = parseInt(formData.get('student_id')) || 0;

            // Validation email
            if (email && !email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                alert('❌ Email không hợp lệ');
                return;
            }

            // Submit form
            try {
                const resp = await fetch('<?= Security::e(APP_BASE_URL); ?>/api/students/save.php', {
                    method: 'POST',
                    body: formData,
                });
                const json = await resp.json();
                if (json.ok) {
                    location.reload();
                } else {
                    alert('❌ Lỗi: ' + (json.message || 'Không thành công'));
                }
            } catch (err) {
                alert('❌ Lỗi kết nối');
            }
        });
    })();
</script>
