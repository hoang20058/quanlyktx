# Hệ thống Quản lý Ký túc xá

Ứng dụng PHP thuần + MySQL + Bootstrap 5 cho bài tập quản lý ký túc xá với đúng 5 bảng dữ liệu: `Student`, `Room`, `Contract`, `UtilityBill`, `Notice`.

## Yêu cầu
- XAMPP / Apache / MySQL
- PHP 8.x
- MySQL 8.x hoặc MariaDB tương thích

## Cấu hình
File `.env` đang dùng:
- `DB_HOST=127.0.0.1`
- `DB_PORT=3306`
- `DB_NAME=quanlyktx`
- `DB_USER=root`
- `DB_PASS=`
- `APP_TIMEZONE=Asia/Ho_Chi_Minh`

## Cài đặt database
Chạy script import schema:

```powershell
& 'd:\Programs\XAMPP\php\php.exe' 'd:\Programs\XAMPP\htdocs\quanlyktx\database\import_schema.php'
```

Nếu muốn nạp dữ liệu demo:

```powershell
& 'd:\Programs\XAMPP\php\php.exe' 'd:\Programs\XAMPP\htdocs\quanlyktx\database\seed.php'
```

## Chạy ứng dụng
- Public site: `http://localhost/quanlyktx/public/`
- Admin: `http://localhost/quanlyktx/public/login.php`

## Đăng nhập admin
- Username: `admin`
- Password: `admin`

## Tính năng chính
- Trang chủ public: hero, carousel, thống kê, phòng, leaderboard, đăng ký, liên hệ
- Admin: quản lý sinh viên, phòng, thông báo
- Điểm nội trú: khen thưởng / kỷ luật tác động vào `boarding_score`
- Liên hệ: lưu dự phòng vào `storage/contact_messages.log`

## Ghi chú
- Dự án dùng autoload đơn giản qua `config/app.php`.
- Các endpoint JSON nằm trong `api/` và yêu cầu đăng nhập admin.
