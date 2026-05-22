<?php
require_once __DIR__ . '/../includes/functions.php';
startSessionIfNeeded();
requireAdminLogin();

require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Quản lý hợp đồng';
$message = '';
$messageType = '';

$flash = getFlashMessage();
$message = $flash['message'];
$messageType = $flash['type'];


function getViewFilter(): string
{
    $view = $_GET['view'] ?? 'active';
    return in_array($view, ['active', 'old'], true) ? $view : 'active';
}

function fetchRoomInfo(PDO $pdo, int $roomId): ?array
{
    $stmt = $pdo->prepare(
        "SELECT capacity,
            status,
            (SELECT COUNT(*) FROM Contract WHERE room_id = :room_id_count AND status = 'Đã ký') AS current_students
         FROM Room
         WHERE room_id = :room_id"
    );
    $stmt->execute([
        ':room_id' => $roomId,
        ':room_id_count' => $roomId,
    ]);

    return $stmt->fetch() ?: null;
}

function countActiveContractsForStudent(PDO $pdo, int $studentId): int
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM Contract WHERE student_id = :student_id AND status = 'Đã ký'"
    );
    $stmt->execute([':student_id' => $studentId]);
    return (int) $stmt->fetchColumn();
}

function getRoomPrice(PDO $pdo, int $roomId): float
{
    $stmt = $pdo->prepare(
        "SELECT price FROM Contract
         WHERE room_id = :room_id AND status = 'Đã ký'
         ORDER BY contract_id DESC LIMIT 1"
    );
    $stmt->execute([':room_id' => $roomId]);
    return (float) $stmt->fetchColumn();
}

function handleCreateContract(PDO $pdo, array $input, string &$message, string &$messageType): void
{
    $studentId = (int) ($input['student_id'] ?? 0);
    $roomId = (int) ($input['room_id'] ?? 0);
    $startDate = date('Y-m-d'); 
    $endDate = date('Y-m-d', strtotime('+1 year'));
    $price = (float) ($input['price'] ?? 0);
    $deposit = (float) ($input['deposit'] ?? 0);

    if ($studentId <= 0 || $roomId <= 0) {
        $messageType = 'danger';
        $message = 'Vui lòng chọn sinh viên và phòng.';
        return;
    }

    if ($price < 0 || $deposit < 0) {
        $messageType = 'danger';
        $message = 'Giá thuê và tiền cọc phải lớn hơn hoặc bằng 0.';
        return;
    }

    $pdo->beginTransaction();

    $roomInfo = fetchRoomInfo($pdo, $roomId);
    $studentActive = countActiveContractsForStudent($pdo, $studentId);

    if (empty($roomInfo) || $roomInfo['status'] !== 'Hoạt động') {
        $pdo->rollBack();
        $messageType = 'danger';
        $message = 'Phòng đang không hoạt động. Vui lòng chọn phòng khác.';
        return;
    }

    if ((int) $roomInfo['capacity'] <= (int) $roomInfo['current_students']) {
        $pdo->rollBack();
        $messageType = 'danger';
        $message = 'Phòng đã hết chỗ trống. Vui lòng chọn phòng khác.';
        return;
    }

    if ($studentActive > 0) {
        $pdo->rollBack();
        $messageType = 'danger';
        $message = 'Sinh viên đã có hợp đồng đã ký.';
        return;
    }

    $stmt = $pdo->prepare(
        "INSERT INTO Contract (student_id, room_id, start_date, end_date, status, price, deposit)
         VALUES (:student_id, :room_id, :start_date, :end_date, 'Đã ký', :price, :deposit)"
    );
    $stmt->execute([
        ':student_id' => $studentId,
        ':room_id' => $roomId,
        ':start_date' => $startDate,
        ':end_date' => $endDate,
        ':price' => $price,
        ':deposit' => $deposit,
    ]);

    $stmt = $pdo->prepare("UPDATE Student SET status = 'Đang ở' WHERE student_id = :student_id");
    $stmt->execute([':student_id' => $studentId]);

    $pdo->commit();
    redirectWithFlash('contracts.php', 'Tạo hợp đồng mới thành công.');
}

function handleAddStudent(PDO $pdo, array $input, string &$message, string &$messageType): void
{
    $studentId = (int) ($input['student_id'] ?? 0);
    $roomId = (int) ($input['room_id'] ?? 0);
    $deposit = (float) ($input['deposit'] ?? 0);
    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d', strtotime('+1 year'));

    if ($studentId <= 0 || $roomId <= 0) {
        $messageType = 'danger';
        $message = 'Vui lòng chọn sinh viên và phòng.';
        return;
    }

    if ($deposit < 0) {
        $messageType = 'danger';
        $message = 'Tiền cọc phải lớn hơn hoặc bằng 0.';
        return;
    }

    $pdo->beginTransaction();

    $roomInfo = fetchRoomInfo($pdo, $roomId);
    $studentActive = countActiveContractsForStudent($pdo, $studentId);
    $roomPrice = getRoomPrice($pdo, $roomId);

    if (empty($roomInfo) || $roomInfo['status'] !== 'Hoạt động') {
        $pdo->rollBack();
        $messageType = 'danger';
        $message = 'Phòng đang không hoạt động. Vui lòng chọn phòng khác.';
        return;
    }

    if ((int) $roomInfo['capacity'] <= (int) $roomInfo['current_students']) {
        $pdo->rollBack();
        $messageType = 'danger';
        $message = 'Phòng đã hết chỗ trống.';
        return;
    }

    if ($studentActive > 0) {
        $pdo->rollBack();
        $messageType = 'danger';
        $message = 'Sinh viên đã có hợp đồng đã ký.';
        return;
    }

    if ($roomPrice <= 0) {
        $pdo->rollBack();
        $messageType = 'danger';
        $message = 'Chưa có giá phòng áp dụng. Vui lòng tạo hợp đồng có giá trước.';
        return;
    }

    $stmt = $pdo->prepare(
        "INSERT INTO Contract (student_id, room_id, start_date, end_date, status, price, deposit)
         VALUES (:student_id, :room_id, :start_date, :end_date, 'Đã ký', :price, :deposit)"
    );
    $stmt->execute([
        ':student_id' => $studentId,
        ':room_id' => $roomId,
        ':start_date' => $startDate,
        ':end_date' => $endDate,
        ':price' => $roomPrice,
        ':deposit' => $deposit,
    ]);

    $stmt = $pdo->prepare("UPDATE Student SET status = 'Đang ở' WHERE student_id = :student_id");
    $stmt->execute([':student_id' => $studentId]);

    $pdo->commit();
    redirectWithFlash('contracts.php', 'Đã thêm sinh viên vào phòng.');
}


// 1 sv
function handleTerminateContract(PDO $pdo, array $input, string &$message, string &$messageType): void
{
    $contractId = (int) ($input['contract_id'] ?? 0);

    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "SELECT student_id, room_id FROM Contract
         WHERE contract_id = :contract_id AND status = 'Đã ký'"
    );
    $stmt->execute([':contract_id' => $contractId]);
    $contractInfo = $stmt->fetch();

    if (empty($contractInfo)) {
        $pdo->rollBack();
        $messageType = 'danger';
        $message = 'Hợp đồng không còn hiệu lực.';
        return;
    }

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM UtilityBill
         WHERE room_id = :room_id AND status = 'Chưa thanh toán'"
    );
    $stmt->execute([':room_id' => $contractInfo['room_id']]);
    $hasUnpaidBills = (int) $stmt->fetchColumn() > 0;

    if ($hasUnpaidBills) {
        $pdo->rollBack();
        $messageType = 'danger';
        $message = 'Không thể thanh lý khi phòng còn hóa đơn chưa thanh toán.';
        return;
    }

    $stmt = $pdo->prepare(
        "UPDATE Contract
         SET status = 'Kết thúc', end_date = CURDATE()
         WHERE contract_id = :contract_id AND status = 'Đã ký'"
    );
    $stmt->execute([':contract_id' => $contractId]);

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM Contract
         WHERE student_id = :student_id AND status = 'Đã ký'"
    );
    $stmt->execute([':student_id' => $contractInfo['student_id']]);
    $stillActive = (int) $stmt->fetchColumn() > 0;

    if (!$stillActive) {
        $stmt = $pdo->prepare(
            "UPDATE Student SET status = 'Chưa ở' WHERE student_id = :student_id"
        );
        $stmt->execute([':student_id' => $contractInfo['student_id']]);
    }

    $pdo->commit();
    redirectWithFlash('contracts.php', 'Đã thanh lý hợp đồng.');
}

function handleTerminateRoom(PDO $pdo, array $input, string &$message, string &$messageType): void
{
    $roomId = (int) ($input['room_id'] ?? 0);

    if ($roomId <= 0) {
        $messageType = 'danger';
        $message = 'Phòng không hợp lệ.';
        return;
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM UtilityBill
         WHERE room_id = :room_id AND status = 'Chưa thanh toán'"
    );
    $stmt->execute([':room_id' => $roomId]);
    $hasUnpaidBills = (int) $stmt->fetchColumn() > 0;

    if ($hasUnpaidBills) {
        $pdo->rollBack();
        $messageType = 'danger';
        $message = 'Không thể thanh lý khi phòng còn hóa đơn chưa thanh toán.';
        return;
    }

    $stmt = $pdo->prepare(
        "SELECT DISTINCT student_id
         FROM Contract
         WHERE room_id = :room_id AND status = 'Đã ký'"
    );
    $stmt->execute([':room_id' => $roomId]);
    $studentIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $pdo->prepare(
        "UPDATE Contract
         SET status = 'Kết thúc', end_date = CURDATE()
         WHERE room_id = :room_id AND status = 'Đã ký'"
    );
    $stmt->execute([':room_id' => $roomId]);

    foreach ($studentIds as $studentId) {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM Contract
             WHERE student_id = :student_id AND status = 'Đã ký'"
        );
        $stmt->execute([':student_id' => $studentId]);
        $stillActive = (int) $stmt->fetchColumn() > 0;

        if (!$stillActive) {
            $stmt = $pdo->prepare(
                "UPDATE Student SET status = 'Chưa ở' WHERE student_id = :student_id"
            );
            $stmt->execute([':student_id' => $studentId]);
        }
    }

    $pdo->commit();
    redirectWithFlash('contracts.php', 'Đã thanh lý hợp đồng theo phòng.');
}

function handleAdminRenewContract(PDO $pdo, array $input, string &$message, string &$messageType): void
{
    $contractId = (int) ($input['contract_id'] ?? 0);
    $newEndDate = trim($input['new_end_date'] ?? '');
    $newPrice   = isset($input['new_price']) && $input['new_price'] !== '' ? (float) $input['new_price'] : null;

    if (empty($newEndDate)) {
        $messageType = 'danger';
        $message = 'Vui lòng chọn ngày kết thúc mới.';
        return;
    }

    $stmt = $pdo->prepare(
        "SELECT student_id, room_id, price, deposit, end_date
         FROM Contract
         WHERE contract_id = :contract_id AND status = 'Đã ký'"
    );
    $stmt->execute([':contract_id' => $contractId]);
    $contract = $stmt->fetch();

    if (empty($contract)) {
        $messageType = 'danger';
        $message = 'Hợp đồng không hợp lệ hoặc đã kết thúc.';
        return;
    }

    $requestedEndDate = new DateTime($newEndDate);
    $currentEndDate   = $contract['end_date'] ? new DateTime($contract['end_date']) : new DateTime('today');

    if ($requestedEndDate <= $currentEndDate) {
        $messageType = 'danger';
        $message = 'Ngày gia hạn phải sau ngày kết thúc hiện tại.';
        return;
    }

    if ($newPrice !== null && $newPrice < 0) {
        $messageType = 'danger';
        $message = 'Giá thuê mới không được âm.';
        return;
    }

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM UtilityBill WHERE room_id = :room_id AND status = 'Chưa thanh toán'"
    );
    $stmt->execute([':room_id' => $contract['room_id']]);
    if ((int) $stmt->fetchColumn() > 0) {
        $messageType = 'danger';
        $message = 'Phòng còn hóa đơn chưa thanh toán. Vui lòng thanh toán trước khi gia hạn.';
        return;
    }

    $pdo->beginTransaction();

    $priceToSet = $newPrice ?? $contract['price'];
    $stmt = $pdo->prepare(
        "UPDATE Contract
         SET end_date  = :end_date,
             price     = :price
         WHERE contract_id = :contract_id"
    );
    $stmt->execute([
        ':end_date'    => $newEndDate,
        ':price'       => $priceToSet,
        ':contract_id' => $contractId,
    ]);

    // Gửi thông báo cá nhân cho sinh viên
    $newEnd = $requestedEndDate->format('d/m/Y');
    $priceNote = $newPrice !== null ? ' Giá thuê mới: ' . number_format($newPrice, 0, ',', '.') . ' VNĐ.' : '';
    $stmt = $pdo->prepare(
        "INSERT INTO Notice (target_type, student_id, description, date)
         VALUES ('Cá nhân', :student_id, :desc, CURDATE())"
    );
    $stmt->execute([
        ':student_id' => $contract['student_id'],
        ':desc'       => 'Hợp đồng của bạn đã được gia hạn. Ngày kết thúc mới: ' . $newEnd . '.' . $priceNote,
    ]);

    $pdo->commit();
    redirectWithFlash('contracts.php', 'Gia hạn hợp đồng thành công.');
}

function fetchOldContracts(PDO $pdo, string $search): array
{
    $conditions = ["c.status = 'Kết thúc'"];
    $params = [];

    if ($search !== '') {
        $conditions[] = '(s.full_name LIKE ? OR s.student_code LIKE ? OR r.room_number LIKE ?)';
        $keywordValue = '%' . $search . '%';
        $params[] = $keywordValue;
        $params[] = $keywordValue;
        $params[] = $keywordValue;
    }

    $whereClause = 'WHERE ' . implode(' AND ', $conditions);
    $stmt = $pdo->prepare(
        "SELECT c.contract_id,
                c.start_date,
                c.end_date,
                c.price,
                c.deposit,
                c.status,
                s.full_name,
                s.student_code,
                r.room_number
         FROM Contract c
         INNER JOIN Student s ON c.student_id = s.student_id
         LEFT JOIN Room r ON c.room_id = r.room_id
         {$whereClause}
         ORDER BY r.room_number ASC, c.end_date DESC, c.contract_id DESC"
    );
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function fetchAllRoomsWithContractSummary(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT r.room_id,
            r.room_number,
            r.capacity,
            MIN(c.start_date) AS min_start_date,
            MAX(c.price) AS room_price,
            COALESCE(SUM(c.deposit), 0) AS room_deposit,
            COUNT(c.contract_id) AS contract_count
         FROM Room r
         LEFT JOIN Contract c ON r.room_id = c.room_id AND c.status = 'Đã ký'
         GROUP BY r.room_id, r.room_number, r.capacity
         ORDER BY r.room_number ASC"
    );
    return $stmt->fetchAll();
}

function fetchRoomContracts(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT c.contract_id, c.room_id, c.student_id,
            c.start_date, c.end_date, c.deposit, c.price, c.status,
            s.full_name, s.student_code, s.phone, s.email
         FROM Contract c
         INNER JOIN Student s ON c.student_id = s.student_id
         WHERE c.status = 'Đã ký'
         ORDER BY c.room_id, s.full_name"
    );

    $roomContracts = [];
    foreach ($stmt->fetchAll() as $row) {
        $roomId = (int) $row['room_id'];
        if (!isset($roomContracts[$roomId])) {
            $roomContracts[$roomId] = [];
        }
        $roomContracts[$roomId][] = $row;
    }

    return $roomContracts;
}

function fetchAvailableStudents(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT s.student_id, s.full_name, s.student_code
         FROM Student s
         LEFT JOIN Contract c ON s.student_id = c.student_id AND c.status = 'Đã ký'
         WHERE c.contract_id IS NULL
         ORDER BY s.full_name"
    );
    return $stmt->fetchAll();
}

$view = getViewFilter();
$oldSearch = trim($_GET['q'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create') {
            handleCreateContract($pdo, $_POST, $message, $messageType);
        } elseif ($action === 'add_student') {
            handleAddStudent($pdo, $_POST, $message, $messageType);
        } elseif ($action === 'terminate') {
            handleTerminateContract($pdo, $_POST, $message, $messageType);
        } elseif ($action === 'terminate_room') {
            handleTerminateRoom($pdo, $_POST, $message, $messageType);
        } elseif ($action === 'admin_renew') {
            handleAdminRenewContract($pdo, $_POST, $message, $messageType);
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $messageType = 'danger';
        $message = 'Có lỗi xảy ra. Vui lòng thử lại. (' . $e->getMessage() . ')';
    }
}

$roomsWithContracts = [];
$roomContracts      = [];
$oldContracts       = [];

if ($view === 'old') {
    $oldContracts = fetchOldContracts($pdo, $oldSearch);
} else {
    $roomsWithContracts = fetchAllRoomsWithContractSummary($pdo);
    $roomContracts      = fetchRoomContracts($pdo);
}

$availableStudents = fetchAvailableStudents($pdo);


require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1">Quản lý hợp đồng</h1>
            <p class="text-muted mb-0">Theo dõi hợp đồng thuê phòng</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="contracts.php?view=active">Hợp đồng hiện tại</a>
            <a class="btn btn-outline-secondary" href="contracts.php?view=old">Hợp đồng cũ</a>
        </div>
    </div>

    <?php if (!empty($message)) : ?>
        <div class="alert alert-<?php echo $messageType; ?>" role="alert">
            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="card stat-card">
        <div class="card-body">
            <div class="table-responsive">
                <?php if ($view === 'old') : ?>
                    <form method="get" action="" class="row g-2 mb-3">
                        <input type="hidden" name="view" value="old">
                        <div class="col-12 col-md-6 col-lg-4">
                            <input type="text" class="form-control" name="q" placeholder="Tìm theo tên, mã SV, số phòng" value="<?php echo htmlspecialchars($oldSearch, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-12 col-md-auto">
                            <button type="submit" class="btn btn-outline-primary">Tìm kiếm</button>
                            <a href="contracts.php?view=old" class="btn btn-light">Xóa Tìm Kiếm</a>
                        </div>
                    </form>
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center">ID</th>
                                <th>Tên SV (Mã SV)</th>
                                <th class="text-center">Số phòng</th>
                                <th class="text-center">Ngày bắt đầu</th>
                                <th class="text-center">Ngày kết thúc</th>
                                <th class="text-center">Giá thuê</th>
                                <th class="text-center">Tiền cọc</th>
                                <th class="text-center">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($oldContracts)) : ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">Chưa có hợp đồng cũ.</td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ($oldContracts as $contract) : ?>
                                    <tr>
                                        <td class="text-center"><?php echo htmlspecialchars($contract['contract_id'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($contract['full_name'], ENT_QUOTES, 'UTF-8'); ?>
                                            (<?php echo htmlspecialchars($contract['student_code'], ENT_QUOTES, 'UTF-8'); ?>)
                                        </td>
                                        <td class="text-center">
                                            <?php if ($contract['room_number']) : ?>
                                                <?php echo htmlspecialchars($contract['room_number'], ENT_QUOTES, 'UTF-8'); ?>
                                            <?php else : ?>
                                                <span class="text-muted fst-italic">Phòng đã xoá</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center"><?php echo htmlspecialchars(formatDate($contract['start_date']), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars(formatDate($contract['end_date'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars(formatCurrency($contract['price']), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars(formatCurrency($contract['deposit']), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="text-center">
                                            <?php if ($contract['status'] === 'Kết thúc') : ?>
                                                <span class="badge bg-secondary">Kết thúc</span>
                                            <?php else : ?>
                                                <span class="badge bg-danger"><?php echo htmlspecialchars($contract['status'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center">Số phòng</th>
                                <th class="text-center">Sức chứa</th>
                                <th class="text-center">Giá thuê</th>
                                <th class="text-center">Tiền cọc</th>
                                <th class="text-center">Trạng thái</th>
                                <th class="text-center">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($roomsWithContracts)) : ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Chưa có phòng đã ký.</td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ($roomsWithContracts as $room) : ?>
                                    <tr class="room-contract-row" data-bs-target="#roomContracts<?php echo $room['room_id']; ?>">
                                        <td class="text-center"><?php echo htmlspecialchars($room['room_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="text-center fw-bold text-primary"><?php echo (int) $room['contract_count']; ?>/<?php echo (int) $room['capacity']; ?></td>
                                        <td class="text-center"><?php echo $room['room_price'] ? htmlspecialchars(formatCurrency($room['room_price']), ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                                        <td class="text-center"><?php echo $room['room_deposit'] ? htmlspecialchars(formatCurrency($room['room_deposit']), ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                                        <td class="text-center">
                                            <?php if ((int)$room['contract_count'] > 0) : ?>
                                                <span class="badge bg-success">Đã ký</span>
                                            <?php else : ?>
                                                <span class="badge bg-secondary">Chưa ký</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-2">
                                                <?php if ((int)$room['contract_count'] === 0) : ?>
                                                    <button class="btn btn-sm btn-primary btn-create-contract"
                                                        data-room-id="<?php echo (int) $room['room_id']; ?>"
                                                        data-room-number="<?php echo htmlspecialchars($room['room_number'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-bs-toggle="modal" data-bs-target="#addContractModal">Tạo hợp đồng</button>
                                                <?php else : ?>
                                                    <form method="post" action="" class="d-inline" onsubmit="return confirm('Bạn chắc chắn muốn thanh lý toàn bộ hợp đồng của phòng này?');">
                                                        <input type="hidden" name="action" value="terminate_room">
                                                        <input type="hidden" name="room_id" value="<?php echo (int) $room['room_id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Thanh lý</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr class="collapse bg-light" id="roomContracts<?php echo $room['room_id']; ?>">
                                        <td colspan="6">
                                            <div class="p-3">
                                                <?php $roomStudents = $roomContracts[(int) $room['room_id']] ?? []; ?>
                                                <?php if (empty($roomStudents)) : ?>
                                                    <div class="text-muted">Chưa có hợp đồng đã ký.</div>
                                                <?php else : ?>
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-bordered mb-3">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th>Sinh viên</th>
                                                                    <th class="text-center">Ngày bắt đầu</th>
                                                                    <th class="text-center">Ngày kết thúc</th>
                                                                    <th class="text-center">Tiền cọc</th>
                                                                    <th class="text-center">Hành động</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($roomStudents as $roomStudent) : ?>
                                                                    <?php
                                                                        $studentEndDate = $roomStudent['end_date'] ? new DateTime($roomStudent['end_date']) : null;
                                                                        $todayObj = new DateTime('today');
                                                                        $dateBadge = '';
                                                                        $needsRenewal = false;
                                                                        if ($studentEndDate) {
                                                                            $daysLeft = (int) $todayObj->diff($studentEndDate)->format('%r%a');
                                                                            if ($daysLeft < 0) {
                                                                                $dateBadge = '<span class="badge bg-danger ms-2">Đã quá hạn</span>';
                                                                                $needsRenewal = true;
                                                                            } elseif ($daysLeft <= 30) {
                                                                                $dateBadge = '<span class="badge bg-warning text-dark ms-2">Sắp hết hạn</span>';
                                                                                $needsRenewal = true;
                                                                            }
                                                                        }
                                                                    ?>
                                                                    <tr>
                                                                        <td>
                                                                            <?php echo htmlspecialchars($roomStudent['full_name'], ENT_QUOTES, 'UTF-8'); ?>
                                                                            (<?php echo htmlspecialchars($roomStudent['student_code'], ENT_QUOTES, 'UTF-8'); ?>)
                                                                        </td>
                                                                        <td class="text-center"><?php echo htmlspecialchars(formatDate($roomStudent['start_date']), ENT_QUOTES, 'UTF-8'); ?></td>
                                                                        <td class="text-center">
                                                                            <?php echo $roomStudent['end_date'] ? htmlspecialchars(formatDate($roomStudent['end_date']), ENT_QUOTES, 'UTF-8') : '-'; ?>
                                                                            <?php echo $dateBadge; ?>
                                                                        </td>
                                                                        <td class="text-center"><?php echo htmlspecialchars(formatCurrency($roomStudent['deposit']), ENT_QUOTES, 'UTF-8'); ?></td>
                                                                        <td>
                                                                            <div class="d-flex justify-content-center gap-2">
                                                                                <?php if ($needsRenewal) : ?>
                                                                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#renewContractModal<?php echo $roomStudent['contract_id']; ?>">Gia hạn</button>
                                                                                <?php endif; ?>
                                                                                <form method="post" action="" class="d-inline" onsubmit="return confirm('Bạn chắc chắn muốn thanh lý hợp đồng này?');">
                                                                                    <input type="hidden" name="action" value="terminate">
                                                                                    <input type="hidden" name="contract_id" value="<?php echo (int) $roomStudent['contract_id']; ?>">
                                                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Thanh lý</button>
                                                                                </form>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                <?php endif; ?>

                                                <form method="post" action="" class="row g-2 align-items-end">
                                                    <input type="hidden" name="action" value="add_student">
                                                    <input type="hidden" name="room_id" value="<?php echo (int) $room['room_id']; ?>">
                                                    <div class="col-12 col-lg-4">
                                                        <label class="form-label">Thêm sinh viên</label>
                                                        <select class="form-select" name="student_id" required>
                                                            <option value="">-- Chọn sinh viên --</option>
                                                            <?php foreach ($availableStudents as $student) : ?>
                                                                <option value="<?php echo (int) $student['student_id']; ?>">
                                                                    <?php echo htmlspecialchars($student['full_name'], ENT_QUOTES, 'UTF-8'); ?>
                                                                    (<?php echo htmlspecialchars($student['student_code'], ENT_QUOTES, 'UTF-8'); ?>)
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-12 col-md-4 col-lg-3">
                                                        <label class="form-label">Tiền cọc</label>
                                                        <input type="number" step="0.01" class="form-control" name="deposit" required>
                                                    </div>
                                                    <div class="col-12 col-lg-2">
                                                        <button type="submit" class="btn btn-outline-primary w-100">Thêm</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php foreach ($roomContracts as $roomId => $students) : ?>
    <?php foreach ($students as $roomStudent) : ?>
        <div class="modal fade" id="renewContractModal<?php echo $roomStudent['contract_id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="post" action="">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">Gia hạn hợp đồng</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="action" value="admin_renew">
                            <input type="hidden" name="contract_id" value="<?php echo (int) $roomStudent['contract_id']; ?>">
                            <div class="mb-3">
                                <label class="form-label">Sinh viên</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($roomStudent['full_name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($roomStudent['student_code'], ENT_QUOTES, 'UTF-8'); ?>)" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Ngày kết thúc hiện tại</label>
                                <input type="text" class="form-control" value="<?php echo $roomStudent['end_date'] ? htmlspecialchars(formatDate($roomStudent['end_date']), ENT_QUOTES, 'UTF-8') : '-'; ?>" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Ngày kết thúc MỚI <span class="text-danger">*</span></label>
                                <?php
                                    $defaultNewEnd = '';
                                    if ($roomStudent['end_date']) {
                                        $d = new DateTime($roomStudent['end_date']);
                                        $d->modify('+6 months');
                                        $defaultNewEnd = $d->format('Y-m-d');
                                    }
                                ?>
                                <input type="date" class="form-control" name="new_end_date" required value="<?php echo $defaultNewEnd; ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Giá thuê mới <span class="text-muted fw-normal">(bỏ trống nếu không đổi)</span></label>
                                <input type="number" min="0" class="form-control" name="new_price" placeholder="<?php echo htmlspecialchars(formatCurrency($roomStudent['price']), ENT_QUOTES, 'UTF-8'); ?>">
                                <small class="text-muted">Giá hiện tại: <?php echo htmlspecialchars(formatCurrency($roomStudent['price']), ENT_QUOTES, 'UTF-8'); ?></small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-primary">Xác nhận gia hạn</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endforeach; ?>


<script>
    document.querySelectorAll('.room-contract-row').forEach(row => {
        row.addEventListener('click', event => {
            if (event.target.closest('button, a, input, select, textarea, form, label')) {
                return;
            }

            const targetId = row.getAttribute('data-bs-target');
            if (!targetId) {
                return;
            }

            const target = document.querySelector(targetId);
            if (!target) {
                return;
            }

            const collapse = bootstrap.Collapse.getOrCreateInstance(target, { toggle: false });
            collapse.toggle();
        });
    });
</script>

<div class="modal fade" id="addContractModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-file-earmark-plus me-2"></i>Tạo hợp đồng — Phòng <span id="addContractRoomLabel"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="room_id" id="addContractRoomId">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Chọn sinh viên <span class="text-danger">*</span></label>
                        <select class="form-select" name="student_id" id="addContractStudentId" required>
                            <option value="">-- Chọn sinh viên --</option>
                            <?php foreach ($availableStudents as $student) : ?>
                                <option value="<?php echo (int) $student['student_id']; ?>">
                                    <?php echo htmlspecialchars($student['full_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    (<?php echo htmlspecialchars($student['student_code'], ENT_QUOTES, 'UTF-8'); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Giá thuê (VNĐ) <span class="text-danger">*</span></label>
                            <input type="number" min="0" class="form-control" name="price" id="addContractPrice" placeholder="VD: 1500000" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Tiền cọc (VNĐ) <span class="text-danger">*</span></label>
                            <input type="number" min="0" class="form-control" name="deposit" id="addContractDeposit" placeholder="VD: 500000" required>
                        </div>
                    </div>
                    <p class="text-muted mt-3 mb-0 small"><i class="bi bi-info-circle me-1"></i>Hợp đồng sẽ bắt đầu từ hôm nay và kết thúc sau 1 năm.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Tạo hợp đồng</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('addContractModal').addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        if (!btn || !btn.classList.contains('btn-create-contract')) return;
        document.getElementById('addContractRoomId').value    = btn.dataset.roomId;
        document.getElementById('addContractRoomLabel').textContent = btn.dataset.roomNumber;
        document.getElementById('addContractStudentId').value = '';
        document.getElementById('addContractPrice').value     = '';
        document.getElementById('addContractDeposit').value   = '';
    });
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>


