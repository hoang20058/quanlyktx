<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAdminAuth();

function fetchStudents(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT s.student_id, s.full_name, s.student_code, s.dob, s.phone, s.email, s.department,
               s.status, s.priority_level, s.boarding_score,
               r.room_id, r.room_number, r.floor_number,
               c.contract_id, c.start_date, c.end_date, c.status AS contract_status,
               CASE WHEN c.contract_id IS NOT NULL THEN 'Đang ở' ELSE s.status END AS display_status
          FROM Student s
     LEFT JOIN Contract c ON c.student_id = s.student_id AND c.status = 'Đang ở'
     LEFT JOIN Room r ON r.room_id = c.room_id
      ORDER BY s.student_id DESC
    ");

    return $stmt->fetchAll();
}

function fetchStudentRooms(PDO $pdo): array
{
    $stmt = $pdo->query('
        SELECT room_id, room_number, floor_number, capacity, room_type, status
          FROM Room
      ORDER BY room_number ASC
    ');

    return $stmt->fetchAll();
}

function fetchStudentById(PDO $pdo, int $studentId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM Student WHERE student_id = :student_id LIMIT 1');
    $stmt->execute([':student_id' => $studentId]);
    $student = $stmt->fetch();

    return $student ?: null;
}

function fetchRoomById(PDO $pdo, int $roomId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM Room WHERE room_id = :room_id LIMIT 1');
    $stmt->execute([':room_id' => $roomId]);
    $room = $stmt->fetch();

    return $room ?: null;
}

function currentStudentContract(PDO $pdo, int $studentId): ?array
{
    $stmt = $pdo->prepare("
        SELECT *
          FROM Contract
         WHERE student_id = :student_id AND status = 'Đang ở'
      ORDER BY contract_id DESC
         LIMIT 1
    ");
    $stmt->execute([':student_id' => $studentId]);
    $contract = $stmt->fetch();

    return $contract ?: null;
}

function countRoomOccupancy(PDO $pdo, int $roomId): int
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Contract WHERE room_id = :room_id AND status = 'Đang ở'");
    $stmt->execute([':room_id' => $roomId]);

    return (int) $stmt->fetchColumn();
}

function nullableStudentText(mixed $value): ?string
{
    $value = is_string($value) ? trim($value) : '';
    return $value === '' ? null : $value;
}

function validateStudentPayload(PDO $pdo, array $input, int $studentId): void
{
    $email = nullableStudentText($input['email'] ?? null);
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Email không hợp lệ.');
    }

    $studentCode = nullableStudentText($input['student_code'] ?? null);
    if (!$studentCode) {
        return;
    }

    $sql = $studentId > 0
        ? 'SELECT COUNT(*) FROM Student WHERE student_code = :student_code AND student_id <> :student_id'
        : 'SELECT COUNT(*) FROM Student WHERE student_code = :student_code';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':student_code', $studentCode);
    if ($studentId > 0) {
        $stmt->bindValue(':student_id', $studentId, PDO::PARAM_INT);
    }
    $stmt->execute();

    if ((int) $stmt->fetchColumn() > 0) {
        throw new InvalidArgumentException('Mã sinh viên đã tồn tại.');
    }
}

function handleSaveStudent(PDO $pdo, array $input): void
{
    $studentId = (int) ($input['student_id'] ?? 0);
    $fullName = trim((string) ($input['full_name'] ?? ''));

    if ($fullName === '') {
        throw new InvalidArgumentException('Vui lòng nhập họ tên sinh viên.');
    }

    validateStudentPayload($pdo, $input, $studentId);

    $payload = [
        ':full_name' => $fullName,
        ':student_code' => nullableStudentText($input['student_code'] ?? null),
        ':dob' => nullableStudentText($input['dob'] ?? null),
        ':phone' => nullableStudentText($input['phone'] ?? null),
        ':email' => nullableStudentText($input['email'] ?? null),
        ':department' => nullableStudentText($input['department'] ?? null),
        ':status' => (string) ($input['status'] ?? 'Chờ duyệt'),
        ':priority_level' => max(1, min(8, (int) ($input['priority_level'] ?? 8))),
        ':boarding_score' => max(0, (int) ($input['boarding_score'] ?? 100)),
    ];

    if ($studentId > 0) {
        $payload[':student_id'] = $studentId;
        $stmt = $pdo->prepare('
            UPDATE Student
               SET full_name = :full_name,
                   student_code = :student_code,
                   dob = :dob,
                   phone = :phone,
                   email = :email,
                   department = :department,
                   status = :status,
                   priority_level = :priority_level,
                   boarding_score = :boarding_score
             WHERE student_id = :student_id
        ');
        $stmt->execute($payload);
        return;
    }

    $stmt = $pdo->prepare('
        INSERT INTO Student
            (full_name, student_code, dob, phone, email, department, status, priority_level, boarding_score)
        VALUES
            (:full_name, :student_code, :dob, :phone, :email, :department, :status, :priority_level, :boarding_score)
    ');
    $stmt->execute($payload);
}

function handleDeleteStudent(PDO $pdo, int $studentId): void
{
    if ($studentId <= 0) {
        throw new InvalidArgumentException('Sinh viên không hợp lệ.');
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('DELETE FROM Student WHERE student_id = :student_id');
        $stmt->execute([':student_id' => $studentId]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function handleApproveStudent(PDO $pdo, int $studentId, int $roomId): void
{
    $student = fetchStudentById($pdo, $studentId);
    $room = fetchRoomById($pdo, $roomId);

    if (!$student || !$room) {
        throw new InvalidArgumentException('Sinh viên hoặc phòng không tồn tại.');
    }

    if (countRoomOccupancy($pdo, $roomId) >= (int) $room['capacity']) {
        throw new RuntimeException('Phòng đã đầy, vui lòng chọn phòng khác.');
    }

    $pdo->beginTransaction();
    try {
        $startDate = new DateTimeImmutable('today');
        $endDate = $startDate->modify('+5 months');

        $stmt = $pdo->prepare("UPDATE Student SET status = 'Đang ở' WHERE student_id = :student_id");
        $stmt->execute([':student_id' => $studentId]);

        $stmt = $pdo->prepare("
            INSERT INTO Contract (student_id, room_id, start_date, end_date, status)
            VALUES (:student_id, :room_id, :start_date, :end_date, 'Đang ở')
        ");
        $stmt->execute([
            ':student_id' => $studentId,
            ':room_id' => $roomId,
            ':start_date' => $startDate->format('Y-m-d'),
            ':end_date' => $endDate->format('Y-m-d'),
        ]);

        $stmt = $pdo->prepare("
            INSERT INTO Notice (target_type, category, point_change, room_id, student_id, description, date)
            VALUES ('Phòng', 'Khen thưởng', 0, :room_id, :student_id, :description, CURDATE())
        ");
        $stmt->execute([
            ':room_id' => $roomId,
            ':student_id' => $studentId,
            ':description' => sprintf('Sinh viên %s đã được phân vào phòng %s.', $student['full_name'], $room['room_number']),
        ]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function handleRejectStudent(PDO $pdo, int $studentId): void
{
    $stmt = $pdo->prepare("UPDATE Student SET status = 'Đã từ chối' WHERE student_id = :student_id AND status = 'Chờ duyệt'");
    $stmt->execute([':student_id' => $studentId]);

    if ($stmt->rowCount() === 0) {
        throw new RuntimeException('Chỉ có thể từ chối hồ sơ đang chờ duyệt.');
    }
}

function handleSwitchStudentRoom(PDO $pdo, int $studentId, int $newRoomId): void
{
    $room = fetchRoomById($pdo, $newRoomId);
    if (!$room) {
        throw new InvalidArgumentException('Phòng không tồn tại.');
    }

    $current = currentStudentContract($pdo, $studentId);
    if ($current && (int) $current['room_id'] === $newRoomId) {
        return;
    }

    if (countRoomOccupancy($pdo, $newRoomId) >= (int) $room['capacity']) {
        throw new RuntimeException('Phòng đã đầy, không thể chuyển sinh viên.');
    }

    $pdo->beginTransaction();
    try {
        if ($current) {
            $stmt = $pdo->prepare('UPDATE Contract SET room_id = :room_id WHERE contract_id = :contract_id');
            $stmt->execute([
                ':room_id' => $newRoomId,
                ':contract_id' => (int) $current['contract_id'],
            ]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO Contract (student_id, room_id, start_date, status)
                VALUES (:student_id, :room_id, CURDATE(), 'Đang ở')
            ");
            $stmt->execute([
                ':student_id' => $studentId,
                ':room_id' => $newRoomId,
            ]);
        }

        $stmt = $pdo->prepare("UPDATE Student SET status = 'Đang ở' WHERE student_id = :student_id");
        $stmt->execute([':student_id' => $studentId]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

$pdo = Database::connection();
$pageTitle = 'Quản lý sinh viên - ' . APP_NAME;
$activeMenu = 'students';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'save') {
            handleSaveStudent($pdo, $_POST);
            setFlashMessage('success', 'Lưu sinh viên thành công.');
            redirectTo(APP_URL . '/admin/students.php');
        }

        if ($action === 'delete') {
            handleDeleteStudent($pdo, (int) ($_POST['student_id'] ?? 0));
            setFlashMessage('success', 'Xóa sinh viên thành công.');
            redirectTo(APP_URL . '/admin/students.php');
        }

        if ($action === 'approve') {
            handleApproveStudent($pdo, (int) ($_POST['student_id'] ?? 0), (int) ($_POST['room_id'] ?? 0));
            setFlashMessage('success', 'Duyệt hồ sơ sinh viên thành công.');
            redirectTo(APP_URL . '/admin/students.php');
        }

        if ($action === 'reject') {
            handleRejectStudent($pdo, (int) ($_POST['student_id'] ?? 0));
            setFlashMessage('success', 'Từ chối hồ sơ thành công.');
            redirectTo(APP_URL . '/admin/students.php');
        }

        if ($action === 'switch_room') {
            handleSwitchStudentRoom($pdo, (int) ($_POST['student_id'] ?? 0), (int) ($_POST['new_room_id'] ?? 0));
            setFlashMessage('success', 'Chuyển phòng thành công.');
            redirectTo(APP_URL . '/admin/students.php');
        }

        throw new InvalidArgumentException('Thao tác không hợp lệ.');
    } catch (Throwable $e) {
        setFlashMessage('danger', $e->getMessage());
        redirectTo(APP_URL . '/admin/students.php');
    }
}

$students = fetchStudents($pdo);
$rooms = fetchStudentRooms($pdo);
$livingStudents = array_values(array_filter($students, static fn (array $student): bool => (($student['display_status'] ?? $student['status'] ?? '') === 'Đang ở')));
$pendingStudents = array_values(array_filter($students, static fn (array $student): bool => (($student['display_status'] ?? $student['status'] ?? '') === 'Chờ duyệt')));
$livingDepartments = array_values(array_unique(array_filter(array_map(static fn (array $student): string => (string) ($student['department'] ?? ''), $livingStudents))));
$livingRooms = array_values(array_unique(array_filter(array_map(static fn (array $student): string => !empty($student['room_number']) ? 'P' . (string) $student['room_number'] : '', $livingStudents))));
$livingPriorities = array_values(array_unique(array_filter(array_map(static fn (array $student): string => (string) ($student['priority_level'] ?? ''), $livingStudents), static fn (string $value): bool => $value !== '')));
$pendingDepartments = array_values(array_unique(array_filter(array_map(static fn (array $student): string => (string) ($student['department'] ?? ''), $pendingStudents))));
$pendingPriorities = array_values(array_unique(array_filter(array_map(static fn (array $student): string => (string) ($student['priority_level'] ?? ''), $pendingStudents), static fn (string $value): bool => $value !== '')));
sort($livingDepartments);
sort($livingRooms);
sort($livingPriorities, SORT_NUMERIC);
sort($pendingDepartments);
sort($pendingPriorities, SORT_NUMERIC);

require_once __DIR__ . '/../../views/partials/admin_header.php';
?>
<div class="table-panel p-4">
    <div class="datatable-toolbar mb-3">
        <div>
            <div class="section-subtitle text-uppercase fw-semibold small">Page Controller</div>
            <h2 class="section-title mb-0">Bảng dữ liệu sinh viên</h2>
        </div>
        <div class="table-actions d-flex gap-2">
            <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#studentModal" data-student-id="0">Thêm sinh viên</button>
        </div>
    </div>

    <h4>Sinh viên đang ở</h4>
    <div class="admin-filter-bar" data-filter-target="livingStudentsTable">
        <div class="admin-filter-field">
            <label for="livingFilterDepartment">Ngành / Khoa</label>
            <select id="livingFilterDepartment" class="form-select form-select-sm" data-filter-key="department">
                <option value="">Tất cả ngành</option>
                <?php foreach ($livingDepartments as $department): ?>
                    <option value="<?= h($department); ?>"><?= h($department); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-filter-field">
            <label for="livingFilterRoom">Phòng</label>
            <select id="livingFilterRoom" class="form-select form-select-sm" data-filter-key="room">
                <option value="">Tất cả phòng</option>
                <?php foreach ($livingRooms as $roomNumber): ?>
                    <option value="<?= h($roomNumber); ?>"><?= h($roomNumber); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-filter-field">
            <label for="livingFilterPriority">Ưu tiên</label>
            <select id="livingFilterPriority" class="form-select form-select-sm" data-filter-key="priority">
                <option value="">Tất cả mức</option>
                <?php foreach ($livingPriorities as $priority): ?>
                    <option value="<?= h($priority); ?>">Mức <?= h($priority); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-filter-field">
            <label for="livingFilterScore">Điểm nội trú</label>
            <select id="livingFilterScore" class="form-select form-select-sm" data-filter-key="scoreBand">
                <option value="">Tất cả điểm</option>
                <option value="high">Tốt (>= 80)</option>
                <option value="medium">Ổn định (60-79)</option>
                <option value="low">Cần theo dõi (&lt; 60)</option>
            </select>
        </div>
        <div class="admin-filter-actions">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-filter-clear>Đặt lại</button>
        </div>
    </div>

    <table id="livingStudentsTable" class="table datatable table-hover align-middle">
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
        <?php foreach ($livingStudents as $row): ?>
            <?php
            $livingRoomNumber = !empty($row['room_number']) ? 'P' . (string) $row['room_number'] : '';
            $studentScore = (int) ($row['boarding_score'] ?? 0);
            $studentScoreBand = $studentScore >= 80 ? 'high' : ($studentScore >= 60 ? 'medium' : 'low');
            ?>
            <tr data-department="<?= h($row['department']); ?>"
                data-room="<?= h($livingRoomNumber); ?>"
                data-priority="<?= h($row['priority_level']); ?>"
                data-score-band="<?= h($studentScoreBand); ?>">
                <td class="fw-semibold"><?= h($row['student_code']); ?></td>
                <td><?= h($row['full_name']); ?></td>
                <td><?= h($row['department']); ?></td>
                <td><?= $row['room_number'] ? 'P' . h($row['room_number']) : '-'; ?></td>
                <td><span class="badge text-bg-light border"><?= h($row['display_status'] ?? $row['status']); ?></span></td>
                <td><?= h($row['boarding_score']); ?></td>
                <td>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-sm btn-outline-primary btn-edit-student"
                                data-bs-toggle="modal"
                                data-bs-target="#studentModal"
                                data-student-id="<?= h($row['student_id']); ?>"
                                data-full-name="<?= h($row['full_name']); ?>"
                                data-student-code="<?= h($row['student_code']); ?>"
                                data-dob="<?= h($row['dob']); ?>"
                                data-phone="<?= h($row['phone']); ?>"
                                data-email="<?= h($row['email']); ?>"
                                data-department="<?= h($row['department']); ?>"
                                data-status="<?= h($row['status']); ?>"
                                data-priority-level="<?= h($row['priority_level']); ?>"
                                data-boarding-score="<?= h($row['boarding_score']); ?>">
                            Sửa
                        </button>
                        <form method="post" onsubmit="return confirm('Bạn chắc chắn muốn xóa sinh viên này?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="student_id" value="<?= h($row['student_id']); ?>">
                            <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                        </form>
                        <button class="btn btn-sm btn-outline-success btn-switch-room"
                                data-bs-toggle="modal"
                                data-bs-target="#switchRoomModal"
                                data-student-id="<?= h($row['student_id']); ?>"
                                data-student-name="<?= h($row['full_name']); ?>"
                                data-current-room-id="<?= h($row['room_id'] ?? 0); ?>">
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
        <div class="admin-filter-bar" data-filter-target="pendingStudentsTable">
            <div class="admin-filter-field">
                <label for="pendingFilterDepartment">Ngành / Khoa</label>
                <select id="pendingFilterDepartment" class="form-select form-select-sm" data-filter-key="department">
                    <option value="">Tất cả ngành</option>
                    <?php foreach ($pendingDepartments as $department): ?>
                        <option value="<?= h($department); ?>"><?= h($department); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="admin-filter-field">
                <label for="pendingFilterPriority">Ưu tiên</label>
                <select id="pendingFilterPriority" class="form-select form-select-sm" data-filter-key="priority">
                    <option value="">Tất cả mức</option>
                    <?php foreach ($pendingPriorities as $priority): ?>
                        <option value="<?= h($priority); ?>">Mức <?= h($priority); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="admin-filter-actions">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-filter-clear>Đặt lại</button>
            </div>
        </div>
        <table id="pendingStudentsTable" class="table datatable table-sm table-hover align-middle">
            <thead><tr><th>Mã SV</th><th>Họ tên</th><th>Ngành</th><th>Ưu tiên</th><th>Thao tác</th></tr></thead>
            <tbody>
            <?php foreach ($pendingStudents as $row): ?>
                <tr data-department="<?= h($row['department']); ?>" data-priority="<?= h($row['priority_level']); ?>">
                    <td class="fw-semibold"><?= h($row['student_code']); ?></td>
                    <td><?= h($row['full_name']); ?></td>
                    <td><?= h($row['department']); ?></td>
                    <td>
                        <?php if ((int) $row['priority_level'] <= 3): ?>
                            <span class="badge bg-danger"><?= h($row['priority_level']); ?></span>
                        <?php elseif ((int) $row['priority_level'] <= 6): ?>
                            <span class="badge bg-warning"><?= h($row['priority_level']); ?></span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><?= h($row['priority_level']); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex gap-2 flex-wrap">
                            <button class="btn btn-sm btn-outline-primary btn-edit-student"
                                    data-bs-toggle="modal"
                                    data-bs-target="#studentModal"
                                    data-student-id="<?= h($row['student_id']); ?>"
                                    data-full-name="<?= h($row['full_name']); ?>"
                                    data-student-code="<?= h($row['student_code']); ?>"
                                    data-dob="<?= h($row['dob']); ?>"
                                    data-phone="<?= h($row['phone']); ?>"
                                    data-email="<?= h($row['email']); ?>"
                                    data-department="<?= h($row['department']); ?>"
                                    data-status="<?= h($row['status']); ?>"
                                    data-priority-level="<?= h($row['priority_level']); ?>"
                                    data-boarding-score="<?= h($row['boarding_score']); ?>">
                                Sửa
                            </button>
                            <button class="btn btn-sm btn-success btn-approve-student"
                                    data-bs-toggle="modal"
                                    data-bs-target="#approveModal"
                                    data-student-id="<?= h($row['student_id']); ?>"
                                    data-student-name="<?= h($row['full_name']); ?>">
                                Duyệt
                            </button>
                            <form method="post" onsubmit="return confirm('Bạn chắc chắn muốn từ chối hồ sơ này?');">
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="student_id" value="<?= h($row['student_id']); ?>">
                                <button class="btn btn-sm btn-danger" type="submit">Từ chối</button>
                            </form>
                        </div>
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
            <form id="studentForm" method="post">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="student_id" value="0">
                <input type="hidden" name="boarding_score" value="100">
                <div class="modal-header border-0 pb-0">
                    <div><div class="section-subtitle text-uppercase fw-semibold small">Form thêm/sửa</div><h5 class="modal-title">Thông tin sinh viên</h5></div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Mã sinh viên</label><input name="student_code" class="form-control" type="text" placeholder="SV001"></div>
                        <div class="col-md-8"><label class="form-label">Họ và tên</label><input name="full_name" class="form-control" type="text" placeholder="Nguyễn Văn An" required></div>
                        <div class="col-md-4"><label class="form-label">Ngày sinh</label><input name="dob" class="form-control" type="date"></div>
                        <div class="col-md-4"><label class="form-label">Số điện thoại</label><input name="phone" class="form-control" type="text" placeholder="09xxxxxxxx"></div>
                        <div class="col-md-4"><label class="form-label">Email</label><input name="email" class="form-control" type="email" placeholder="student@example.com"></div>
                        <div class="col-md-6"><label class="form-label">Ngành / Khoa</label><input name="department" class="form-control" type="text" placeholder="Công nghệ thông tin"></div>
                        <div class="col-md-3"><label class="form-label">Trạng thái</label><select name="status" class="form-select"><option>Chờ duyệt</option><option selected>Đang ở</option><option>Đã chuyển đi</option><option>Đã từ chối</option></select></div>
                        <div class="col-md-3"><label class="form-label">Ưu tiên</label><input name="priority_level" class="form-control" type="number" min="1" max="8" value="8"></div>
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
                                            <button type="button" id="confirmAddScore" class="btn btn-outline-info btn-sm">OK</button>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Trừ điểm</label>
                                        <div class="input-group">
                                            <button type="button" class="btn btn-outline-danger" id="btnSubScore">-</button>
                                            <input type="number" id="subScoreInput" class="form-control text-center" value="0" min="0" max="100">
                                            <button type="button" id="confirmSubScore" class="btn btn-outline-info btn-sm">OK</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu sinh viên</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="switchRoomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="switchRoomForm" method="post">
                <input type="hidden" name="action" value="switch_room">
                <input type="hidden" name="student_id" id="switch_student_id">
                <div class="modal-header"><h5 class="modal-title">Chuyển phòng</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Sinh viên</label><input id="switch_student_name" class="form-control" readonly></div>
                    <div class="mb-3"><label class="form-label">Chọn phòng mới</label>
                        <select id="switch_new_room_id" name="new_room_id" class="form-select">
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?= h($room['room_id']); ?>">P<?= h($room['room_number']); ?> - Tầng <?= h($room['floor_number']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button><button type="submit" class="btn btn-success">Chuyển</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="approveForm" method="post">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="student_id" id="approve_student_id" value="0">
                <div class="modal-header"><h5 class="modal-title">Duyệt hồ sơ sinh viên</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Sinh viên</label><input id="approve_student_name" class="form-control" readonly></div>
                    <div class="mb-3"><label class="form-label">Chọn phòng</label>
                        <select id="approve_room_id" name="room_id" class="form-select">
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?= h($room['room_id']); ?>">P<?= h($room['room_number']); ?> - Tầng <?= h($room['floor_number']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button><button type="submit" class="btn btn-success">Duyệt</button></div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../views/partials/admin_footer.php'; ?>

<script>
(function () {
    const studentForm = document.getElementById('studentForm');
    const studentModal = document.getElementById('studentModal');
    const scoreField = studentForm?.querySelector('[name="boarding_score"]');
    const scoreDisplay = document.getElementById('displayBoardingScore');
    const addScoreInput = document.getElementById('addScoreInput');
    const subScoreInput = document.getElementById('subScoreInput');

    const fillForm = (form, data) => {
        Object.entries(data).forEach(([key, value]) => {
            const field = form?.querySelector(`[name="${key}"]`);
            if (field) field.value = value ?? '';
        });
    };

    const updateScoreDisplay = () => {
        const score = Math.max(0, parseInt(scoreField?.value || '100', 10) || 100);
        if (scoreField) scoreField.value = String(score);
        if (!scoreDisplay) return;
        scoreDisplay.textContent = String(score);
        scoreDisplay.style.color = score >= 80 ? '#198754' : (score >= 60 ? '#0d6efd' : (score >= 40 ? '#ff9800' : '#dc3545'));
    };

    document.querySelectorAll('.btn-edit-student').forEach((button) => {
        button.addEventListener('click', () => {
            fillForm(studentForm, {
                student_id: button.dataset.studentId,
                student_code: button.dataset.studentCode,
                full_name: button.dataset.fullName,
                dob: button.dataset.dob,
                phone: button.dataset.phone,
                email: button.dataset.email,
                department: button.dataset.department,
                status: button.dataset.status,
                priority_level: button.dataset.priorityLevel,
                boarding_score: button.dataset.boardingScore
            });
            addScoreInput.value = '0';
            subScoreInput.value = '0';
            updateScoreDisplay();
        });
    });

    studentModal?.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;
        if (trigger && trigger.getAttribute('data-student-id') === '0') {
            studentForm?.reset();
            fillForm(studentForm, { student_id: '0', boarding_score: '100', priority_level: '8', status: 'Đang ở' });
        }
        addScoreInput.value = '0';
        subScoreInput.value = '0';
        updateScoreDisplay();
    });

    document.getElementById('btnAddScore')?.addEventListener('click', () => {
        addScoreInput.value = String((parseInt(addScoreInput.value || '0', 10) || 0) + 1);
    });

    document.getElementById('btnSubScore')?.addEventListener('click', () => {
        subScoreInput.value = String(Math.max(0, (parseInt(subScoreInput.value || '0', 10) || 0) - 1));
    });

    document.getElementById('confirmAddScore')?.addEventListener('click', () => {
        const current = parseInt(scoreField?.value || '100', 10) || 100;
        const add = parseInt(addScoreInput.value || '0', 10) || 0;
        scoreField.value = String(current + Math.max(0, add));
        addScoreInput.value = '0';
        updateScoreDisplay();
    });

    document.getElementById('confirmSubScore')?.addEventListener('click', () => {
        const current = parseInt(scoreField?.value || '100', 10) || 100;
        const sub = parseInt(subScoreInput.value || '0', 10) || 0;
        scoreField.value = String(Math.max(0, current - Math.max(0, sub)));
        subScoreInput.value = '0';
        updateScoreDisplay();
    });

    document.querySelectorAll('.btn-switch-room').forEach((button) => {
        button.addEventListener('click', () => {
            document.getElementById('switch_student_id').value = button.dataset.studentId || '';
            document.getElementById('switch_student_name').value = button.dataset.studentName || '';
            const select = document.getElementById('switch_new_room_id');
            if (select && button.dataset.currentRoomId) select.value = button.dataset.currentRoomId;
        });
    });

    document.querySelectorAll('.btn-approve-student').forEach((button) => {
        button.addEventListener('click', () => {
            document.getElementById('approve_student_id').value = button.dataset.studentId || '0';
            document.getElementById('approve_student_name').value = button.dataset.studentName || '';
        });
    });

    updateScoreDisplay();
})();
</script>
