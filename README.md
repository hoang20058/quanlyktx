# Hệ thống Quản lý Ký túc xá

Ứng dụng PHP thuần + MySQL + Bootstrap 5 cho bài tập quản lý ký túc xá. Dự án đang được thiết kế theo mô hình đơn giản gồm 3 lớp chính: giao diện `public/` và `views/`, xử lý nghiệp vụ trong `models/`, và các API trong `api/`.

## Mục tiêu
- Quản lý sinh viên, phòng ở, hợp đồng và hóa đơn điện nước.
- Hỗ trợ quy trình đăng ký ở ký túc xá, duyệt hồ sơ, tạo hợp đồng và thu công nợ.
- Quản lý điểm nội trú theo cơ chế khen thưởng / kỷ luật.

## Yêu cầu môi trường
- XAMPP / Apache / MySQL
- PHP 8.x
- MySQL 8.x hoặc MariaDB tương thích
- Trình duyệt hỗ trợ Bootstrap 5

## Cấu hình kết nối
File cấu hình đọc từ `.env` ở thư mục gốc. Các biến quan trọng:

- `DB_HOST=127.0.0.1`
- `DB_PORT=3306`
- `DB_NAME=quanlyktx`
- `DB_USER=root`
- `DB_PASS=`
- `APP_TIMEZONE=Asia/Ho_Chi_Minh`

Nếu chạy trong XAMPP theo mặc định, bạn chỉ cần sửa lại `.env` cho đúng máy của mình.

## Cài đặt dự án
1. Copy toàn bộ thư mục dự án vào `htdocs`, ví dụ: `D:\Programs\XAMPP\htdocs\quanlyktx`.
2. Mở XAMPP và bật `Apache` + `MySQL`.
3. Tạo database rỗng hoặc để script tự tạo database `quanlyktx`.
4. Import schema bằng script PHP:

```powershell
& 'D:\Programs\XAMPP\php\php.exe' 'D:\Programs\XAMPP\htdocs\quanlyktx\database\import_schema.php'
```

5. Nếu cần dữ liệu mẫu, chạy thêm script seed:

```powershell
& 'D:\Programs\XAMPP\php\php.exe' 'D:\Programs\XAMPP\htdocs\quanlyktx\database\seed.php'
```

## Chạy ứng dụng
- Trang chủ public: `http://localhost/quanlyktx/public/`
- Trang đăng nhập sinh viên / public: `http://localhost/quanlyktx/public/login.php`
- Trang đăng nhập admin: `http://localhost/quanlyktx/public/admin/login.php`
- Trang admin sau đăng nhập: `http://localhost/quanlyktx/public/admin/index.php`

## Tài khoản demo
- Username: `admin`
- Password: `admin`

## Cấu trúc thư mục chính
- `public/`: các trang hiển thị cho người dùng và admin.
- `views/`: layout, partials, form view.
- `api/`: các endpoint nhận request AJAX / form submit.
- `models/`: repository thao tác database bằng PDO.
- `core/`: lớp nền tảng như `Database`, `Security`, `Api`, `Env`.
- `database/`: schema, seed và script import dữ liệu.
- `tools/`: script kiểm tra, nhập seed hoặc xử lý dữ liệu tiện ích.

## Tinh gọn sau khi clean code
- Đã loại bỏ các endpoint chốt hóa đơn hàng loạt cũ không còn dùng trong luồng hiện tại.
- Giữ lại luồng hóa đơn theo phòng / tháng trong `public/admin/meter-reading.php` và `api/bills/meter-reading.php`.
- Bộ seed đang dùng thống nhất qua `database/seeding.sql` và `tools/import_seed.php`.

## Chức năng chính
### Public / Sinh viên
- Xem trang chủ, phòng đang hoạt động, thông báo mới, bảng xếp hạng.
- Đăng ký ở ký túc xá.
- Tra cứu hóa đơn theo phòng / sinh viên.
- Gửi liên hệ.

### Admin
- Quản lý sinh viên: thêm, sửa, xóa, duyệt hồ sơ, chuyển phòng.
- Quản lý phòng: CRUD phòng, theo dõi sức chứa và trạng thái.
- Quản lý hợp đồng: tạo hợp đồng, gia hạn, kết thúc, xem công nợ, thanh toán hợp đồng.
- Quản lý hóa đơn điện nước: nhập chỉ số, tạo hóa đơn theo tháng, đánh dấu thanh toán.
- Quản lý thông báo: khen thưởng / kỷ luật làm thay đổi `boarding_score`.
- Xem dashboard và thống kê tổng quan.

## Ghi chú kỹ thuật
- Dự án dùng `config/app.php` để nạp cấu hình và autoload class.
- SQL được xử lý bằng PDO để hạn chế SQL Injection.
- Các thao tác AJAX trong admin gọi tới `api/` và trả JSON.
- Giao diện dùng Bootstrap 5, DataTables và Chart.js.
