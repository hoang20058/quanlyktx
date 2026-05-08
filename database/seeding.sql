-- Demo seeding for dormitory system (compact dataset)
-- 8 rooms, 16 students, 11 active contracts, includes empty/underfilled rooms

START TRANSACTION;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE UtilityBill;
TRUNCATE TABLE Contract;
TRUNCATE TABLE Notice;
TRUNCATE TABLE Student;
TRUNCATE TABLE Room;
SET FOREIGN_KEY_CHECKS = 1;

-- Rooms: 8 total, each floor has 2-3 rooms
INSERT INTO Room (room_number, floor_number, capacity, room_type, status, price) VALUES
(101,1,4,'Thường','Hoạt động',500000),
(102,1,4,'Thường','Hoạt động',500000),
(103,1,6,'Dịch vụ','Hoạt động',750000),
(201,2,4,'Thường','Hoạt động',520000),
(202,2,4,'Thường','Đang sửa chữa',500000),
(203,2,6,'Dịch vụ','Hoạt động',780000),
(301,3,4,'Thường','Hoạt động',530000),
(302,3,4,'Thường','Hoạt động',530000);

-- Students: 16 total (11 living, 3 waiting, 2 moved)
INSERT INTO Student (full_name, student_code, dob, phone, email, department, status, priority_level, boarding_score) VALUES
('Nguyen Van An','SV001','2002-01-10','0901000001','sv001@example.com','CNTT','Đang ở',1,92),
('Tran Thi Binh','SV002','2002-03-15','0901000002','sv002@example.com','Kế toán','Đang ở',2,86),
('Le Van Cuong','SV003','2001-11-08','0901000003','sv003@example.com','CNTT','Đang ở',3,78),
('Pham Thi Dung','SV004','2003-02-20','0901000004','sv004@example.com','Sư phạm','Đang ở',2,84),
('Hoang Van Em','SV005','2002-08-09','0901000005','sv005@example.com','CNTT','Đang ở',4,70),
('Vu Thi Phuong','SV006','2001-07-12','0901000006','sv006@example.com','Y dược','Đang ở',3,74),
('Bui Van Giang','SV007','2002-10-25','0901000007','sv007@example.com','Cơ khí','Đang ở',5,68),
('Do Thi Ha','SV008','2003-04-03','0901000008','sv008@example.com','Kinh tế','Đang ở',2,88),
('Ngo Van Khoa','SV009','2002-06-18','0901000009','sv009@example.com','CNTT','Đang ở',1,90),
('Ly Thi Lan','SV010','2001-09-22','0901000010','sv010@example.com','Sư phạm','Đang ở',6,66),
('Trinh Van Minh','SV011','2002-12-01','0901000011','sv011@example.com','Xây dựng','Đang ở',4,72),
('Nguyen Thi Nhi','SV012','2003-01-14','0901000012','sv012@example.com','Kế toán','Chờ duyệt',7,62),
('Tran Van Oanh','SV013','2001-05-28','0901000013','sv013@example.com','CNTT','Chờ duyệt',8,58),
('Le Thi Quyen','SV014','2002-03-30','0901000014','sv014@example.com','Y dược','Chờ duyệt',7,64),
('Pham Van Son','SV015','2001-10-05','0901000015','sv015@example.com','Cơ khí','Đã chuyển đi',5,60),
('Hoang Thi Trang','SV016','2002-07-07','0901000016','sv016@example.com','Kinh tế','Đã chuyển đi',6,57);

-- Contracts: 11 living, with price calculations and deposits
-- Formula: price = (room.price / 30 * days_in_contract) * (100 - discount) / 100
-- discount_percent: priority 1-2 = 50%, priority 3-4 = 30%, priority 5+ = 10%
-- For simplicity: assume 5-month contracts (150 days)
INSERT INTO Contract (student_id, room_id, start_date, end_date, status, price, deposit, discount_percent)
SELECT 
    s.student_id, 
    r.room_id, 
    '2026-01-05', 
    '2026-06-05',
    'Đang ở', 
    ROUND((r.price / 30 * 150) * (100 - IF(s.priority_level <= 2, 50, IF(s.priority_level <= 4, 30, 10))) / 100, 2),
    ROUND((r.price / 30 * 150) * (100 - IF(s.priority_level <= 2, 50, IF(s.priority_level <= 4, 30, 10))) / 100 * 0.8, 2),
    IF(s.priority_level <= 2, 50, IF(s.priority_level <= 4, 30, 10))
FROM Student s 
JOIN Room r ON s.student_code='SV001' AND r.room_number=101
UNION ALL
SELECT 
    s.student_id, 
    r.room_id, 
    '2026-01-05', 
    '2026-06-05',
    'Đang ở', 
    ROUND((r.price / 30 * 150) * (100 - IF(s.priority_level <= 2, 50, IF(s.priority_level <= 4, 30, 10))) / 100, 2),
    ROUND((r.price / 30 * 150) * (100 - IF(s.priority_level <= 2, 50, IF(s.priority_level <= 4, 30, 10))) / 100 * 0.9, 2),
    IF(s.priority_level <= 2, 50, IF(s.priority_level <= 4, 30, 10))
FROM Student s 
JOIN Room r ON s.student_code='SV002' AND r.room_number=101
UNION ALL
SELECT 
    s.student_id, 
    r.room_id, 
    '2026-02-01', 
    '2026-07-01',
    'Đang ở', 
    ROUND((r.price / 30 * 150) * (100 - IF(s.priority_level <= 2, 50, IF(s.priority_level <= 4, 30, 10))) / 100, 2),
    ROUND((r.price / 30 * 150) * (100 - IF(s.priority_level <= 2, 50, IF(s.priority_level <= 4, 30, 10))) / 100 * 0.7, 2),
    IF(s.priority_level <= 2, 50, IF(s.priority_level <= 4, 30, 10))
FROM Student s 
JOIN Room r ON s.student_code='SV003' AND r.room_number=101
UNION ALL
SELECT 
    s.student_id, 
    r.room_id, 
    '2026-01-12', 
    '2026-06-12',
    'Đang ở', 
    ROUND((r.price / 30 * 150) * (100 - IF(s.priority_level <= 2, 50, IF(s.priority_level <= 4, 30, 10))) / 100, 2),
    ROUND((r.price / 30 * 150) * (100 - IF(s.priority_level <= 2, 50, IF(s.priority_level <= 4, 30, 10))) / 100, 2),
    IF(s.priority_level <= 2, 50, IF(s.priority_level <= 4, 30, 10))
FROM Student s 
JOIN Room r ON s.student_code='SV004' AND r.room_number=102
UNION ALL
SELECT 
    s.student_id, 
    r.room_id, 
    '2026-02-10', 
    '2026-07-10',
    'Đang ở', 
    ROUND((r.price / 30 * 150) * (100 - IF(s.priority_level <= 2, 50, IF(s.priority_level <= 4, 30, 10))) / 100, 2),
    ROUND((r.price / 30 * 150) * (100 - IF(s.priority_level <= 2, 50, IF(s.priority_level <= 4, 30, 10))) / 100 * 0.5, 2),
    IF(s.priority_level <= 2, 50, IF(s.priority_level <= 4, 30, 10))
FROM Student s 
JOIN Room r ON s.student_code='SV005' AND r.room_number=102
UNION ALL
SELECT 
    s.student_id, 
    r.room_id, 
    '2026-01-20', 
    '2026-06-20',
    'Đang ở', 
    ROUND((r.price / 30 * 150) * (100 - IF(s.priority_level <= 2, 50, IF(s.priority_level <= 4, 30, 10))) / 100, 2),
    ROUND((r.price / 30 * 150) * (100 - IF(s.priority_level <= 2, 50, IF(s.priority_level <= 4, 30, 10))) / 100, 2),
    IF(s.priority_level <= 2, 50, IF(s.priority_level <= 4, 30, 10))
FROM Student s 
JOIN Room r ON s.student_code='SV006' AND r.room_number=103
UNION ALL
SELECT 
    s.student_id, 
    r.room_id, 
    '2026-02-03', 
    '2026-07-03',
    'Đang ở', 
    ROUND((r.price / 30 * 150) * (100 - IF(s.priority_level <= 2, 50, IF(s.priority_level <= 4, 30, 10))) / 100, 2),
    0,
    IF(s.priority_level <= 2, 50, IF(s.priority_level <= 4, 30, 10))
FROM Student s 
JOIN Room r ON s.student_code='SV007' AND r.room_number=103
UNION ALL
SELECT 
    s.student_id, 
    r.room_id, 
    '2026-02-15', 
    '2026-07-15',
    'Đang ở', 
    ROUND((r.price / 30 * 150) * (100 - IF(s.priority_level <= 2, 50, IF(s.priority_level <= 4, 30, 10))) / 100, 2),
    ROUND((r.price / 30 * 150) * (100 - IF(s.priority_level <= 2, 50, IF(s.priority_level <= 4, 30, 10))) / 100 * 0.6, 2),
    IF(s.priority_level <= 2, 50, IF(s.priority_level <= 4, 30, 10))
FROM Student s 
JOIN Room r ON s.student_code='SV008' AND r.room_number=103
UNION ALL
SELECT 
    s.student_id, 
    r.room_id, 
    '2026-01-25', 
    '2026-06-25',
    'Đang ở', 
    ROUND((r.price / 30 * 150) * (100 - IF(s.priority_level <= 2, 50, IF(s.priority_level <= 4, 30, 10))) / 100, 2),
    ROUND((r.price / 30 * 150) * (100 - IF(s.priority_level <= 2, 50, IF(s.priority_level <= 4, 30, 10))) / 100, 2),
    IF(s.priority_level <= 2, 50, IF(s.priority_level <= 4, 30, 10))
FROM Student s 
JOIN Room r ON s.student_code='SV009' AND r.room_number=201
UNION ALL
SELECT 
    s.student_id, 
    r.room_id, 
    '2026-01-18', 
    '2026-06-18',
    'Đang ở', 
    ROUND((r.price / 30 * 150) * (100 - IF(s.priority_level <= 2, 50, IF(s.priority_level <= 4, 30, 10))) / 100, 2),
    ROUND((r.price / 30 * 150) * (100 - IF(s.priority_level <= 2, 50, IF(s.priority_level <= 4, 30, 10))) / 100 * 0.75, 2),
    IF(s.priority_level <= 2, 50, IF(s.priority_level <= 4, 30, 10))
FROM Student s 
JOIN Room r ON s.student_code='SV010' AND r.room_number=203
UNION ALL
SELECT 
    s.student_id, 
    r.room_id, 
    '2026-02-22', 
    '2026-07-22',
    'Đang ở', 
    ROUND((r.price / 30 * 150) * (100 - IF(s.priority_level <= 2, 50, IF(s.priority_level <= 4, 30, 10))) / 100, 2),
    ROUND((r.price / 30 * 150) * (100 - IF(s.priority_level <= 2, 50, IF(s.priority_level <= 4, 30, 10))) / 100 * 0.85, 2),
    IF(s.priority_level <= 2, 50, IF(s.priority_level <= 4, 30, 10))
FROM Student s 
JOIN Room r ON s.student_code='SV011' AND r.room_number=203;

-- Utility bills for selected rooms
INSERT INTO UtilityBill (room_id, billing_month, billing_year, total_amount, status)
SELECT r.room_id, 3, 2026, 620000, 'Đã thanh toán' FROM Room r WHERE r.room_number=101
UNION ALL
SELECT r.room_id, 4, 2026, 640000, 'Chưa thanh toán' FROM Room r WHERE r.room_number=101
UNION ALL
SELECT r.room_id, 4, 2026, 590000, 'Chưa thanh toán' FROM Room r WHERE r.room_number=102
UNION ALL
SELECT r.room_id, 4, 2026, 860000, 'Chưa thanh toán' FROM Room r WHERE r.room_number=103
UNION ALL
SELECT r.room_id, 4, 2026, 560000, 'Đã thanh toán' FROM Room r WHERE r.room_number=201
UNION ALL
SELECT r.room_id, 4, 2026, 900000, 'Chưa thanh toán' FROM Room r WHERE r.room_number=203;

COMMIT;
