-- Demo seeding for the simplified dormitory system
-- Contracts only store room assignment; money is stored in UtilityBill.

START TRANSACTION;

INSERT INTO Room (room_number, floor_number, capacity, room_type, status, price) VALUES
(101, 1, 4, 'Thường', 'Hoạt động', 500000),
(102, 1, 4, 'Thường', 'Hoạt động', 500000),
(103, 1, 6, 'Dịch vụ', 'Hoạt động', 750000),
(201, 2, 4, 'Thường', 'Hoạt động', 520000),
(202, 2, 4, 'Thường', 'Đang sửa chữa', 500000),
(203, 2, 6, 'Dịch vụ', 'Hoạt động', 780000),
(301, 3, 4, 'Thường', 'Hoạt động', 530000),
(302, 3, 4, 'Thường', 'Hoạt động', 530000);

INSERT INTO Student (full_name, student_code, dob, phone, email, department, status, priority_level, boarding_score) VALUES
('Nguyen Van An', 'SV001', '2002-01-10', '0901000001', 'sv001@example.com', 'CNTT', 'Đang ở', 1, 92),
('Tran Thi Binh', 'SV002', '2002-03-15', '0901000002', 'sv002@example.com', 'Kế toán', 'Đang ở', 2, 86),
('Le Van Cuong', 'SV003', '2001-11-08', '0901000003', 'sv003@example.com', 'CNTT', 'Đang ở', 3, 78),
('Pham Thi Dung', 'SV004', '2003-02-20', '0901000004', 'sv004@example.com', 'Sư phạm', 'Đang ở', 2, 84),
('Hoang Van Em', 'SV005', '2002-08-09', '0901000005', 'sv005@example.com', 'CNTT', 'Đang ở', 4, 70),
('Vu Thi Phuong', 'SV006', '2001-07-12', '0901000006', 'sv006@example.com', 'Y dược', 'Đang ở', 3, 74),
('Bui Van Giang', 'SV007', '2002-10-25', '0901000007', 'sv007@example.com', 'Cơ khí', 'Đang ở', 5, 68),
('Do Thi Ha', 'SV008', '2003-04-03', '0901000008', 'sv008@example.com', 'Kinh tế', 'Đang ở', 2, 88),
('Ngo Van Khoa', 'SV009', '2002-06-18', '0901000009', 'sv009@example.com', 'CNTT', 'Đang ở', 1, 90),
('Ly Thi Lan', 'SV010', '2001-09-22', '0901000010', 'sv010@example.com', 'Sư phạm', 'Đang ở', 6, 66),
('Trinh Van Minh', 'SV011', '2002-12-01', '0901000011', 'sv011@example.com', 'Xây dựng', 'Đang ở', 4, 72),
('Nguyen Thi Nhi', 'SV012', '2003-01-14', '0901000012', 'sv012@example.com', 'Kế toán', 'Chờ duyệt', 7, 62),
('Tran Van Oanh', 'SV013', '2001-05-28', '0901000013', 'sv013@example.com', 'CNTT', 'Chờ duyệt', 8, 58),
('Le Thi Quyen', 'SV014', '2002-03-30', '0901000014', 'sv014@example.com', 'Y dược', 'Chờ duyệt', 7, 64),
('Pham Van Son', 'SV015', '2001-10-05', '0901000015', 'sv015@example.com', 'Cơ khí', 'Đã chuyển đi', 5, 60),
('Hoang Thi Trang', 'SV016', '2002-07-07', '0901000016', 'sv016@example.com', 'Kinh tế', 'Đã chuyển đi', 6, 57);

INSERT INTO Contract (student_id, room_id, start_date, end_date, status) VALUES
(1, 1, '2026-01-05', '2026-06-05', 'Đang ở'),
(2, 1, '2026-01-05', '2026-06-05', 'Đang ở'),
(3, 1, '2026-02-01', '2026-07-01', 'Đang ở'),
(4, 2, '2026-01-12', '2026-06-12', 'Đang ở'),
(5, 2, '2026-02-10', '2026-07-10', 'Đang ở'),
(6, 3, '2026-01-20', '2026-06-20', 'Đang ở'),
(7, 3, '2026-02-03', '2026-07-03', 'Đang ở'),
(8, 3, '2026-02-15', '2026-07-15', 'Đang ở'),
(9, 4, '2026-01-25', '2026-06-25', 'Đang ở'),
(10, 6, '2026-01-18', '2026-06-18', 'Đang ở'),
(11, 6, '2026-02-22', '2026-07-22', 'Đang ở');

INSERT INTO UtilityBill (room_id, billing_month, billing_year, total_amount, new_electric_index, new_water_index, status) VALUES
(1, 3, 2026, 620000, 120, 32, 'Đã thanh toán'),
(1, 4, 2026, 640000, 145, 39, 'Chưa thanh toán'),
(2, 4, 2026, 590000, 98, 28, 'Chưa thanh toán'),
(3, 4, 2026, 860000, 180, 45, 'Chưa thanh toán'),
(4, 4, 2026, 560000, 110, 25, 'Đã thanh toán'),
(6, 4, 2026, 900000, 190, 48, 'Chưa thanh toán');

INSERT INTO Notice (target_type, category, point_change, room_id, student_id, description, date) VALUES
('Cả tòa', 'Thông báo chung', 0, NULL, NULL, 'Khai báo nội quy tháng 5 đã được cập nhật.', CURDATE()),
('Phòng', 'Khen thưởng', 5, 1, NULL, 'Phòng 101 giữ vệ sinh tốt, cộng điểm thi đua.', CURDATE()),
('Cá nhân', 'Kỷ luật', -10, NULL, 3, 'Sinh viên cần nhắc nhở về giờ giấc sinh hoạt.', CURDATE());

COMMIT;
