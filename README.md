# Quan ly ky tuc xa

Du an PHP thuan theo phong cach **Modern Procedural PHP / Page Controller / Top-Heavy**.

## Cau truc dang chay

- `public/`: cac page controller public va admin. Moi page tu nap config, dinh nghia ham `fetch...()` / `handle...()`, xu ly POST, sau do moi render HTML.
- `views/partials/`: layout chung cho public va admin.
- `core/`: lop nen tang dang dung nhu `Database`, `Security`, `Env`, `Helpers`.
- `config/app.php`: nap `.env`, ket noi cac core class va start session.
- `assets/`: CSS va JavaScript dung chung. Script rieng cua page nam trong chinh page do.
- `database/`: schema, seed va script import database.
- `legacy_reference/`: code va tai lieu cu de doi chieu, khong nam trong luong chay chinh.

## Luong xu ly chinh

1. Nguoi dung mo mot file trong `public/`.
2. Page nap `config/app.php`, kiem tra quyen neu la trang admin.
3. Cac truy van doc du lieu nam trong ham `fetch...()`.
4. Cac thao tac them/sua/xoa nam trong ham `handle...()`.
5. Form gui POST ve chinh page bang truong `action`.
6. Xu ly xong thi redirect kem flash message de tranh submit lai khi F5.
7. HTML chi render sau khi du lieu da duoc chuan bi.

## Quy uoc nghiep vu hien tai

- Hop dong chi ghi nhan sinh vien, phong, ngay vao, ngay ra va trang thai.
- Hoa don la noi duy nhat quan ly tien can thu va trang thai thanh toan.
- Nhap chi so dien nuoc tao mot hoa don cho tung phong theo tung thang, khong cong don vao hop dong.

## Cac trang chinh

- Public:
  - `public/index.php`
  - `public/register.php`
  - `public/bill-inquiry.php`
  - `public/contact.php`
- Admin:
  - `public/admin/index.php`
  - `public/admin/rooms.php`
  - `public/admin/students.php`
  - `public/admin/contracts.php`
  - `public/admin/contract-detail.php`
  - `public/admin/bills.php`
  - `public/admin/meter-reading.php`
  - `public/admin/notices.php`

Trang `public/admin/analytics.php` da duoc bo khoi menu va chi redirect ve Dashboard.

## Cai dat nhanh

1. Dat thu muc trong `htdocs`, vi du:

```powershell
D:\Programs\XAMPP\htdocs\quanlyktx
```

2. Bat Apache va MySQL trong XAMPP.
3. Cau hinh `.env`:

```dotenv
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=quanlyktx
DB_USER=root
DB_PASS=
APP_URL=
APP_TIMEZONE=Asia/Ho_Chi_Minh
```

4. Import database:

```powershell
& 'D:\Programs\XAMPP\php\php.exe' 'D:\Programs\XAMPP\htdocs\quanlyktx\database\import_schema.php'
```

5. Nap du lieu mau:

```powershell
& 'D:\Programs\XAMPP\php\php.exe' 'D:\Programs\XAMPP\htdocs\quanlyktx\database\seed.php'
```

## Tai khoan demo

- Username: `admin`
- Password: `admin`

## Ghi chu sau khi clean

- `models/` va `api/` cu da duoc chuyen vao `legacy_reference/` vi giao dien hien tai khong con goi repository/API endpoint nua.
- Cac tai lieu cu trong `docs/`, `system_analysis/`, bao cao cu va file tham khao hop dong cung nam trong `legacy_reference/`.
- Khi thuyet trinh, nen tap trung vao cac page controller trong `public/`, vi day la luong thuc thi hien tai.
