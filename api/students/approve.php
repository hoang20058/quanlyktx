<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAuth();

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('Only POST allowed');
    }

    $token = $_POST['csrf_token'] ?? null;
    if (!Security::verifyCsrfToken($token)) {
        throw new RuntimeException('CSRF token không hợp lệ');
    }

    $studentId = (int) ($_POST['student_id'] ?? 0);
    $roomId = (int) ($_POST['room_id'] ?? 0);

    if ($studentId <= 0 || $roomId <= 0) {
        throw new InvalidArgumentException('Dữ liệu không hợp lệ.');
    }

    $db = Database::connection();

    // validate existence
    $student = StudentRepository::find($studentId);
    if (!$student) {
        throw new RuntimeException('Sinh viên không tồn tại.');
    }

    $room = RoomRepository::find($roomId);
    if (!$room) {
        throw new RuntimeException('Phòng không tồn tại.');
    }

    // check capacity
    $stmtOcc = $db->prepare("SELECT COUNT(*) FROM Contract WHERE room_id = :rid AND status = 'Đang ở'");
    $stmtOcc->execute([':rid' => $roomId]);
    $occupied = (int) $stmtOcc->fetchColumn();

    if ($occupied >= (int) $room['capacity']) {
        throw new RuntimeException('Phòng đã đầy, vui lòng chọn phòng khác.');
    }

    $db->beginTransaction();
    try {
        // update student status
        $stmt = $db->prepare("UPDATE Student SET status = 'Đang ở' WHERE student_id = :id");
        $stmt->execute([':id' => $studentId]);

        // insert contract with end_date set to 5 months from now (standard contract period)
        $startDate = new DateTime();
        $endDate = clone $startDate;
        $endDate->modify('+5 months');
        
        $ins = $db->prepare('INSERT INTO Contract (student_id, room_id, start_date, end_date, deposit, discount_percent, status) VALUES (:student_id, :room_id, :start_date, :end_date, :deposit, :discount_percent, :status)');
        $ins->execute([
            ':student_id' => $studentId, 
            ':room_id' => $roomId, 
            ':start_date' => $startDate->format('Y-m-d'),
            ':end_date' => $endDate->format('Y-m-d'),
            ':deposit' => 0,
            ':discount_percent' => 0,
            ':status' => 'Đang ở'
        ]);
        $contractId = (int) $db->lastInsertId();

        // insert notice (targeting the room and student)
        $studentName = $student['full_name'];
        $roomNumber = $room['room_number'];
        $desc = sprintf('Chúc mừng sinh viên %s đã được phân vào phòng %s', $studentName, $roomNumber);
        $nstmt = $db->prepare('INSERT INTO Notice (target_type, category, point_change, room_id, student_id, description, date) VALUES (:target_type, :category, :point_change, :room_id, :student_id, :description, CURDATE())');
        $nstmt->execute([
            ':target_type' => 'Phòng',
            ':category' => 'Khen thưởng',
            ':point_change' => 0,
            ':room_id' => $roomId,
            ':student_id' => $studentId,
            ':description' => $desc,
        ]);
        $noticeId = (int) $db->lastInsertId();

        $db->commit();

        echo json_encode(['ok' => true, 'message' => 'Duyệt sinh viên thành công.', 'contract_id' => $contractId, 'notice_id' => $noticeId]);
        exit;
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
    exit;
}
