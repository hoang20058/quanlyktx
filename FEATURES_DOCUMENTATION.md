# Tài liệu Chi tiết Các Chức năng Hệ thống Quản lý Ký túc xá

## I. CẤU TRÚC THƯ MỤC DỰ ÁN

```
quanlyktx/
├── api/                          # API endpoints (JSON responses)
│   ├── bills/                   # Quản lý hóa đơn tiện ích
│   ├── contracts/               # Quản lý hợp đồng
│   ├── notices/                 # Quản lý thông báo
│   ├── rooms/                   # Quản lý phòng
│   └── students/                # Quản lý sinh viên
├── models/                       # Data access layer (Repositories)
├── public/                       # Public & Admin UI
│   ├── admin/                   # Trang quản trị viên
│   ├── index.php                # Trang chủ
│   ├── login.php                # Đăng nhập
│   ├── register.php             # Đăng ký
│   ├── bill-inquiry.php         # Tra cứu hóa đơn
│   └── contact.php              # Liên hệ
├── views/                        # HTML templates
│   └── partials/                # Reusable components
├── assets/                       # CSS, JS, Images
├── config/                       # Configuration files
├── core/                         # Core utilities
│   ├── Database.php             # Database connection
│   ├── Security.php             # Security utilities
│   └── Api.php                  # API response handler
└── database/                     # Database files
```

---

## II. DANH SÁCH CÁC CHỨC NĂNG CHÍNH

### 🔴 **1. QUẢN LÝ SINH VIÊN**
**Mô tả:** Quản lý thông tin sinh viên, đăng ký, duyệt, và trạng thái học.

#### 📂 Folder & Files:
- **Models:** `models/StudentRepository.php`
- **API:** `api/students/` (save.php, delete.php, approve.php, by-room.php)
- **View:** `public/admin/students.php`

#### 📋 Các Hàm/Method:

| Hàm | Chức năng |
|-----|----------|
| `StudentRepository::all()` | Lấy toàn bộ sinh viên (bao gồm phòng và trạng thái) |
| `StudentRepository::find($id)` | Lấy chi tiết 1 sinh viên |
| `StudentRepository::save($data)` | Thêm hoặc sửa sinh viên |
| `StudentRepository::delete($id)` | Xóa sinh viên |
| `StudentRepository::register($data)` | Đăng ký sinh viên mới (status = "Chờ duyệt") |
| `StudentRepository::validate($data)` | Validate email và mã sinh viên không trùng |
| `StudentRepository::isStudentCodeDuplicate($code)` | Kiểm tra mã sinh viên trùng |
| `StudentRepository::isValidEmail($email)` | Validate email format |
| `StudentRepository::transferRoom($studentId, $roomId)` | Chuyển phòng sinh viên + kiểm tra sức chứa |
| `StudentRepository::currentContract($studentId)` | Lấy hợp đồng hiện tại của sinh viên |
| `StudentRepository::registrationStats()` | Thống kê: chờ duyệt, đang ở, đã chuyển đi |
| `StudentRepository::topStudents($limit)` | Lấy top sinh viên có điểm cao nhất |
| `StudentRepository::priorityDistribution()` | Phân bố sinh viên theo mức ưu tiên (1-8) |
| `StudentRepository::lowScoringStudents($threshold)` | Lấy sinh viên có điểm < threshold |

#### 🔗 API Endpoints:

| Endpoint | Method | Chức năng |
|----------|--------|----------|
| `/api/students/save.php` | POST | Thêm/sửa sinh viên (admin) |
| `/api/students/delete.php` | POST | Xóa sinh viên (admin) |
| `/api/students/approve.php` | POST | Duyệt hồ sơ sinh viên chờ duyệt |
| `/api/students/by-room.php` | GET | Lấy danh sách sinh viên trong phòng (dùng cho thông báo) |

#### 📊 Admin Pages:
- **`public/admin/students.php`:**
  - Tab "Sinh viên đang ở": Hiển thị danh sách sinh viên đang ở
    - Cột: Mã SV, Họ tên, Ngành, Phòng, Trạng thái, Điểm
    - Thao tác: Sửa, Xóa, Chuyển phòng
  - Tab "Hồ sơ chờ duyệt": Danh sách sinh viên chờ duyệt
    - Cột: Mã SV, Họ tên, Ngành, **Ưu tiên** (badge)
    - Thao tác: Duyệt

#### 🔑 Key Features:
- ✅ Validate email và mã sinh viên không trùng
- ✅ Hệ thống ưu tiên (priority_level 1-8)
- ✅ Chuyển phòng có kiểm tra sức chứa
- ✅ Trạng thái: Chờ duyệt → Đang ở → Đã chuyển đi
- ✅ Điểm nội trú (boarding_score) 0-100

---

### 🏠 **2. QUẢN LÝ PHÒNG**
**Mô tả:** Quản lý thông tin phòng, sức chứa, giá thuê, trạng thái hoạt động.

#### 📂 Folder & Files:
- **Models:** `models/RoomRepository.php`
- **API:** `api/rooms/` (save.php, delete.php, switch.php)
- **View:** `public/admin/rooms.php`, `public/admin/room.php` (chi tiết)

#### 📋 Các Hàm/Method:

| Hàm | Chức năng |
|-----|----------|
| `RoomRepository::all()` | Lấy tất cả phòng + số người ở + điểm trung bình |
| `RoomRepository::find($id)` | Lấy chi tiết 1 phòng |
| `RoomRepository::save($data)` | Thêm hoặc sửa phòng |
| `RoomRepository::delete($id)` | Xóa phòng |
| `RoomRepository::selectOptions()` | Lấy danh sách phòng để dropdown |
| `RoomRepository::occupiedSelectOptions()` | Lấy danh sách phòng đang có người ở |
| `RoomRepository::stats()` | Thống kê: tổng phòng, phòng hoạt động, sức chứa, đã thuê |
| `RoomRepository::topRooms($limit)` | Lấy top phòng đông người nhất |
| `RoomRepository::studentsByRoom($roomId)` | Lấy danh sách sinh viên trong phòng |
| `RoomRepository::roomStatusDistribution()` | Phân bố phòng: trống, có người, đầy, đang sửa |
| `RoomRepository::getOccupancy($roomId)` | Lấy số người đang ở trong phòng |

#### 🔗 API Endpoints:

| Endpoint | Method | Chức năng |
|----------|--------|----------|
| `/api/rooms/save.php` | POST | Thêm/sửa phòng (admin) |
| `/api/rooms/delete.php` | POST | Xóa phòng (admin) |
| `/api/rooms/switch.php` | POST | Chuyển sinh viên sang phòng khác + kiểm tra sức chứa |

#### 📊 Admin Pages:
- **`public/admin/rooms.php`:**
  - Hiển thị danh sách phòng (DataTable)
  - Cột: Phòng, Tầng, Sức chứa, Loại, Giá/tháng, Trạng thái, Người ở, Điểm TB
  - Thao tác: Xem chi tiết, Sửa, Xóa

- **`public/admin/room.php`:**
  - Chi tiết phòng: Thông tin, danh sách sinh viên trong phòng, hóa đơn

#### 🔑 Key Features:
- ✅ Phòng có sức chứa (capacity): Hệ thống kiểm tra không vượt quá
- ✅ Trạng thái phòng: Hoạt động, Đang sửa chữa
- ✅ Loại phòng: Thường, VIP, ...
- ✅ Giá phòng theo tháng
- ✅ Hiển thị số người ở + điểm trung bình

---

### 📋 **3. QUẢN LÝ HỢP ĐỒNG**
**Mô tả:** Quản lý hợp đồng ký túc xá, thanh toán, kỳ hạn, giảm giá theo ưu tiên.

#### 📂 Folder & Files:
- **Models:** `models/ContractRepository.php`
- **API:** `api/contracts/` (save.php, delete.php, pay.php, extend.php, terminate.php)
- **View:** `public/admin/contracts.php`, `public/admin/contract-detail.php`

#### 📋 Các Hàm/Method:

| Hàm | Chức năng |
|-----|----------|
| `ContractRepository::all()` | Lấy tất cả hợp đồng + thông tin thanh toán |
| `ContractRepository::find($id)` | Lấy chi tiết 1 hợp đồng |
| `ContractRepository::activeByStudent($studentId)` | Lấy hợp đồng hiện tại của sinh viên |
| `ContractRepository::activeContracts()` | Lấy hợp đồng đang hoạt động |
| `ContractRepository::studentsWithDebt()` | Lấy sinh viên có nợ (thanh toán < phải trả) |
| `ContractRepository::save($data)` | Tạo hợp đồng mới |
| `ContractRepository::delete($id)` | Xóa hợp đồng |
| `ContractRepository::addPayment($contractId, $amount)` | Thêm thanh toán cho hợp đồng |
| `ContractRepository::calculateRoomFee()` | Tính tiền phòng theo ngày, có giảm giá ưu tiên |
| `ContractRepository::getDiscountByPriority($level)` | Lấy % giảm giá theo mức ưu tiên |

#### 🔗 API Endpoints:

| Endpoint | Method | Chức năng |
|----------|--------|----------|
| `/api/contracts/save.php` | POST | Tạo hợp đồng mới (admin) |
| `/api/contracts/delete.php` | POST | Xóa hợp đồng (admin) |
| `/api/contracts/pay.php` | POST | Ghi nhận thanh toán hợp đồng |
| `/api/contracts/extend.php` | POST | Gia hạn hợp đồng |
| `/api/contracts/terminate.php` | POST | Chấm dứt hợp đồng |

#### 📊 Admin Pages:
- **`public/admin/contracts.php`:**
  - Danh sách hợp đồng (DataTable)
  - Cột: Mã SV, Sinh viên, Phòng, Ngày bắt đầu, Ngày kết thúc, Tiền phòng, Đã thanh toán, Còn nợ, Trạng thái

- **`public/admin/contract-detail.php`:**
  - Chi tiết hợp đồng
  - Thanh toán trực tuyến (form thêm thanh toán)
  - Danh sách hóa đơn tiện ích liên quan

#### 🔑 Key Features:
- ✅ Tính tiền phòng động: theo ngày, áp dụng giảm giá ưu tiên
- ✅ Giảm giá theo priority_level: 1-3 (cao) giảm nhiều, 7-8 (thấp) giảm ít
- ✅ Hệ thống theo dõi nợ (thanh toán vs phải trả)
- ✅ Gia hạn hợp đồng, chấm dứt hợp đồng
- ✅ Lịch sử thanh toán

---

### 💰 **4. QUẢN LÝ HÓA ĐƠN TIỆN ÍCH (Nước + Điện)**
**Mô tả:** Quản lý chi phí nước, điện, tính tiền theo chỉ số mét, thanh toán.

#### 📂 Folder & Files:
- **Models:** `models/UtilityBillRepository.php`
- **API:** `api/bills/` (save.php, delete.php, meter-reading.php, mark-paid.php)
- **View:** `public/admin/bills.php`, `public/admin/meter-reading.php`

#### 📋 Các Hàm/Method:

| Hàm | Chức năng |
|-----|----------|
| `UtilityBillRepository::all()` | Lấy tất cả hóa đơn tiện ích |
| `UtilityBillRepository::find($id)` | Lấy chi tiết 1 hóa đơn |
| `UtilityBillRepository::save($data)` | Tạo/sửa hóa đơn |
| `UtilityBillRepository::delete($id)` | Xóa hóa đơn |
| `UtilityBillRepository::existsForRoomAndMonthYear($roomId, $month, $year)` | Kiểm tra hóa đơn tháng/năm của phòng |
| `UtilityBillRepository::unpaidBills()` | Lấy hóa đơn chưa thanh toán |
| `UtilityBillRepository::billsByRoom($roomId)` | Lấy hóa đơn của 1 phòng |

#### 🔗 API Endpoints:

| Endpoint | Method | Chức năng |
|----------|--------|----------|
| `/api/bills/save.php` | POST | Tạo/sửa hóa đơn (admin) |
| `/api/bills/delete.php` | POST | Xóa hóa đơn (admin) |
| `/api/bills/meter-reading.php` | POST | Ghi chỉ số mét nước/điện |
| `/api/bills/mark-paid.php` | POST | Đánh dấu hóa đơn đã thanh toán |

#### 📊 Admin Pages:
- **`public/admin/bills.php`:**
  - Danh sách hóa đơn (DataTable)
  - Cột: Phòng, Tháng/Năm, Nước, Điện, Tổng, Thanh toán, Còn nợ, Trạng thái
  - Thao tác: Xem, Sửa, Xóa, Đánh dấu đã thanh toán

- **`public/admin/meter-reading.php`:**
  - Ghi chỉ số mét nước + điện
  - Chọn phòng → Nhập chỉ số cũ, chỉ số mới
  - Tự động tính: Lượng tiêu thụ, tiền

#### 🔑 Key Features:
- ✅ Tính tiền nước/điện theo chỉ số mét
- ✅ Hỗ trợ nước và điện riêng biệt
- ✅ Ghi chỉ số từng tháng
- ✅ Theo dõi thanh toán

---

### 📢 **5. QUẢN LÝ THÔNG BÁO / KHEN THƯỞNG / KỶ LUẬT**
**Mô tả:** Gửi thông báo cho sinh viên/phòng, cộng/trừ điểm nội trú.

#### 📂 Folder & Files:
- **Models:** `models/NoticeRepository.php`
- **API:** `api/notices/` (save.php, delete.php)
- **View:** `public/admin/notices.php`

#### 📋 Các Hàm/Method:

| Hàm | Chức năng |
|-----|----------|
| `NoticeRepository::all()` | Lấy tất cả thông báo + tên sinh viên/phòng |
| `NoticeRepository::find($id)` | Lấy chi tiết 1 thông báo |
| `NoticeRepository::save($data)` | Tạo/sửa thông báo + tự động cộng/trừ điểm |
| `NoticeRepository::delete($id)` | Xóa thông báo + revert điểm |
| `NoticeRepository::applyPointChange($targetType, $pointChange, $roomId, $studentId)` | Cộng/trừ điểm sinh viên theo đối tượng |

#### 🔗 API Endpoints:

| Endpoint | Method | Chức năng |
|----------|--------|----------|
| `/api/notices/save.php` | POST | Tạo/sửa thông báo (admin) |
| `/api/notices/delete.php` | POST | Xóa thông báo (admin) |

#### 📊 Admin Pages:
- **`public/admin/notices.php`:**
  - Danh sách thông báo (DataTable)
  - Cột: Ngày, Loại (Thông báo/Khen/Kỷ luật), Đối tượng, Điểm, Nội dung
  - Thao tác: Sửa, Xóa
  - Form thêm/sửa:
    - Đối tượng: Cả tòa / Phòng / Cá nhân
    - Loại: Thông báo chung / Khen thưởng / Kỷ luật
    - Điểm thay đổi: +/- điểm nội trú
    - Phòng & Sinh viên: Tùy chọn

#### 🔑 Key Features:
- ✅ 3 loại thông báo: Thông báo, Khen thưởng, Kỷ luật
- ✅ 3 đối tượng: Cả tòa, Phòng, Cá nhân
- ✅ Tự động cộng/trừ điểm nội trú
- ✅ Xóa thông báo tự động revert điểm

---

### 📊 **6. DASHBOARD / THỐNG KÊ**
**Mô tả:** Hiển thị dashboard tổng quan cho quản trị viên.

#### 📂 Folder & Files:
- **View:** `public/admin/index.php` (Dashboard)
- **Analytics:** `public/admin/analytics.php` (Chi tiết thống kê)

#### 📊 Admin Pages:
- **`public/admin/index.php`:**
  - Thẻ thống kê: Sinh viên chờ duyệt, Đang ở, Phòng hoạt động, Hóa đơn chưa thanh toán
  - Top phòng đông người nhất
  - Top sinh viên điểm cao nhất
  - Phân bố ưu tiên
  - Phân bố điểm thấp

- **`public/admin/analytics.php`:**
  - Biểu đồ thống kê chi tiết
  - Phân bố phòng: Trống, Có người, Đầy, Đang sửa
  - Phân bố sinh viên theo ưu tiên
  - Danh sách sinh viên điểm thấp

---

### 🔐 **7. QUẢN LÝ TÀI KHOẢN & XÁC THỰC**
**Mô tả:** Đăng nhập, đăng ký, phân quyền (admin/user).

#### 📂 Folder & Files:
- **Core:** `core/Security.php` (Xác thực, phân quyền)
- **View:** 
  - `public/login.php` (Đăng nhập public)
  - `public/register.php` (Đăng ký)
  - `public/admin/login.php` (Đăng nhập admin)
  - `public/logout.php` (Đăng xuất)

#### 🔑 Security Features:
- ✅ Password hashing (bcrypt)
- ✅ CSRF Token protection
- ✅ Session management
- ✅ Role-based access (Admin vs User)
- ✅ SQL Injection prevention (Prepared statements)

---

### 🔍 **8. TRA CỨU THÔNG TIN (Cho Sinh viên)**
**Mô tả:** Sinh viên có thể tra cứu hóa đơn, thông báo của mình.

#### 📂 Folder & Files:
- **View:**
  - `public/bill-inquiry.php` (Tra cứu hóa đơn)
  - `public/contact.php` (Liên hệ)
  - `public/index.php` (Trang chủ)

#### 📊 Public Pages:
- **`public/index.php`:** Trang chủ, giới thiệu hệ thống
- **`public/bill-inquiry.php`:** Sinh viên nhập mã để tra cứu hóa đơn
- **`public/contact.php`:** Form liên hệ

---

## III. DATABASE SCHEMA

### 📦 5 Bảng chính:

1. **Student** (Sinh viên)
   - student_id, full_name, student_code, dob, phone, email, department
   - status (Chờ duyệt / Đang ở / Đã chuyển đi)
   - priority_level (1-8), boarding_score (0-100)

2. **Room** (Phòng)
   - room_id, room_number, floor_number, capacity, room_type
   - status (Hoạt động / Đang sửa chữa), price

3. **Contract** (Hợp đồng)
   - contract_id, student_id, room_id
   - start_date, end_date, status (Đang ở / Kết thúc)
   - paid_amount, discount_percent

4. **UtilityBill** (Hóa đơn nước/điện)
   - bill_id, room_id, month, year
   - water_used, water_cost, electricity_used, electricity_cost
   - total_cost, paid_amount, paid_date

5. **Notice** (Thông báo)
   - notice_id, target_type (Cả tòa / Phòng / Cá nhân)
   - category (Thông báo / Khen / Kỷ luật)
   - room_id, student_id, point_change, description, date

---

## IV. CORE UTILITIES

### `core/Security.php`
- `Security::requireAuth()` - Yêu cầu đăng nhập
- `Security::requireAdminAuth()` - Yêu cầu admin
- `Security::e($text)` - HTML escape
- `Security::hashPassword($pwd)` - Hash password
- `Security::verifyPassword($pwd, $hash)` - Verify password

### `core/Api.php`
- `Api::json($data, $code)` - Return JSON response
- `Api::input()` - Get POST data
- `Api::requireCsrf($data)` - Check CSRF token

### `core/Database.php`
- `Database::connection()` - Get PDO connection

---

## V. TÓM TẮT LUỒNG DỮ LIỆU

### Thêm sinh viên:
1. Admin mở `public/admin/students.php`
2. Nhấn "Thêm sinh viên"
3. Form POST → `api/students/save.php`
4. `StudentRepository::save()` → Database
5. Response OK → Page reload

### Duyệt hồ sơ:
1. Hồ sơ chờ duyệt → Nhấn "Duyệt"
2. POST → `api/students/approve.php`
3. Cập nhật `Student.status = 'Đang ở'`
4. Tạo `Contract` mới (nếu cần)
5. Response OK → Page reload

### Chuyển phòng:
1. Nhấn "Chuyển phòng" trên sinh viên
2. Chọn phòng mới
3. POST → `api/rooms/switch.php`
4. **Kiểm tra sức chứa:** `RoomRepository::getOccupancy()` vs `room.capacity`
5. Nếu đủ chỗ → `StudentRepository::transferRoom()` cập nhật `Contract.room_id`
6. Nếu đầy → Return error

### Tạo thông báo:
1. Form thêm thông báo
2. Chọn: Đối tượng, Loại, Điểm, Phòng/Sinh viên, Nội dung
3. POST → `api/notices/save.php`
4. `NoticeRepository::save()` lưu notice
5. `NoticeRepository::applyPointChange()` **cộng/trừ điểm theo đối tượng:**
   - Cá nhân: Cộng cho 1 sinh viên
   - Phòng: Cộng cho tất cả sinh viên trong phòng
   - Cả tòa: Cộng cho tất cả sinh viên đang ở

### Ghi chỉ số mét:
1. Admin vào `public/admin/meter-reading.php`
2. Chọn phòng, nhập chỉ số cũ/mới (nước + điện)
3. POST → `api/bills/meter-reading.php`
4. Tính: Lượng tiêu thụ = chỉ số mới - chỉ số cũ
5. Tính tiền: Lượng × giá mỗi đơn vị
6. Tạo `UtilityBill` record

### Thanh toán hợp đồng:
1. Admin xem `public/admin/contract-detail.php`
2. Form thêm thanh toán (nhập số tiền)
3. POST → `api/contracts/pay.php`
4. Cập nhật `Contract.paid_amount += amount`
5. Tính nợ: Tiền phòng - Đã thanh toán

---

## VI. MÀU SẮC & THÀNH PHẦN UI

### Badge ưu tiên (Priority Level):
- 🔴 **Đỏ (1-3):** Ưu tiên cao → Giảm giá 30%
- 🟡 **Vàng (4-6):** Ưu tiên trung → Giảm giá 15%
- ⚫ **Xám (7-8):** Ưu tiên thấp → Giảm giá 0%

### Trạng thái:
- 🟢 Đang ở / Hoạt động
- 🟡 Chờ duyệt / Đang sửa chữa
- 🔴 Kết thúc / Đã chuyển đi

---

## VII. CHỨC NĂNG NÂNG CAO

1. **Tính tiền phòng động:**
   - `ContractRepository::calculateRoomFee()` - Tính theo ngày + giảm giá ưu tiên

2. **Kiểm tra sức chứa phòng:**
   - `RoomRepository::getOccupancy()` - Kiểm tra trước khi chuyển

3. **Hệ thống theo dõi nợ:**
   - `ContractRepository::studentsWithDebt()` - Lấy sinh viên có nợ

4. **Thống kê đa chiều:**
   - Phân bố phòng, ưu tiên, điểm, nợ
   - Top phòng, top sinh viên

5. **CSRF Protection:**
   - `Security::generateCsrfToken()` trên mỗi form
   - `Api::requireCsrf()` trên mỗi POST endpoint

---

## VIII. KẾT LUẬN

Hệ thống Quản lý Ký túc xá là một ứng dụng PHP thuần không dùng framework, tổ chức theo mô hình:
- **MVC:** Models (Repositories) + Views (PHP templates) + Controllers (API endpoints)
- **RESTful API:** JSON responses, POST/GET requests
- **Security:** CSRF tokens, Prepared statements, Password hashing, Session management
- **Database:** PDO với 5 bảng chính

**Các chức năng chính:** Quản lý sinh viên, phòng, hợp đồng, hóa đơn, thông báo, thống kê, và kiểm soát truy cập.
