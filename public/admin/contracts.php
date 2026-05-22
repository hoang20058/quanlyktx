<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAdminAuth();

function fetchContracts(PDO $pdo): array
{
    $stmt = $pdo->query('
        SELECT c.contract_id, c.student_id, c.room_id, c.start_date, c.end_date, c.status,
               s.full_name, s.student_code,
               r.room_number
          FROM Contract c
          JOIN Student s ON s.student_id = c.student_id
          JOIN Room r ON r.room_id = c.room_id
      ORDER BY c.contract_id DESC
    ');

    return $stmt->fetchAll();
}

function fetchContractStudents(PDO $pdo): array
{
    $stmt = $pdo->query('
        SELECT student_id, full_name, student_code
          FROM Student
      ORDER BY full_name ASC
    ');

    return $stmt->fetchAll();
}

function fetchContractRooms(PDO $pdo): array
{
    $stmt = $pdo->query('
        SELECT room_id, room_number, floor_number, capacity, room_type, status
          FROM Room
      ORDER BY room_number ASC
    ');

    return $stmt->fetchAll();
}

function fetchContractById(PDO $pdo, int $contractId): ?array
{
    $stmt = $pdo->prepare('
        SELECT contract_id, student_id, room_id, start_date, end_date, status
          FROM Contract
         WHERE contract_id = :contract_id
         LIMIT 1
    ');
    $stmt->execute([':contract_id' => $contractId]);
    $contract = $stmt->fetch();

    return $contract ?: null;
}

function assertStudentAndRoomExist(PDO $pdo, int $studentId, int $roomId): void
{
    $studentStmt = $pdo->prepare('SELECT student_id FROM Student WHERE student_id = :student_id LIMIT 1');
    $studentStmt->execute([':student_id' => $studentId]);

    $roomStmt = $pdo->prepare('SELECT room_id FROM Room WHERE room_id = :room_id LIMIT 1');
    $roomStmt->execute([':room_id' => $roomId]);

    if (!$studentStmt->fetch() || !$roomStmt->fetch()) {
        throw new InvalidArgumentException('Sinh viên hoặc phòng không hợp lệ.');
    }
}

function normalizeContractStatus(string $status): string
{
    $allowedStatuses = ['Đang ở', 'Đã chuyển ra', 'Đã hủy'];

    return in_array($status, $allowedStatuses, true) ? $status : 'Đang ở';
}

function syncStudentStatusByContract(PDO $pdo, int $studentId, string $contractStatus): void
{
    $studentStatus = $contractStatus === 'Đang ở' ? 'Đang ở' : 'Đã chuyển đi';
    $stmt = $pdo->prepare('UPDATE Student SET status = :status WHERE student_id = :student_id');
    $stmt->execute([
        ':status' => $studentStatus,
        ':student_id' => $studentId,
    ]);
}

function handleSaveContract(PDO $pdo, array $input): void
{
    $contractId = (int) ($input['contract_id'] ?? 0);
    $studentId = (int) ($input['student_id'] ?? 0);
    $roomId = (int) ($input['room_id'] ?? 0);
    $startDate = trim((string) ($input['start_date'] ?? ''));
    $endDate = trim((string) ($input['end_date'] ?? ''));
    $status = normalizeContractStatus((string) ($input['status'] ?? 'Đang ở'));

    if ($studentId <= 0 || $roomId <= 0 || $startDate === '' || $endDate === '') {
        throw new InvalidArgumentException('Vui lòng nhập đủ sinh viên, phòng, ngày vào và ngày ra.');
    }

    if (new DateTimeImmutable($endDate) < new DateTimeImmutable($startDate)) {
        throw new InvalidArgumentException('Ngày ra phải lớn hơn hoặc bằng ngày vào.');
    }

    assertStudentAndRoomExist($pdo, $studentId, $roomId);
    $oldContract = $contractId > 0 ? fetchContractById($pdo, $contractId) : null;

    $pdo->beginTransaction();
    try {
        if ($contractId > 0) {
            if (!$oldContract) {
                throw new InvalidArgumentException('Hợp đồng không tồn tại.');
            }

            $stmt = $pdo->prepare('
                UPDATE Contract
                   SET student_id = :student_id,
                       room_id = :room_id,
                       start_date = :start_date,
                       end_date = :end_date,
                       status = :status
                 WHERE contract_id = :contract_id
            ');
            $stmt->execute([
                ':student_id' => $studentId,
                ':room_id' => $roomId,
                ':start_date' => $startDate,
                ':end_date' => $endDate,
                ':status' => $status,
                ':contract_id' => $contractId,
            ]);
        } else {
            $stmt = $pdo->prepare('
                INSERT INTO Contract (student_id, room_id, start_date, end_date, status)
                VALUES (:student_id, :room_id, :start_date, :end_date, :status)
            ');
            $stmt->execute([
                ':student_id' => $studentId,
                ':room_id' => $roomId,
                ':start_date' => $startDate,
                ':end_date' => $endDate,
                ':status' => $status,
            ]);
        }

        syncStudentStatusByContract($pdo, $studentId, $status);

        if ($oldContract && (int) $oldContract['student_id'] !== $studentId) {
            syncStudentStatusByContract($pdo, (int) $oldContract['student_id'], 'Đã chuyển ra');
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function handleDeleteContract(PDO $pdo, int $contractId): void
{
    $contract = fetchContractById($pdo, $contractId);
    if (!$contract) {
        throw new InvalidArgumentException('Hợp đồng không hợp lệ.');
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('DELETE FROM Contract WHERE contract_id = :contract_id');
        $stmt->execute([':contract_id' => $contractId]);

        syncStudentStatusByContract($pdo, (int) $contract['student_id'], 'Đã chuyển ra');
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function handleExtendContract(PDO $pdo, int $contractId, string $newEndDate): void
{
    $contract = fetchContractById($pdo, $contractId);
    if (!$contract || trim($newEndDate) === '') {
        throw new InvalidArgumentException('Vui lòng nhập ngày kết thúc mới.');
    }

    if (new DateTimeImmutable($newEndDate) < new DateTimeImmutable((string) $contract['start_date'])) {
        throw new InvalidArgumentException('Ngày kết thúc mới phải lớn hơn hoặc bằng ngày vào.');
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("UPDATE Contract SET end_date = :end_date, status = 'Đang ở' WHERE contract_id = :contract_id");
        $stmt->execute([
            ':end_date' => $newEndDate,
            ':contract_id' => $contractId,
        ]);

        syncStudentStatusByContract($pdo, (int) $contract['student_id'], 'Đang ở');
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function handleTerminateContract(PDO $pdo, array $input): void
{
    $contractId = (int) ($input['contract_id'] ?? 0);
    $endDate = trim((string) ($input['end_date'] ?? date('Y-m-d')));
    $reason = trim((string) ($input['reason'] ?? 'Kết thúc hợp đồng'));
    $contract = fetchContractById($pdo, $contractId);

    if (!$contract) {
        throw new InvalidArgumentException('Hợp đồng không tồn tại.');
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("UPDATE Contract SET end_date = :end_date, status = 'Đã chuyển ra' WHERE contract_id = :contract_id");
        $stmt->execute([
            ':end_date' => $endDate !== '' ? $endDate : date('Y-m-d'),
            ':contract_id' => $contractId,
        ]);

        syncStudentStatusByContract($pdo, (int) $contract['student_id'], 'Đã chuyển ra');

        if ($reason !== '') {
            $stmt = $pdo->prepare("
                INSERT INTO Notice (target_type, category, point_change, room_id, student_id, description, date)
                VALUES ('Cá nhân', 'Thông báo chung', 0, :room_id, :student_id, :description, CURDATE())
            ");
            $stmt->execute([
                ':room_id' => (int) $contract['room_id'],
                ':student_id' => (int) $contract['student_id'],
                ':description' => $reason,
            ]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

$pdo = Database::connection();
$pageTitle = 'Quản lý hợp đồng - ' . APP_NAME;
$activeMenu = 'contracts';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'save') {
            handleSaveContract($pdo, $_POST);
            setFlashMessage('success', 'Lưu hợp đồng thành công.');
            redirectTo(APP_URL . '/admin/contracts.php');
        }

        if ($action === 'delete') {
            handleDeleteContract($pdo, (int) ($_POST['contract_id'] ?? 0));
            setFlashMessage('success', 'Xóa hợp đồng thành công.');
            redirectTo(APP_URL . '/admin/contracts.php');
        }

        if ($action === 'extend') {
            handleExtendContract($pdo, (int) ($_POST['contract_id'] ?? 0), (string) ($_POST['new_end_date'] ?? ''));
            setFlashMessage('success', 'Gia hạn hợp đồng thành công.');
            redirectTo(APP_URL . '/admin/contracts.php');
        }

        if ($action === 'terminate') {
            handleTerminateContract($pdo, $_POST);
            setFlashMessage('success', 'Kết thúc hợp đồng thành công.');
            redirectTo(APP_URL . '/admin/contracts.php');
        }

        throw new InvalidArgumentException('Thao tác không hợp lệ.');
    } catch (Throwable $e) {
        setFlashMessage('danger', $e->getMessage());
        redirectTo(APP_URL . '/admin/contracts.php');
    }
}

$contracts = fetchContracts($pdo);
$students = fetchContractStudents($pdo);
$rooms = fetchContractRooms($pdo);
$contractStatuses = array_values(array_unique(array_filter(array_map(static fn (array $contract): string => (string) ($contract['status'] ?? ''), $contracts))));
$contractRooms = array_values(array_unique(array_filter(array_map(static fn (array $contract): string => !empty($contract['room_number']) ? 'P' . (string) $contract['room_number'] : '', $contracts))));
sort($contractStatuses);
sort($contractRooms);
$today = new DateTimeImmutable('today');
$contractSoonLimit = $today->modify('+30 days');

require_once __DIR__ . '/../../views/partials/admin_header.php';
?>
<div class="table-panel p-4">
    <div class="datatable-toolbar mb-3">
        <div>
            <div class="section-subtitle text-uppercase fw-semibold small">Page Controller</div>
            <h2 class="section-title mb-0">Bảng dữ liệu hợp đồng</h2>
        </div>
        <div class="table-actions d-flex gap-2">
            <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#contractModal" data-contract-id="0">
                <i class="bi bi-plus-lg me-1"></i>Thêm hợp đồng
            </button>
        </div>
    </div>

    <div class="admin-filter-bar" data-filter-target="contractsTable">
        <div class="admin-filter-field">
            <label for="contractFilterStatus">Trạng thái</label>
            <select id="contractFilterStatus" class="form-select form-select-sm" data-filter-key="status">
                <option value="">Tất cả trạng thái</option>
                <?php foreach ($contractStatuses as $status): ?>
                    <option value="<?= h($status); ?>"><?= h($status); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-filter-field">
            <label for="contractFilterRoom">Phòng</label>
            <select id="contractFilterRoom" class="form-select form-select-sm" data-filter-key="room">
                <option value="">Tất cả phòng</option>
                <?php foreach ($contractRooms as $roomNumber): ?>
                    <option value="<?= h($roomNumber); ?>"><?= h($roomNumber); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-filter-field">
            <label for="contractFilterExpiry">Hạn hợp đồng</label>
            <select id="contractFilterExpiry" class="form-select form-select-sm" data-filter-key="expiryState">
                <option value="">Tất cả hạn</option>
                <option value="open">Không ngày ra</option>
                <option value="active">Còn hạn</option>
                <option value="soon">Sắp hết hạn 30 ngày</option>
                <option value="overdue">Quá hạn</option>
            </select>
        </div>
        <div class="admin-filter-actions">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-filter-clear>Đặt lại</button>
        </div>
    </div>

    <table id="contractsTable" class="table datatable table-hover align-middle">
        <thead>
        <tr>
            <th>Sinh viên</th>
            <th>Mã SV</th>
            <th>Phòng</th>
            <th>Ngày vào</th>
            <th>Ngày ra</th>
            <th>Trạng thái</th>
            <th>Thao tác</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($contracts as $contract): ?>
            <?php
            $contractStatus = (string) ($contract['status'] ?? '');
            $contractRoomNumber = !empty($contract['room_number']) ? 'P' . (string) $contract['room_number'] : '';
            $contractExpiryState = 'open';
            if (!empty($contract['end_date'])) {
                try {
                    $contractEndDate = new DateTimeImmutable((string) $contract['end_date']);
                    if ($contractEndDate < $today) {
                        $contractExpiryState = 'overdue';
                    } elseif ($contractEndDate <= $contractSoonLimit) {
                        $contractExpiryState = 'soon';
                    } else {
                        $contractExpiryState = 'active';
                    }
                } catch (Exception) {
                    $contractExpiryState = 'open';
                }
            }
            ?>
            <tr data-status="<?= h($contractStatus); ?>"
                data-room="<?= h($contractRoomNumber); ?>"
                data-expiry-state="<?= h($contractExpiryState); ?>">
                <td><?= h($contract['full_name']); ?></td>
                <td><?= h($contract['student_code']); ?></td>
                <td><?= h($contractRoomNumber); ?></td>
                <td><?= h($contract['start_date']); ?></td>
                <td><?= $contract['end_date'] ? h($contract['end_date']) : '-'; ?></td>
                <td><span class="badge text-bg-info"><?= h($contractStatus); ?></span></td>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">⋮</button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <button class="dropdown-item btn-edit-contract"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#contractModal"
                                        data-contract-id="<?= h($contract['contract_id']); ?>"
                                        data-student-id="<?= h($contract['student_id']); ?>"
                                        data-room-id="<?= h($contract['room_id']); ?>"
                                        data-start-date="<?= h($contract['start_date']); ?>"
                                        data-end-date="<?= h($contract['end_date']); ?>"
                                        data-status="<?= h($contractStatus); ?>">
                                    Sửa
                                </button>
                            </li>
                            <li><button class="dropdown-item btn-action-extend" type="button" data-bs-toggle="modal" data-bs-target="#extendModal" data-contract-id="<?= h($contract['contract_id']); ?>">Gia hạn</button></li>
                            <li><button class="dropdown-item btn-action-terminate" type="button" data-bs-toggle="modal" data-bs-target="#terminateModal" data-contract-id="<?= h($contract['contract_id']); ?>">Kết thúc</button></li>
                            <li><a class="dropdown-item" href="./contract-detail.php?id=<?= h($contract['contract_id']); ?>">Chi tiết</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="post" onsubmit="return confirm('Bạn chắc chắn muốn xóa hợp đồng này?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="contract_id" value="<?= h($contract['contract_id']); ?>">
                                    <button class="dropdown-item text-danger" type="submit">Xóa</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="contractModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="contractForm" method="post">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="contract_id" value="0">
                <div class="modal-header"><h5 class="modal-title">Thêm/Sửa hợp đồng</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Sinh viên</label>
                            <select name="student_id" class="form-select" required>
                                <option value="">-- Chọn sinh viên --</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?= h($student['student_id']); ?>"><?= h($student['full_name']); ?> (<?= h($student['student_code']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Phòng</label>
                            <select name="room_id" class="form-select" required>
                                <option value="">-- Chọn phòng --</option>
                                <?php foreach ($rooms as $room): ?>
                                    <option value="<?= h($room['room_id']); ?>">
                                        P<?= h($room['room_number']); ?> - <?= h($room['room_type']); ?>, tầng <?= h($room['floor_number']); ?>, sức chứa <?= h($room['capacity']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6"><label class="form-label">Ngày vào</label><input name="start_date" type="date" class="form-control" required></div>
                        <div class="col-6"><label class="form-label">Ngày ra</label><input name="end_date" type="date" class="form-control" required></div>
                        <div class="col-12">
                            <label class="form-label">Trạng thái</label>
                            <select name="status" class="form-select">
                                <option>Đang ở</option>
                                <option>Đã chuyển ra</option>
                                <option>Đã hủy</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button><button type="submit" class="btn btn-primary">Lưu</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="extendModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="extendForm" method="post">
                <input type="hidden" name="action" value="extend">
                <input type="hidden" name="contract_id" value="0">
                <div class="modal-header"><h5 class="modal-title">Gia hạn hợp đồng</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <label class="form-label">Ngày kết thúc mới</label>
                    <input name="new_end_date" type="date" class="form-control" required>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button><button type="submit" class="btn btn-primary">Xác nhận</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="terminateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="terminateForm" method="post">
                <input type="hidden" name="action" value="terminate">
                <input type="hidden" name="contract_id" value="0">
                <div class="modal-header"><h5 class="modal-title">Kết thúc hợp đồng</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Lý do</label>
                            <select name="reason" class="form-select">
                                <option value="Chuyển đi">Chuyển đi</option>
                                <option value="Vi phạm">Vi phạm</option>
                                <option value="Khác">Khác</option>
                            </select>
                        </div>
                        <div class="col-6"><label class="form-label">Ngày kết thúc</label><input name="end_date" type="date" class="form-control" value="<?= h(date('Y-m-d')); ?>"></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button><button type="submit" class="btn btn-danger">Xác nhận kết thúc</button></div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../views/partials/admin_footer.php'; ?>

<script>
(function () {
    const contractForm = document.getElementById('contractForm');

    const fillForm = (form, data) => {
        Object.entries(data).forEach(([key, value]) => {
            const field = form?.querySelector(`[name="${key}"]`);
            if (field) field.value = value ?? '';
        });
    };

    document.querySelectorAll('.btn-edit-contract').forEach((button) => {
        button.addEventListener('click', () => {
            fillForm(contractForm, {
                contract_id: button.dataset.contractId,
                student_id: button.dataset.studentId,
                room_id: button.dataset.roomId,
                start_date: button.dataset.startDate,
                end_date: button.dataset.endDate,
                status: button.dataset.status
            });
        });
    });

    document.getElementById('contractModal')?.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;
        if (trigger && trigger.getAttribute('data-contract-id') === '0') {
            contractForm?.reset();
            fillForm(contractForm, { contract_id: '0', status: 'Đang ở' });
        }
    });

    document.querySelectorAll('.btn-action-extend').forEach((button) => {
        button.addEventListener('click', () => {
            fillForm(document.getElementById('extendForm'), { contract_id: button.dataset.contractId });
        });
    });

    document.querySelectorAll('.btn-action-terminate').forEach((button) => {
        button.addEventListener('click', () => {
            fillForm(document.getElementById('terminateForm'), { contract_id: button.dataset.contractId });
        });
    });
})();
</script>
