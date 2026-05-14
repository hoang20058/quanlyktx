# Hướng Dẫn Cài Đặt - Quản Lý Ký Túc Xá

## 📋 Yêu Cầu Hệ Thống

- **PHP:** 8.0.30 trở lên
- **MySQL:** 5.7 trở lên (hoặc MariaDB)
- **Web Server:** Apache (đi kèm XAMPP)
- **Browser:** Chrome, Firefox, Safari, Edge (bất kỳ trình duyệt hiện đại)

## 🚀 Cài Đặt Nhanh (XAMPP)

### Bước 1: Chuẩn Bị Thư Mục

```bash
# Sao chép dự án vào htdocs của XAMPP
cp -r quanlyktx "D:\Programs\XAMPP\htdocs\"
```

### Bước 2: Cấu Hình Cơ Sở Dữ Liệu

```bash
# Tạo file .env với thông tin database
cd "D:\Programs\XAMPP\htdocs\quanlyktx"
cp .env.example .env
```

**Nội dung .env:**
```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=quanlyktx
DB_USER=root
DB_PASSWORD=

APP_URL=http://localhost/quanlyktx
APP_DEBUG=false
```

### Bước 3: Tạo Cơ Sở Dữ Liệu

```bash
# Mở MySQL Command Line
mysql -u root -p

# Tạo database
CREATE DATABASE quanlyktx CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE quanlyktx;

# Import schema
source D:\Programs\XAMPP\htdocs\quanlyktx\database\schema.sql;

# (Tùy chọn) Import demo data
source D:\Programs\XAMPP\htdocs\quanlyktx\database\seeding.sql;

exit;
```

**Hoặc sử dụng PHP CLI:**
```bash
cd "D:\Programs\XAMPP\htdocs\quanlyktx"
php tools/import_seed.php
```

### Bước 4: Khởi Động Ứng Dụng

1. **Mở XAMPP Control Panel**
   - Start Apache
   - Start MySQL

2. **Truy cập ứng dụng:**
   - Trang chủ: `http://localhost/quanlyktx/`
   - Admin: `http://localhost/quanlyktx/public/login.php`
   - Đăng nhập với: `admin` / `admin`

## 📁 Cấu Trúc Thư Mục

```
quanlyktx/
├── config/              # Tệp cấu hình ứng dụng
├── core/                # Lớp cốt lõi (Database, Security, API)
├── database/            # Schema SQL, dữ liệu demo
├── docs/                # Tài liệu dự án (hướng dẫn này)
├── models/              # Lớp truy cập dữ liệu (Repository)
├── public/              # Thư mục web root (điểm vào)
│   ├── admin/           # Trang quản trị
│   ├── assets/          # CSS, JavaScript
│   └── index.php        # Trang chủ
├── src/                 # Mã nguồn bổ sung
│   ├── Exceptions/      # Lớp ngoại lệ tùy chỉnh
│   └── Validation/      # Lớp xác thực đầu vào
├── storage/             # Thư mục lưu trữ (logs, uploads)
├── tools/               # Công cụ CLI (import, check hệ thống)
├── views/               # Các thành phần template
└── .env                 # Biến môi trường
```

## ✅ Kiểm Tra Hệ Thống

Chạy script kiểm tra tính toàn vẹn của hệ thống:

```bash
php tools/system_check.php
```

Kết quả sẽ hiển thị:
- ✅ Kết nối database
- ✅ Các bảng đã tạo
- ✅ Dữ liệu demo
- ✅ Quyền truy cập file

## 🔐 Đăng Nhập

### Tài Khoản Admin
- **Username:** admin
- **Password:** admin

**⚠️ Lưu ý:** Đây là tài khoản mặc định cho dự án đại học. Trong môi trường sản xuất, phải đổi thành password mạnh.

### Tài Khoản Sinh Viên
- Sinh viên có thể tự đăng ký tại `/public/register.php`
- Sau đó admin duyệt trong `/public/admin/students.php`

## 🐛 Xử Lý Sự Cố

### Lỗi: "không thể kết nối cơ sở dữ liệu"

**Giải pháp:**
1. Đảm bảo MySQL đang chạy trong XAMPP
2. Kiểm tra thông tin DB trong `.env`
3. Chạy lại `tools/import_seed.php`

### Lỗi: "404 - Trang không tìm thấy"

**Giải pháp:**
1. Kiểm tra URL: `http://localhost/quanlyktx/`
2. Kiểm tra Virtual Host trong Apache config (nếu dùng domain khác)
3. Restart Apache

### Lỗi: "Quyền truy cập bị từ chối"

**Giải pháp:**
1. Kiểm tra quyền thư mục: `chmod 755 public/ storage/`
2. Kiểm tra session cookies trong browser
3. Clear cache browser (Ctrl+Shift+Delete)

## 📚 Tài Liệu Liên Quan

- [ARCHITECTURE.md](ARCHITECTURE.md) - Thiết kế hệ thống
- [CODE_STANDARDS.md](CODE_STANDARDS.md) - Tiêu chuẩn code
- [API_GUIDE.md](API_GUIDE.md) - API endpoints
- [WORKFLOW.md](WORKFLOW.md) - Quy trình nghiệp vụ

## 🔧 Bảo Trì

### Sao Lưu Cơ Sở Dữ Liệu

```bash
mysqldump -u root -p quanlyktx > backup_$(date +%Y%m%d).sql
```

### Phục Hồi Từ Sao Lưu

```bash
mysql -u root -p quanlyktx < backup_20240514.sql
```

## 📞 Hỗ Trợ

Nếu gặp vấn đề:
1. Xem logs trong `storage/logs/`
2. Chạy `tools/system_check.php`
3. Kiểm tra lỗi trong browser console (F12)
4. Xem tài liệu trong folder `docs/`

---

**Bản cập nhật:** 2024-05-14  
**Phiên bản:** 1.0
