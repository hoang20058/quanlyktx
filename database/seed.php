<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

$db = Database::connection();
$db->exec('SET FOREIGN_KEY_CHECKS = 0');
$db->exec('DELETE FROM Notice');
$db->exec('DELETE FROM UtilityBill');
$db->exec('DELETE FROM Contract');
$db->exec('DELETE FROM Room');
$db->exec('DELETE FROM Student');
$db->exec('SET FOREIGN_KEY_CHECKS = 1');

$rooms = [
    [101, 1, 6, 'Thường', 'Hoạt động', 650000],
    [102, 1, 4, 'Thường', 'Hoạt động', 620000],
    [204, 2, 8, 'Dịch vụ', 'Hoạt động', 720000],
    [302, 3, 6, 'Thường', 'Đang sửa chữa', 580000],
    [401, 4, 2, 'Dịch vụ', 'Hoạt động', 850000],
];
$stmt = $db->prepare('INSERT INTO Room (room_number, floor_number, capacity, room_type, status, price) VALUES (?, ?, ?, ?, ?, ?)');
foreach ($rooms as $room) {
    $stmt->execute($room);
}

$students = [
    ['Nguyễn Văn An', 'SV001', '2004-01-15', '0901000001', 'an@example.com', 'Công nghệ thông tin', 'Đang ở', 8, 105],
    ['Trần Thị Bình', 'SV002', '2004-03-20', '0901000002', 'binh@example.com', 'Kế toán', 'Đang ở', 7, 98],
    ['Lê Quốc Cường', 'SV003', '2005-07-01', '0901000003', 'cuong@example.com', 'Điện tử', 'Chờ duyệt', 8, 100],
    ['Phạm Thị Duyên', 'SV004', '2004-11-10', '0901000004', 'duyen@example.com', 'Du lịch', 'Đang ở', 6, 112],
    ['Hoàng Minh Khoa', 'SV005', '2003-06-25', '0901000005', 'khoa@example.com', 'Quản trị kinh doanh', 'Đã chuyển đi', 8, 90],
];
$stmt = $db->prepare('INSERT INTO Student (full_name, student_code, dob, phone, email, department, status, priority_level, boarding_score) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
foreach ($students as $student) {
    $stmt->execute($student);
}

$contracts = [
    [1, 1, '2026-02-01', null, 'Đang ở'],
    [2, 2, '2026-03-01', null, 'Đang ở'],
    [4, 3, '2026-04-01', null, 'Đang ở'],
];
$stmt = $db->prepare('INSERT INTO Contract (student_id, room_id, start_date, end_date, status) VALUES (?, ?, ?, ?, ?)');
foreach ($contracts as $contract) {
    $stmt->execute($contract);
}

$notices = [
    ['Cả tòa', 'Thông báo chung', 0, null, null, 'Khai báo nội quy tháng 5 đã được cập nhật.', date('Y-m-d')],
    ['Phòng', 'Khen thưởng', 5, 1, null, 'Phòng 101 giữ vệ sinh tốt, cộng điểm thi đua.', date('Y-m-d')],
    ['Cá nhân', 'Kỷ luật', -10, null, 3, 'Sinh viên cần nhắc nhở về giờ giấc sinh hoạt.', date('Y-m-d')],
];
$stmt = $db->prepare('INSERT INTO Notice (target_type, category, point_change, room_id, student_id, description, date) VALUES (?, ?, ?, ?, ?, ?, ?)');
foreach ($notices as $notice) {
    $stmt->execute($notice);
}

echo "Seed completed successfully.\n";
