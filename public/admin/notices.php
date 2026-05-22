<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAdminAuth();

function fetchNotices(PDO $pdo): array
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

function fetchNoticeRooms(PDO $pdo): array
{
    $stmt = $pdo->query('
        SELECT room_id, room_number, floor_number
          FROM Room
      ORDER BY room_number ASC
    ');

    return $stmt->fetchAll();
}

function fetchNoticeStudents(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT s.student_id, s.full_name, s.student_code, r.room_id, r.room_number
          FROM Student s
     LEFT JOIN Contract c ON c.student_id = s.student_id AND c.status = 'Đang ở'
     LEFT JOIN Room r ON r.room_id = c.room_id
      ORDER BY s.full_name ASC
    ");

    return $stmt->fetchAll();
}

function fetchNoticeById(PDO $pdo, int $noticeId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM Notice WHERE notice_id = :notice_id LIMIT 1');
    $stmt->execute([':notice_id' => $noticeId]);
    $notice = $stmt->fetch();

    return $notice ?: null;
}

function nullableNoticeInt(mixed $value): ?int
{
    if ($value === null || $value === '') {
        return null;
    }

    return (int) $value;
}

function nullableNoticeText(mixed $value): ?string
{
    $value = is_string($value) ? trim($value) : '';
    return $value === '' ? null : $value;
}

function noticeStudentBelongsToRoom(PDO $pdo, int $studentId, int $roomId): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
          FROM Contract
         WHERE student_id = :student_id
           AND room_id = :room_id
           AND status = 'Đang ở'
    ");
    $stmt->execute([
        ':student_id' => $studentId,
        ':room_id' => $roomId,
    ]);

    return (int) $stmt->fetchColumn() > 0;
}

function applyNoticePointChange(PDO $pdo, string $targetType, int $pointChange, ?int $roomId, ?int $studentId): void
{
    if ($pointChange === 0) {
        return;
    }

    if ($targetType === 'Cá nhân' && $studentId) {
        $stmt = $pdo->prepare('UPDATE Student SET boarding_score = boarding_score + :point_change WHERE student_id = :student_id');
        $stmt->execute([
            ':point_change' => $pointChange,
            ':student_id' => $studentId,
        ]);
    }
}

function normalizeNoticePayload(PDO $pdo, array $input): array
{
    $targetType = (string) ($input['target_type'] ?? 'Cả tòa');
    $roomId = nullableNoticeInt($input['room_id'] ?? null);
    $studentId = nullableNoticeInt($input['student_id'] ?? null);
    $pointChange = (int) ($input['point_change'] ?? 0);

    if ($targetType === 'Cả tòa') {
        $roomId = null;
        $studentId = null;
        $pointChange = 0;
    } elseif ($targetType === 'Phòng') {
        if (!$roomId) {
            throw new InvalidArgumentException('Vui lòng chọn phòng.');
        }
        $studentId = null;
        $pointChange = 0;
    } elseif ($targetType === 'Cá nhân') {
        if (!$roomId || !$studentId) {
            throw new InvalidArgumentException('Vui lòng chọn phòng và sinh viên.');
        }

        if (!noticeStudentBelongsToRoom($pdo, $studentId, $roomId)) {
            throw new InvalidArgumentException('Sinh viên không thuộc phòng đã chọn.');
        }
    } else {
        throw new InvalidArgumentException('Đối tượng thông báo không hợp lệ.');
    }

    return [
        'target_type' => $targetType,
        'category' => (string) ($input['category'] ?? 'Thông báo chung'),
        'point_change' => $pointChange,
        'room_id' => $roomId,
        'student_id' => $studentId,
        'description' => nullableNoticeText($input['description'] ?? null),
        'date' => (string) ($input['date'] ?? date('Y-m-d')),
    ];
}

function handleSaveNotice(PDO $pdo, array $input): void
{
    $noticeId = (int) ($input['notice_id'] ?? 0);
    $existing = $noticeId > 0 ? fetchNoticeById($pdo, $noticeId) : null;
    $payload = normalizeNoticePayload($pdo, $input);

    $pdo->beginTransaction();
    try {
        if ($existing) {
            applyNoticePointChange(
                $pdo,
                (string) $existing['target_type'],
                -((int) $existing['point_change']),
                nullableNoticeInt($existing['room_id'] ?? null),
                nullableNoticeInt($existing['student_id'] ?? null)
            );

            $stmt = $pdo->prepare('
                UPDATE Notice
                   SET target_type = :target_type,
                       category = :category,
                       point_change = :point_change,
                       room_id = :room_id,
                       student_id = :student_id,
                       description = :description,
                       date = :date
                 WHERE notice_id = :notice_id
            ');
            $stmt->execute([
                ':target_type' => $payload['target_type'],
                ':category' => $payload['category'],
                ':point_change' => $payload['point_change'],
                ':room_id' => $payload['room_id'],
                ':student_id' => $payload['student_id'],
                ':description' => $payload['description'],
                ':date' => $payload['date'],
                ':notice_id' => $noticeId,
            ]);
        } else {
            $stmt = $pdo->prepare('
                INSERT INTO Notice (target_type, category, point_change, room_id, student_id, description, date)
                VALUES (:target_type, :category, :point_change, :room_id, :student_id, :description, :date)
            ');
            $stmt->execute([
                ':target_type' => $payload['target_type'],
                ':category' => $payload['category'],
                ':point_change' => $payload['point_change'],
                ':room_id' => $payload['room_id'],
                ':student_id' => $payload['student_id'],
                ':description' => $payload['description'],
                ':date' => $payload['date'],
            ]);
        }

        applyNoticePointChange($pdo, $payload['target_type'], $payload['point_change'], $payload['room_id'], $payload['student_id']);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function handleDeleteNotice(PDO $pdo, int $noticeId): void
{
    $notice = fetchNoticeById($pdo, $noticeId);
    if (!$notice) {
        throw new InvalidArgumentException('Thông báo không tồn tại.');
    }

    $pdo->beginTransaction();
    try {
        applyNoticePointChange(
            $pdo,
            (string) $notice['target_type'],
            -((int) $notice['point_change']),
            nullableNoticeInt($notice['room_id'] ?? null),
            nullableNoticeInt($notice['student_id'] ?? null)
        );

        $stmt = $pdo->prepare('DELETE FROM Notice WHERE notice_id = :notice_id');
        $stmt->execute([':notice_id' => $noticeId]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

$pdo = Database::connection();
$pageTitle = 'Quản lý thông báo - ' . APP_NAME;
$activeMenu = 'notices';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'save') {
            handleSaveNotice($pdo, $_POST);
            setFlashMessage('success', 'Lưu thông báo thành công.');
            redirectTo(APP_URL . '/admin/notices.php');
        }

        if ($action === 'delete') {
            handleDeleteNotice($pdo, (int) ($_POST['notice_id'] ?? 0));
            setFlashMessage('success', 'Xóa thông báo thành công.');
            redirectTo(APP_URL . '/admin/notices.php');
        }

        throw new InvalidArgumentException('Thao tác không hợp lệ.');
    } catch (Throwable $e) {
        setFlashMessage('danger', $e->getMessage());
        redirectTo(APP_URL . '/admin/notices.php');
    }
}

$notices = fetchNotices($pdo);
$rooms = fetchNoticeRooms($pdo);
$students = fetchNoticeStudents($pdo);
$noticeCategories = array_values(array_unique(array_filter(array_map(static fn (array $notice): string => (string) ($notice['category'] ?? ''), $notices))));
$noticeTargets = array_values(array_unique(array_filter(array_map(static fn (array $notice): string => (string) ($notice['target_type'] ?? ''), $notices))));
sort($noticeCategories);
sort($noticeTargets);

require_once __DIR__ . '/../../views/partials/admin_header.php';
?>
<div class="table-panel p-4">
    <div class="datatable-toolbar mb-3">
        <div>
            <div class="section-subtitle text-uppercase fw-semibold small">Page Controller</div>
            <h2 class="section-title mb-0">Bảng dữ liệu thông báo</h2>
        </div>
        <div class="table-actions d-flex gap-2">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#noticeModal" data-notice-id="0">Thêm thông báo</button>
        </div>
    </div>

    <div class="admin-filter-bar" data-filter-target="noticesTable">
        <div class="admin-filter-field">
            <label for="noticeFilterCategory">Loại</label>
            <select id="noticeFilterCategory" class="form-select form-select-sm" data-filter-key="category">
                <option value="">Tất cả loại</option>
                <?php foreach ($noticeCategories as $category): ?>
                    <option value="<?= h($category); ?>"><?= h($category); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-filter-field">
            <label for="noticeFilterTarget">Đối tượng</label>
            <select id="noticeFilterTarget" class="form-select form-select-sm" data-filter-key="target">
                <option value="">Tất cả đối tượng</option>
                <?php foreach ($noticeTargets as $target): ?>
                    <option value="<?= h($target); ?>"><?= h($target); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-filter-field">
            <label for="noticeFilterPoint">Ảnh hưởng điểm</label>
            <select id="noticeFilterPoint" class="form-select form-select-sm" data-filter-key="pointState">
                <option value="">Tất cả điểm</option>
                <option value="positive">Cộng điểm</option>
                <option value="negative">Trừ điểm</option>
                <option value="zero">Không đổi điểm</option>
            </select>
        </div>
        <div class="admin-filter-actions">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-filter-clear>Đặt lại</button>
        </div>
    </div>

    <table id="noticesTable" class="table datatable table-hover align-middle">
        <thead>
        <tr>
            <th>Ngày</th>
            <th>Loại</th>
            <th>Đối tượng</th>
            <th>Điểm</th>
            <th>Nội dung</th>
            <th>Thao tác</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($notices as $notice): ?>
            <?php
            $noticePoint = (int) ($notice['point_change'] ?? 0);
            $noticePointState = $noticePoint > 0 ? 'positive' : ($noticePoint < 0 ? 'negative' : 'zero');
            ?>
            <tr data-category="<?= h($notice['category']); ?>"
                data-target="<?= h($notice['target_type']); ?>"
                data-point-state="<?= h($noticePointState); ?>">
                <td><?= h($notice['date']); ?></td>
                <td><?= h($notice['category']); ?></td>
                <td>
                    <div><?= h($notice['target_type']); ?></div>
                    <?php if (!empty($notice['room_number'])): ?>
                        <div class="badge bg-info">Phòng P<?= h($notice['room_number']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($notice['student_name'])): ?>
                        <div class="badge bg-secondary"><?= h($notice['student_name']); ?></div>
                    <?php endif; ?>
                </td>
                <td><?= h($notice['point_change']); ?></td>
                <td><?= h($notice['description']); ?></td>
                <td>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-sm btn-outline-primary btn-edit-notice"
                                data-bs-toggle="modal"
                                data-bs-target="#noticeModal"
                                data-notice-id="<?= h($notice['notice_id']); ?>"
                                data-target-type="<?= h($notice['target_type']); ?>"
                                data-category="<?= h($notice['category']); ?>"
                                data-point-change="<?= h($notice['point_change']); ?>"
                                data-room-id="<?= h($notice['room_id'] ?? ''); ?>"
                                data-student-id="<?= h($notice['student_id'] ?? ''); ?>"
                                data-description="<?= h($notice['description']); ?>"
                                data-date="<?= h($notice['date']); ?>">
                            Sửa
                        </button>
                        <form method="post" onsubmit="return confirm('Bạn chắc chắn muốn xóa thông báo này?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="notice_id" value="<?= h($notice['notice_id']); ?>">
                            <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="noticeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form id="noticeForm" method="post">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="notice_id" value="0">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <div class="section-subtitle text-uppercase fw-semibold small">Form thêm/sửa</div>
                        <h5 class="modal-title">Thông báo / Khen thưởng / Kỷ luật</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Đối tượng</label>
                            <select name="target_type" class="form-select">
                                <option value="Cả tòa" data-mode="building">Cả tòa</option>
                                <option value="Phòng" data-mode="room">Phòng</option>
                                <option value="Cá nhân" data-mode="student">Cá nhân</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Loại</label>
                            <select name="category" class="form-select">
                                <option value="Thông báo chung">Thông báo chung</option>
                                <option value="Khen thưởng">Khen thưởng</option>
                                <option value="Kỷ luật">Kỷ luật</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Điểm thay đổi</label>
                            <input name="point_change" class="form-control" type="number" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phòng</label>
                            <select name="room_id" class="form-select">
                                <option value="">-- Không chọn --</option>
                                <?php foreach ($rooms as $room): ?>
                                    <option value="<?= h($room['room_id']); ?>">P<?= h($room['room_number']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sinh viên</label>
                            <select name="student_id" class="form-select">
                                <option value="">-- Không chọn --</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?= h($student['student_id']); ?>" data-room-id="<?= h($student['room_id'] ?? ''); ?>"><?= h($student['full_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Nội dung</label>
                            <textarea name="description" class="form-control" rows="4"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Ngày</label>
                            <input name="date" class="form-control" type="date" value="<?= h(date('Y-m-d')); ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button><button type="submit" class="btn btn-primary">Lưu thông báo</button></div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../views/partials/admin_footer.php'; ?>

<script>
(function () {
    const form = document.getElementById('noticeForm');
    const modal = document.getElementById('noticeModal');
    const targetInput = form?.querySelector('[name="target_type"]');
    const pointInput = form?.querySelector('[name="point_change"]');
    const roomInput = form?.querySelector('[name="room_id"]');
    const studentInput = form?.querySelector('[name="student_id"]');

    const fillForm = (data) => {
        Object.entries(data).forEach(([key, value]) => {
            const field = form?.querySelector(`[name="${key}"]`);
            if (field) field.value = value ?? '';
        });
    };

    const currentMode = () => targetInput?.selectedOptions?.[0]?.dataset.mode || 'building';

    const filterStudents = (preferredStudentId = '') => {
        const roomId = roomInput?.value || '';
        const selectedStudentId = preferredStudentId || studentInput?.value || '';
        let selectedVisible = false;

        studentInput?.querySelectorAll('option').forEach((option) => {
            if (!option.value) return;
            const matchesRoom = roomId !== '' && option.dataset.roomId === roomId;
            option.hidden = !matchesRoom;
            option.disabled = !matchesRoom;
            option.style.display = matchesRoom ? '' : 'none';
            if (matchesRoom && option.value === selectedStudentId) selectedVisible = true;
        });

        if (studentInput) studentInput.value = selectedVisible ? selectedStudentId : '';
    };

    const syncForm = (preferredStudentId = '') => {
        const mode = currentMode();
        const isBuilding = mode === 'building';
        const isRoom = mode === 'room';
        const isStudent = mode === 'student';

        roomInput.disabled = isBuilding;
        roomInput.required = isRoom || isStudent;
        if (isBuilding) roomInput.value = '';

        pointInput.disabled = !isStudent;
        if (!isStudent) pointInput.value = '0';

        filterStudents(preferredStudentId);

        studentInput.disabled = !isStudent || !roomInput.value;
        studentInput.required = isStudent;
        if (!isStudent || studentInput.disabled) studentInput.value = '';
    };

    targetInput?.addEventListener('change', () => syncForm());
    roomInput?.addEventListener('change', () => syncForm());

    document.querySelectorAll('.btn-edit-notice').forEach((button) => {
        button.addEventListener('click', () => {
            fillForm({
                notice_id: button.dataset.noticeId,
                target_type: button.dataset.targetType,
                category: button.dataset.category,
                point_change: button.dataset.pointChange,
                room_id: button.dataset.roomId,
                student_id: button.dataset.studentId,
                description: button.dataset.description,
                date: button.dataset.date
            });
            syncForm(button.dataset.studentId || '');
        });
    });

    modal?.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;
        if (trigger && trigger.getAttribute('data-notice-id') === '0') {
            form?.reset();
            fillForm({ notice_id: '0', date: '<?= h(date('Y-m-d')); ?>', point_change: '0' });
        }
        syncForm();
    });

    syncForm();
})();
</script>
