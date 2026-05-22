# Kiến Trúc Hệ Thống - Quản Lý Ký Túc Xá

## 🏗️ Tổng Quan

Hệ thống Quản Lý Ký Túc Xá được xây dựng theo mô hình **MVC (Model-View-Controller)** với pure PHP, không sử dụng framework. Đây là cách tiếp cận giáo dục tốt để hiểu rõ cơ chế hoạt động của web framework.

```
Request (HTTP GET/POST)
    ↓
Public Page / API Endpoint (Controller)
    ↓
Security & Validation (Middleware)
    ↓
Repository (Model)
    ↓
Database (MySQL)
    ↓
Response (HTML / JSON)
```

## 📚 Thành Phần Chính

### 1️⃣ **Core Layer** (`core/`)

Các lớp cốt lõi của ứng dụng:

| Lớp | Chức Năng |
|-----|----------|
| **Database.php** | Singleton PDO connection, prepared statements |
| **Security.php** | Session, authentication, authorization, XSS protection |
| **Api.php** | JSON response formatting, CSRF protection (removed), input parsing |
| **Env.php** | Load .env file, manage environment variables |
| **Helpers.php** | Utility functions (getPriorityDescription) |

**Cách sử dụng:**
```php
// Kết nối database
$db = Database::connection();
$stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$id]);

// Xác thực
Security::requireAdminAuth();

// Phản hồi API
Api::json(['ok' => true, 'data' => $student]);
```

### 2️⃣ **Model Layer** (`models/`)

Lớp Repository - truy cập dữ liệu:

| Repository | Bảng | Trách Nhiệm |
|------------|------|-------------|
| **StudentRepository** | Student | CRUD sinh viên, xếp hạng, bộ lọc |
| **RoomRepository** | Room | CRUD phòng, thống kê, trạng thái |
| **ContractRepository** | Contract | CRUD hợp đồng, sinh viên-phòng mapping |
| **UtilityBillRepository** | UtilityBill | CRUD hóa đơn, tracking thanh toán |
| **NoticeRepository** | Notice | CRUD thông báo, áp dụng điểm |

**Mô hình Repository:**
```php
// ✅ Tốt: Sử dụng Repository
$student = StudentRepository::find($id);
$student['boarding_score'] -= 5;
StudentRepository::save($student);

// ❌ Tệ: SQL trực tiếp trong controller
$db = new PDO(...);
$db->query("UPDATE students SET boarding_score = ...");
```

### 3️⃣ **View Layer** (`public/`, `views/`)

**Public Pages:**
- `public/index.php` - Trang chủ (danh sách phòng, thông báo, xếp hạng)
- `public/login.php` - Đăng nhập admin
- `public/register.php` - Đăng ký sinh viên
- `public/contact.php` - Form liên hệ

**Admin Pages:**
- `public/admin/index.php` - Dashboard (thống kê, cảnh báo)
- `public/admin/students.php` - Quản lý sinh viên
- `public/admin/rooms.php` - Quản lý phòng
- `public/admin/contracts.php` - Quản lý hợp đồng
- `public/admin/bills.php` - Quản lý hóa đơn
- `public/admin/analytics.php` - Biểu đồ thống kê

**Template Components** (`views/partials/`):
- `admin_header.php` - Sidebar + topbar
- `admin_footer.php` - Đóng divs, script loading
- `public_header.php` - Navbar công khai
- `public_footer.php` - Footer công khai

### 4️⃣ **API Layer** (`api/`)

REST endpoints trả về JSON:

```
POST /api/students/save.php       → Create/update student
POST /api/students/delete.php     → Delete student
POST /api/rooms/switch.php        → Transfer student to room
POST /api/bills/mark-paid.php     → Mark bill as paid
...
```

**Pattern API Endpoint:**
```php
<?php
// 1. Load config
require_once '../../../config/app.php';

// 2. Require authentication
Security::requireAdminAuth();

// 3. Parse input
$data = Api::input();

// 4. Validate
(new Validator($data))
    ->required('name')
    ->email('email')
    ->validate();

// 5. Call repository
$id = StudentRepository::save($data);

// 6. Return response
Api::json(['ok' => true, 'id' => $id]);
```

### 5️⃣ **Database Layer** (`database/`)

**Schema (5 tables):**

```sql
Student
├── id (PK)
├── code (unique)
├── email (unique)
├── full_name
├── boarding_score (0-100)
├── priority_level (1-8)
└── status (Chờ duyệt, Đang ở, Đã thoát)

Room
├── id (PK)
├── room_number (unique)
├── max_occupancy
├── current_occupancy
├── status (Còn trống, Đang ở, Đầy, Sửa chữa)
└── created_at

Contract
├── id (PK)
├── student_id (FK)
├── room_id (FK)
├── start_date
├── end_date
└── status (Chưa bắt đầu, Đang ở, Đã kết thúc)

UtilityBill
├── id (PK)
├── contract_id (FK)
├── month / year
├── water_usage
├── electric_usage
├── amount
├── status (Chưa thanh toán, Đã thanh toán)
└── created_at

Notice
├── id (PK)
├── title
├── content
├── status (draft, published)
├── point_change (mức điểm thay đổi)
└── created_at
```

### 6️⃣ **Validation Layer** (`src/Validation/`)

Lớp Validator kiểm tra đầu vào:

```php
$validator = new Validator($_POST);
$validator->required('name')
          ->string('email')
          ->email('email')
          ->minLength('password', 6)
          ->numeric('age')
          ->validate(); // Throw ValidationException if errors

// Hoặc kiểm tra lỗi
if ($validator->hasErrors()) {
    $errors = $validator->getErrors();
    // Display errors
}
```

### 7️⃣ **Exception Handling** (`src/Exceptions/`)

Các lớp ngoại lệ tùy chỉnh:

```php
// ApplicationException - base class
throw new ApplicationException("Error", 500, "User message");

// ValidationException - validation fails
throw new ValidationException(['email' => ['Invalid email']]);

// DatabaseException - DB operation fails
throw new DatabaseException("Connection failed");

// AuthenticationException - auth fails
throw new AuthenticationException("Invalid credentials");

// NotFoundException - resource not found
throw new NotFoundException("Student", 5);
```

## 🔄 Quy Trình Dữ Liệu

### Ví Dụ: Duyệt Đơn Sinh Viên

```
1. Admin truy cập /public/admin/students.php
   ↓
2. Hiển thị danh sách sinh viên (status='Chờ duyệt')
   StudentRepository::all(['status' => 'Chờ duyệt'])
   ↓
3. Admin click nút "Duyệt"
   ↓
4. Gọi API: POST /api/students/approve.php?id=5
   ↓
5. Server xác thực (Security::requireAdminAuth)
   ↓
6. Cập nhật sinh viên: status='Đang ở', time_updated=now()
   StudentRepository::save(['id'=>5, 'status'=>'Đang ở'])
   ↓
7. Trả về JSON: {ok: true, message: 'Duyệt thành công'}
   ↓
8. JavaScript refresh table (DataTables reload)
```

### Ví Dụ: Chuyển Phòng Sinh Viên

```
1. Admin chọn sinh viên & phòng mới
   ↓
2. Gọi API: POST /api/rooms/switch.php
   ↓
3. Validate:
   - Sinh viên tồn tại?
   - Phòng tồn tại?
   - Phòng chưa đầy? (occupancy < max_occupancy)
   ↓
4. Database transaction:
   - Cập nhật Contract: room_id = new_room_id
   - Cập nhật Room: occupancy -= 1 (phòng cũ), += 1 (phòng mới)
   ↓
5. Trả về: {ok: true, new_room: 'A101'}
   ↓
6. UI cập nhật và hiển thị success message
```

## 🔒 Security Layers

### Layer 1: Input Validation
```php
// Kiểm tra dữ liệu trước khi lưu
$validator->required('name')->email('email')->validate();
```

### Layer 2: SQL Injection Prevention
```php
// ✅ Tốt: Prepared statements
$stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$id]);

// ❌ Tệ: SQL string concat
$stmt = $db->query("SELECT * FROM students WHERE id = " . $id);
```

### Layer 3: XSS Prevention
```php
// ✅ Tốt: Escape HTML output
<h1><?= Security::e($student['full_name']) ?></h1>

// ❌ Tệ: Direct output
<h1><?= $student['full_name'] ?></h1>
```

### Layer 4: Authentication
```php
// Admin-only pages
Security::requireAdminAuth();

// Sinh viên pages
Security::requireAuth();
```

### Layer 5: Session Security
```php
// HTTPOnly + Secure + SameSite cookies
session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']),
    'samesite' => 'Lax',
]);
```

## 📊 Data Flow Diagram

```
┌─────────────────────────────────────────┐
│        Browser (User Interface)         │
│  - Public pages (login, register)       │
│  - Admin dashboard (CRUD operations)    │
└──────────────┬──────────────────────────┘
               │
               ↓ HTTP Request
        ┌─────────────────────┐
        │  Public/Admin Page  │
        │  - Form submission  │
        │  - AJAX calls       │
        └──────────┬──────────┘
                   │
                   ↓ POST/GET
        ┌──────────────────────┐
        │   API Endpoint       │
        │  api/students/save   │
        │  api/rooms/switch    │
        └──────────┬───────────┘
                   │
                   ↓ Validate
        ┌──────────────────────┐
        │  Validator Class     │
        │  - Check required    │
        │  - Validate format   │
        └──────────┬───────────┘
                   │
                   ↓ Security
        ┌──────────────────────┐
        │ Security::require*   │
        │  - Check auth        │
        │  - Check admin       │
        └──────────┬───────────┘
                   │
                   ↓ Process
        ┌──────────────────────┐
        │   Repository Class   │
        │  StudentRepository   │
        │  RoomRepository      │
        └──────────┬───────────┘
                   │
                   ↓ SQL Query
        ┌──────────────────────┐
        │    Database (MySQL)  │
        │  - Prepared statement│
        │  - Return data       │
        └──────────┬───────────┘
                   │
                   ↓ Response
        ┌──────────────────────┐
        │    JSON Response     │
        │  {ok, data, message} │
        └──────────┬───────────┘
                   │
                   ↓ Browser
        ┌──────────────────────┐
        │  Display/Update UI   │
        └──────────────────────┘
```

## 🎯 Design Patterns Used

1. **Repository Pattern** - Data access abstraction
2. **Singleton Pattern** - Database connection (Database::connection)
3. **MVC Pattern** - Model-View-Controller separation
4. **Exception Handling** - Custom exception hierarchy
5. **Validation Chain** - Fluent validator interface
6. **Config Pattern** - Centralized configuration

## ⚡ Performance Considerations

- ✅ **Database Queries:** Optimized with indexes on PK/FK
- ✅ **Session:** HTTPOnly cookies, no data in URL
- ✅ **Frontend:** Bootstrap 5 (minimal CSS), DataTables (efficient sorting)
- ✅ **Prepared Statements:** No SQL concat, prevents injection
- ✅ **Caching:** Not needed for small system

## 🔧 Extensibility

Para thêm tính năng mới:

1. **Thêm bảng mới:**
   - Thêm DDL vào `database/schema.sql`
   - Tạo `models/NewRepository.php`

2. **Thêm API endpoint:**
   - Tạo `api/domain/action.php`
   - Theo pattern hiện tại (validate → auth → repository → json)

3. **Thêm admin page:**
   - Tạo `public/admin/new-page.php`
   - Sử dụng `views/partials/admin_header.php` & `admin_footer.php`

4. **Thêm validation rule:**
   - Thêm method vào `src/Validation/Validator.php`

---

**Phiên bản:** 1.0  
**Cập nhật:** 2024-05-14
