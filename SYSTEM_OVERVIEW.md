# SYSTEM_OVERVIEW

## 1. Mục tiêu dự án
Dự án "Quản lý Ký túc xá" là một hệ thống PHP thuần + MySQL nhằm hỗ trợ quản lý ký túc xá theo mô hình tập trung. Hệ thống cho phép quản lý sinh viên, phòng ở, hợp đồng, hóa đơn điện nước và các thông báo khen thưởng / kỷ luật.

Mục tiêu chính:
- Số hóa quy trình đăng ký ở ký túc xá.
- Quản lý phân phòng và hợp đồng ở.
- Theo dõi công nợ, thanh toán và hóa đơn tiện ích.
- Quản lý điểm nội trú để phục vụ xếp loại sinh viên.

## 2. Kiến trúc tổng thể
Dự án không dùng framework lớn mà tổ chức theo mô hình phân lớp đơn giản:

- `public/`: giao diện người dùng và admin.
- `views/`: layout, partials và form view.
- `api/`: endpoint xử lý request AJAX / form submit.
- `models/`: lớp repository thao tác dữ liệu bằng PDO.
- `core/`: hạ tầng nền tảng như `Database`, `Security`, `Api`, `Env`.
- `database/`: schema, seed và script import.

Các phần đã được dọn sạch trong giai đoạn 1:
- Loại bỏ endpoint chốt hóa đơn hàng loạt cũ không còn dùng.
- Loại bỏ file test rời không thuộc luồng nghiệp vụ.
- Chuẩn hóa việc import seed theo `database/seeding.sql`.

Luồng xử lý chung:
1. Người dùng truy cập trang trong `public/`.
2. Trang nạp `config/app.php` để khởi tạo môi trường, session và autoload.
3. Giao diện gọi `models/` để lấy dữ liệu.
4. Khi lưu / sửa / xóa, giao diện gửi request tới `api/`.
5. `api/` xác thực CSRF, phân quyền và gọi repository để cập nhật MySQL.

## 3. 5 bảng dữ liệu trong Database
### 3.1 `Student`
Lưu thông tin sinh viên đăng ký ở ký túc xá hoặc đang nội trú.

Ý nghĩa các cột chính:
- `student_id`: khóa chính.
- `full_name`: họ tên.
- `student_code`: mã sinh viên.
- `dob`: ngày sinh.
- `phone`: số điện thoại.
- `email`: email liên hệ.
- `department`: khoa / ngành.
- `status`: trạng thái hồ sơ hoặc nội trú.
- `priority_level`: mức ưu tiên xét duyệt.
- `boarding_score`: điểm nội trú.

### 3.2 `Room`
Lưu thông tin phòng ở.

Ý nghĩa các cột chính:
- `room_id`: khóa chính.
- `room_number`: số phòng.
- `floor_number`: tầng.
- `capacity`: sức chứa.
- `room_type`: loại phòng.
- `status`: trạng thái hoạt động / bảo trì.
- `price`: giá phòng theo tháng.

### 3.3 `Contract`
Lưu hợp đồng giữa sinh viên và phòng ở.

Ý nghĩa các cột chính:
- `contract_id`: khóa chính.
- `student_id`: sinh viên được gán hợp đồng.
- `room_id`: phòng được gán.
- `start_date`: ngày vào ở.
- `end_date`: ngày kết thúc.
- `price`: tổng tiền hợp đồng.
- `deposit`: số tiền đã thanh toán.
- `discount_percent`: tỷ lệ giảm giá.
- `status`: trạng thái hợp đồng.

### 3.4 `UtilityBill`
Lưu hóa đơn điện nước theo phòng và theo tháng.

Ý nghĩa các cột chính:
- `bill_id`: khóa chính.
- `room_id`: phòng phát sinh hóa đơn.
- `billing_month`: tháng ghi hóa đơn.
- `billing_year`: năm ghi hóa đơn.
- `total_amount`: tổng tiền phải trả.
- `status`: trạng thái thanh toán.

### 3.5 `Notice`
Lưu thông báo, khen thưởng, kỷ luật.

Ý nghĩa các cột chính:
- `notice_id`: khóa chính.
- `target_type`: phạm vi áp dụng (`Cả tòa`, `Phòng`, `Cá nhân`).
- `category`: loại thông báo.
- `point_change`: số điểm cộng / trừ.
- `room_id`: phòng liên quan.
- `student_id`: sinh viên liên quan.
- `description`: nội dung thông báo.
- `date`: ngày tạo.

## 4. Chức năng chính của Admin
### Dashboard
- Thống kê sinh viên, phòng, thông báo và công nợ.
- Hiển thị phòng nổi bật và sinh viên nợ tiền phòng.

### Quản lý sinh viên
- Thêm / sửa / xóa sinh viên.
- Duyệt hồ sơ đăng ký.
- Chuyển sinh viên sang phòng khác.
- Điều chỉnh điểm nội trú.

### Quản lý phòng
- CRUD phòng.
- Theo dõi sức chứa, số sinh viên đang ở.
- Thay đổi loại phòng, giá phòng, trạng thái phòng.

### Quản lý hợp đồng
- Tạo hợp đồng khi duyệt sinh viên.
- Tính công nợ dựa trên giá phòng và giảm giá.
- Xem chi tiết hợp đồng, thanh toán công nợ, gia hạn, kết thúc.

### Quản lý hóa đơn điện nước
- Nhập chỉ số điện và nước theo phòng / tháng.
- Tự tính số lượng tiêu thụ và tổng tiền.
- Quản lý trạng thái hóa đơn chưa thanh toán / đã thanh toán.

### Quản lý thông báo
- Tạo thông báo chung, khen thưởng, kỷ luật.
- Cộng / trừ `boarding_score` theo phạm vi tòa, phòng hoặc cá nhân.

## 5. Chức năng chính của Sinh viên / Public
- Xem trang chủ, phòng hoạt động, thông báo mới.
- Đăng ký ở ký túc xá.
- Tra cứu hóa đơn / tình trạng công nợ.
- Gửi liên hệ với ban quản lý.

## 6. Luồng dữ liệu quan trọng
### Đăng ký ở
1. Sinh viên gửi form đăng ký trên public.
2. Dữ liệu lưu vào `Student` với trạng thái `Chờ duyệt`.
3. Admin duyệt hồ sơ và gán phòng.
4. Hệ thống tạo `Contract` và cập nhật trạng thái sinh viên thành `Đang ở`.

### Tạo hóa đơn điện nước
1. Admin chọn phòng đang có người ở.
2. Nhập chỉ số điện cũ / mới và nước cũ / mới.
3. Hệ thống tính lượng tiêu thụ và tổng tiền.
4. Lưu vào `UtilityBill` theo tháng / năm.

### Chấm điểm nội trú
1. Admin tạo thông báo loại `Khen thưởng` hoặc `Kỷ luật`.
2. Hệ thống cập nhật `boarding_score` theo phạm vi áp dụng.
3. Khi xóa thông báo, hệ thống hoàn tác phần điểm đã cộng / trừ.

## 7. Điểm cần lưu ý khi báo cáo đồ án
- Hệ thống đang đi theo hướng "thuần PHP" nên logic được chia thủ công giữa `public/`, `api/` và `models/`.
- Nghiệp vụ tài chính không lưu cứng toàn bộ kết quả mà được tính động bằng PHP để tránh sai lệch.
- Session và CSRF được dùng để kiểm soát đăng nhập và request quan trọng.
- PDO được dùng thay cho nối chuỗi SQL trực tiếp.
- Các endpoint hàng loạt cũ đã được loại bỏ để tránh nhầm luồng xử lý trong khi bảo vệ đồ án.

## 8. Tóm tắt công nghệ
- Backend: PHP 8.x
- Database: MySQL / MariaDB
- Frontend: Bootstrap 5, DataTables, Chart.js, jQuery
- Kiến trúc: PHP thuần + repository + API JSON
