# Báo cáo tổng hợp hệ thống quản lý ký túc xá

Tài liệu này tổng hợp nội dung từ `README.md`, thư mục `docs/`, thư mục `system_analysis/` và đối chiếu lại với code hiện tại của dự án. Mục tiêu là giúp bạn đọc hiểu được luồng chạy, chức năng, ý nghĩa từng nhóm file và có thể trình bày lại dù chưa học PHP quá sâu.

## 1. Tóm tắt nhanh để báo cáo

Dự án `quanlyktx` là một website quản lý ký túc xá viết bằng PHP thuần, MySQL và Bootstrap 5. Hệ thống không dùng framework lớn như Laravel, nên mỗi phần được tách thủ công:

- `public/`: các trang người dùng nhìn thấy, gồm trang sinh viên và trang admin.
- `api/`: các endpoint nhận thao tác lưu, sửa, xóa từ JavaScript.
- `models/`: lớp Repository, chịu trách nhiệm đọc và ghi database bằng PDO.
- `core/`: các lớp nền tảng như cấu hình, database, session, bảo mật, response JSON.
- `views/partials/`: header, footer dùng lại cho public và admin.
- `assets/`: CSS và JavaScript.
- `database/`: schema và script tạo dữ liệu.
- `docs/`, `system_analysis/`: tài liệu mô tả hệ thống.

Câu nói ngắn gọn khi bảo vệ:

> Người dùng thao tác trên giao diện trong `public/`. Nếu chỉ xem dữ liệu thì trang gọi Repository để lấy dữ liệu và render HTML. Nếu lưu, sửa, xóa thì JavaScript gửi request đến `api/`. API kiểm tra quyền admin, đọc input, gọi Repository. Repository dùng PDO prepared statement để thao tác MySQL. Kết quả trả về là HTML hoặc JSON.

## 2. Luồng chạy tổng quát

Luồng mở một trang:

1. Trình duyệt gọi URL, ví dụ `public/index.php`.
2. File này `require_once config/app.php`.
3. `config/app.php` nạp `.env`, autoload class, tạo hằng số như `APP_URL`, `DB_NAME`, khởi động session.
4. Trang gọi Repository, ví dụ `RoomRepository::all()`, để lấy dữ liệu.
5. Trang include header bằng `views/partials/public_header.php`.
6. PHP trộn dữ liệu với HTML.
7. Trang include footer bằng `views/partials/public_footer.php`.
8. Trình duyệt nhận HTML, CSS, JS và hiển thị.

Luồng lưu dữ liệu admin:

1. Admin mở một trang quản trị, ví dụ `public/admin/rooms.php`.
2. Trang gọi `Security::requireAdminAuth()` để chặn người chưa đăng nhập.
3. Admin điền form và bấm lưu.
4. JavaScript trong `assets/js/app.js` lấy dữ liệu form.
5. JavaScript gọi endpoint, ví dụ `/api/rooms/save.php`.
6. API gọi `Api::boot()`, hàm này nạp config và kiểm tra quyền admin.
7. API đọc input bằng `Api::input()`.
8. API gọi Repository tương ứng, ví dụ `RoomRepository::save($data)`.
9. Repository chạy SQL bằng PDO.
10. API trả JSON `{ "ok": true }`.
11. JavaScript reload trang hoặc thông báo lỗi.

## 3. Lưu ý quan trọng khi đọc tài liệu cũ

Một số tài liệu cũ trong `docs/` nhắc đến CSRF token, password hashing hoặc lớp `Validator` được tích hợp rộng. Code hiện tại có thư mục `src/Validation` và các class exception, nhưng luồng chính hiện đang đơn giản hơn:

- `core/Security.php` hiện tập trung vào session admin, logout, kiểm tra admin và escape HTML.
- `core/Api.php` hiện không bắt CSRF, chỉ nạp config, bắt admin, đọc input và trả JSON.
- Đăng nhập admin trong `public/admin/login.php` đang dùng tài khoản demo `admin/admin`.

Khi báo cáo, nên nói theo code hiện tại để tránh bị hỏi ngược:

> Dự án có tài liệu hướng đến chuẩn bảo mật cao hơn, nhưng phiên bản hiện tại đã rút gọn để phục vụ bài tập lớn. Phần đang chạy chắc chắn gồm session admin, escape output bằng `Security::e()`, PDO prepared statement và phân quyền admin qua `Security::requireAdminAuth()`.

## 4. Database có 5 bảng chính

### 4.1 `Student`

Bảng lưu sinh viên đăng ký và sinh viên đang ở.

Cột quan trọng:

- `student_id`: khóa chính.
- `full_name`: họ tên.
- `student_code`: mã sinh viên, có thể rỗng khi đăng ký public.
- `dob`, `phone`, `email`, `department`: thông tin cá nhân.
- `status`: `Chờ duyệt`, `Đang ở`, `Đã chuyển đi`.
- `priority_level`: mức ưu tiên từ 1 đến 8.
- `boarding_score`: điểm nội trú, mặc định 100.

Ý nghĩa nghiệp vụ: sinh viên public đăng ký sẽ được lưu vào bảng này với trạng thái `Chờ duyệt`. Khi admin duyệt, sinh viên chuyển sang `Đang ở`.

### 4.2 `Room`

Bảng lưu phòng.

Cột quan trọng:

- `room_id`: khóa chính.
- `room_number`: mã phòng, ví dụ tầng 1 phòng 2 thành `102`.
- `floor_number`: số tầng.
- `capacity`: sức chứa.
- `room_type`: `Dịch vụ` hoặc `Thường`.
- `status`: `Hoạt động` hoặc `Đang sửa chữa`.
- `price`: giá phòng theo tháng.
- `room_image_url`: đường dẫn ảnh phòng, hiện không phải luồng chính.

Ý nghĩa nghiệp vụ: phòng là tài nguyên để gán cho sinh viên qua hợp đồng.

### 4.3 `Contract`

Bảng nối sinh viên với phòng.

Cột quan trọng:

- `contract_id`: khóa chính.
- `student_id`: sinh viên nào.
- `room_id`: phòng nào.
- `start_date`, `end_date`: thời gian ở.
- `deposit`: số tiền đã thanh toán.
- `discount_percent`: phần trăm giảm giá.
- `status`: `Đang ở`, `Đã chuyển ra`, `Đã hủy`.

Ý nghĩa nghiệp vụ: không nhìn `Student` để biết sinh viên ở phòng nào, mà nhìn hợp đồng đang ở. Một sinh viên có hợp đồng `Đang ở` thì được xem là đang nội trú.

### 4.4 `UtilityBill`

Bảng lưu hóa đơn điện nước theo phòng và tháng.

Cột quan trọng:

- `bill_id`: khóa chính.
- `room_id`: phòng phát sinh hóa đơn.
- `billing_month`, `billing_year`: tháng/năm.
- `total_amount`: tổng tiền.
- `status`: `Chưa thanh toán` hoặc `Đã thanh toán`.

Ý nghĩa nghiệp vụ: hóa đơn đang gắn theo phòng, không gắn trực tiếp theo từng sinh viên.

### 4.5 `Notice`

Bảng lưu thông báo, khen thưởng, kỷ luật.

Cột quan trọng:

- `notice_id`: khóa chính.
- `target_type`: `Cả tòa`, `Phòng`, `Cá nhân`.
- `category`: `Thông báo chung`, `Khen thưởng`, `Kỷ luật`.
- `point_change`: số điểm cộng hoặc trừ.
- `room_id`, `student_id`: đối tượng liên quan nếu có.
- `description`: nội dung.
- `date`: ngày.

Ý nghĩa nghiệp vụ: nếu thông báo là cá nhân và có điểm thay đổi, hệ thống cập nhật `Student.boarding_score`.

## 5. Giải thích các file nền tảng

### 5.1 `config/app.php`

Đây là file khởi động toàn hệ thống.

Các khối chính:

- `declare(strict_types=1);`: yêu cầu PHP kiểm tra kiểu dữ liệu chặt hơn.
- `require_once core/Env.php`, `Security.php`, `Database.php`: nạp các lớp lõi.
- `Env::load(...)`: đọc biến môi trường từ `.env`.
- `spl_autoload_register(...)`: khi code gọi class như `RoomRepository`, PHP tự tìm file trong `core/`, `models/`, `controllers/`.
- `define('APP_NAME', ...)`: khai báo tên app.
- `define('APP_URL', ...)`: URL public, mặc định `http://localhost/quanlyktx/public`.
- `define('APP_BASE_URL', ...)`: URL gốc dùng để gọi CSS, JS, API.
- `define('DB_HOST'...)`: thông tin database.
- `date_default_timezone_set(APP_TIMEZONE)`: đặt múi giờ.
- `Security::startSession()`: khởi động session.
- Nạp `core/Helpers.php` nếu có.

Nói dễ hiểu: mọi trang đều đi qua `config/app.php` để hệ thống biết database ở đâu, class nằm ở đâu, URL gốc là gì và session đã sẵn sàng chưa.

### 5.2 `core/Env.php`

File này đọc `.env`.

Luồng:

- Nếu file `.env` không tồn tại thì bỏ qua.
- Đọc từng dòng.
- Bỏ qua dòng rỗng, dòng comment `#`, dòng không có dấu `=`.
- Tách `KEY=VALUE`.
- Đưa biến vào `getenv`, `$_ENV`, `$_SERVER`.

Ý nghĩa: thay vì hardcode database vào code, dự án đọc từ `.env`.

### 5.3 `core/Database.php`

File này tạo kết nối PDO đến MySQL.

Khối quan trọng:

- `private static ?PDO $connection = null;`: lưu kết nối dùng lại, không tạo mới nhiều lần.
- `connection(): PDO`: hàm lấy kết nối.
- Nếu đã có kết nối thì trả luôn.
- Nếu chưa có thì đọc host, port, database, username, password.
- Tạo DSN dạng `mysql:host=...;dbname=...;charset=utf8mb4`.
- Tạo `new PDO(...)`.
- Bật `PDO::ERRMODE_EXCEPTION`: lỗi SQL sẽ ném exception.
- Bật `PDO::FETCH_ASSOC`: dữ liệu trả về dạng mảng theo tên cột.
- Tắt emulate prepares để prepared statement an toàn hơn.

Nói dễ hiểu: `Database::connection()` là cánh cửa duy nhất để code nói chuyện với MySQL.

### 5.4 `core/Security.php`

File này xử lý các việc bảo mật cơ bản.

Hàm chính:

- `startSession()`: mở session một lần, đặt cookie `httponly`, `secure`, `samesite`.
- `e(?string $value): string`: escape HTML bằng `htmlspecialchars`, chống XSS khi in dữ liệu ra giao diện.
- `loginAdmin(array $user)`: lưu thông tin admin vào `$_SESSION['admin']`.
- `logout()`: xóa session admin.
- `admin()`: lấy admin hiện tại từ session.
- `isAdmin()`: kiểm tra có admin hợp lệ không.
- `requireAdminAuth()`: nếu chưa đăng nhập admin thì chuyển về `admin/login.php`.

Khi thấy dòng:

```php
<?= Security::e((string) $row['full_name']); ?>
```

hãy hiểu là: in tên sinh viên ra HTML nhưng đã làm sạch ký tự nguy hiểm.

### 5.5 `core/Api.php`

File này giúp endpoint JSON ngắn gọn.

Hàm chính:

- `boot()`: nạp `config/app.php` và yêu cầu admin đăng nhập.
- `json(array $payload, int $statusCode = 200)`: set HTTP status, set header JSON, echo JSON rồi `exit`.
- `input()`: đọc dữ liệu từ `php://input`; nếu là JSON thì decode, nếu không thì dùng `$_POST`.

Mẫu API hiện tại:

```php
Api::boot();
$data = Api::input();
$id = RoomRepository::save($data);
Api::json(['ok' => true, 'id' => $id]);
```

## 6. Giải thích các Repository trong `models/`

Repository là nơi chứa SQL. View và API không nên viết SQL dài trực tiếp mà gọi Repository.

### 6.1 `models/StudentRepository.php`

Vai trò: quản lý dữ liệu sinh viên.

Các hàm chính:

- `all()`: lấy toàn bộ sinh viên, join với `Contract` và `Room` để biết sinh viên đang ở phòng nào. Có cột `display_status`: nếu có hợp đồng đang ở thì hiển thị `Đang ở`, nếu không thì dùng trạng thái trong `Student`.
- `find(int $studentId)`: tìm một sinh viên theo ID.
- `isStudentCodeDuplicate(string $code, int $excludeStudentId = 0)`: kiểm tra trùng mã sinh viên, khi sửa thì bỏ qua chính sinh viên đang sửa.
- `isValidEmail(string $email)`: dùng `filter_var` để kiểm tra email.
- `validate(array $data, int $studentId = 0)`: gom lỗi email và mã sinh viên trùng.
- `save(array $data)`: nếu có `student_id` thì UPDATE, nếu không thì INSERT.
- `delete(int $studentId)`: xóa sinh viên.
- `register(array $data)`: ép `status = 'Chờ duyệt'`, đặt điểm và ưu tiên rồi gọi `save()`.
- `transferRoom(int $studentId, int $newRoomId)`: chuyển phòng bằng cách cập nhật hoặc tạo hợp đồng đang ở.
- `currentContract(int $studentId)`: lấy hợp đồng đang ở hiện tại.
- `registrationStats()`: đếm chờ duyệt, đang ở, đã chuyển đi, điểm cao nhất.
- `topStudents(int $limit = 5)`: lấy sinh viên điểm cao.
- `priorityDistribution()`: đếm sinh viên đang ở theo mức ưu tiên.
- `lowScoringStudents(int $threshold = 50)`: lấy sinh viên điểm thấp để cảnh báo.

Điểm cần nhớ: public đăng ký sinh viên không tạo hợp đồng ngay. Nó chỉ gọi `StudentRepository::register()`.

### 6.2 `models/RoomRepository.php`

Vai trò: quản lý phòng và thống kê phòng.

Các hàm chính:

- `all()`: lấy phòng, số người đang ở và điểm trung bình của sinh viên trong phòng.
- `selectOptions()`: lấy danh sách phòng cho dropdown.
- `occupiedSelectOptions()`: lấy phòng hoạt động và đang có sinh viên, dùng khi nhập điện nước.
- `find(int $roomId)`: tìm phòng.
- `save(array $data)`: thêm/sửa phòng. Phần quan trọng là tự tính `room_number = floor_number * 100 + room_sequence`.
- `resolveRoomSequence(...)`: nếu form gửi `room_sequence` thì dùng trực tiếp; nếu gửi `room_number` cũ thì tách ra số phòng.
- `delete(int $roomId)`: xóa phòng.
- `stats()`: đếm tổng phòng, phòng hoạt động, tổng sức chứa, số sinh viên đang ở.
- `topRooms(int $limit = 5)`: lấy phòng nổi bật theo số người ở và điểm trung bình.
- `studentsByRoom(int $roomId)`: lấy sinh viên đang ở trong phòng.
- `roomStatusDistribution()`: thống kê phòng trống, có người, đầy, bảo trì.
- `getOccupancy(int $roomId)`: đếm số hợp đồng đang ở trong phòng.

Điểm cần nhớ: mã phòng không nhập trực tiếp nữa. Người dùng nhập tầng và số thứ tự phòng; hệ thống tự render mã phòng real time ở giao diện và backend cũng tự tính lại.

### 6.3 `models/ContractRepository.php`

Vai trò: quản lý hợp đồng, công nợ, thanh toán.

Các hàm chính:

- `all()`: lấy hợp đồng, sinh viên, phòng; sau đó PHP tự tính `price` và `debt`.
- `activeByStudent(int $studentId)`: lấy hợp đồng đang ở của một sinh viên.
- `find(int $contractId)`: lấy một hợp đồng.
- `activeContracts()`: lấy tất cả hợp đồng đang ở.
- `studentsWithDebt()`: lấy hợp đồng đang ở mà còn nợ, sắp xếp nợ giảm dần.
- `calculateRoomFee(float $roomPrice, string $startDate, ?string $endDate, int $discountPercent = 0)`: tính tiền phòng theo ngày.
- `getDiscountByPriority(int $priorityLevel)`: mức ưu tiên càng cao thì giảm nhiều hơn.
- `save(array $data)`: thêm/sửa hợp đồng; tự lấy giảm giá từ sinh viên nếu chưa truyền.
- `delete(int $contractId)`: xóa hợp đồng.
- `addPayment(int $contractId, float $amount)`: tăng `deposit`, tức là ghi nhận đã thanh toán thêm.

Công thức tiền phòng:

```text
basePrice = roomPrice / 30 * daysInContract
finalPrice = basePrice * (100 - discountPercent) / 100
debt = finalPrice - deposit
```

Điểm cần nhớ: công nợ không chỉ đọc cứng từ database, mà được tính lại trong PHP dựa trên giá phòng, ngày ở, ưu tiên và số tiền đã thanh toán.

### 6.4 `models/UtilityBillRepository.php`

Vai trò: quản lý hóa đơn điện nước.

Các hàm chính:

- `all()`: lấy hóa đơn kèm số phòng.
- `find(int $billId)`: lấy một hóa đơn.
- `save(array $data)`: thêm/sửa hóa đơn.
- `delete(int $billId)`: xóa hóa đơn.
- `existsForRoomAndMonthYear(int $roomId, int $month, int $year)`: kiểm tra đã có hóa đơn tháng đó chưa.
- `unpaidBills()`: lấy hóa đơn chưa thanh toán.
- `billsByRoom(int $roomId)`: lấy hóa đơn của một phòng.

Điểm cần nhớ: hóa đơn hiện lưu tổng tiền, tháng/năm, phòng và trạng thái.

### 6.5 `models/NoticeRepository.php`

Vai trò: quản lý thông báo và điểm nội trú.

Các hàm chính:

- `all()`: lấy thông báo, join phòng và sinh viên để hiển thị tên.
- `find(int $noticeId)`: lấy một thông báo.
- `save(array $data)`: thêm/sửa thông báo và xử lý điểm.
- `delete(int $noticeId)`: xóa thông báo và hoàn tác điểm nếu trước đó đã cộng/trừ.
- `applyPointChange(...)`: cộng/trừ điểm theo cá nhân, phòng hoặc cả tòa.
- `studentBelongsToRoom(...)`: kiểm tra sinh viên có đang thuộc phòng đã chọn không.

Logic target hiện tại:

- `Cả tòa`: không chọn phòng, không chọn sinh viên, điểm bị ép về 0.
- `Phòng`: bắt buộc chọn phòng, không chọn sinh viên, điểm bị ép về 0.
- `Cá nhân`: bắt buộc chọn phòng và sinh viên; sinh viên phải đang ở phòng đó; điểm có thể thay đổi.

Điểm cần nhớ: khi sửa notice cũ có điểm, code trừ ngược điểm cũ trước rồi mới áp dụng điểm mới. Khi xóa notice cũng hoàn tác điểm.

## 7. Giải thích các trang public

### 7.1 `index.php` ở thư mục gốc

File gốc chỉ điều hướng vào `public/`. Nó giúp người dùng mở root project vẫn đến được trang public.

### 7.2 `public/index.php`

Đây là trang chủ sinh viên.

Khối PHP đầu file:

- Nạp `config/app.php`.
- Đặt `$pageTitle`.
- Lấy dữ liệu:
  - `$rooms = RoomRepository::all()`
  - `$notices = NoticeRepository::all()`
  - `$roomStats = RoomRepository::stats()`
  - `$studentStats = StudentRepository::registrationStats()`
  - `$topRooms = RoomRepository::topRooms(5)`
  - `$topStudents = StudentRepository::topStudents(5)`
  - `$unpaidBills = UtilityBillRepository::unpaidBills()`
- Tính thêm tỷ lệ lấp đầy, phòng còn chỗ, thông báo mới.
- Include `public_header.php`.

Khối HTML:

- Hero có ảnh ký túc xá và nút đăng ký, xem phòng, tra cứu hóa đơn.
- Dải chỉ số nhanh: phòng còn nhận hồ sơ, sinh viên đang ở, tỷ lệ lấp đầy, số thông báo.
- Danh sách phòng dạng hàng ngang, dễ quét.
- Thông báo mới và quy trình đăng ký.
- Bảng xếp hạng phòng và sinh viên điểm cao.
- Hóa đơn chưa thanh toán.
- CTA cuối trang.

Điểm cần nhớ: trang chủ chỉ đọc dữ liệu, không ghi database.

### 7.3 `public/register.php`

Đây là form sinh viên đăng ký nội trú.

Khối đầu:

- Tạo `$formData` rỗng.
- Nếu request là POST thì lấy dữ liệu từ `$_POST`.
- Ép `priority_level` nằm trong 1 đến 8.
- Kiểm tra họ tên, email, ngành/khoa bắt buộc.
- Gọi `StudentRepository::validate($formData)`.
- Nếu hợp lệ thì gọi `StudentRepository::register($formData)`.
- Thành công thì hiện thông báo và reset form.
- Lỗi thì hiện `$errorMessage`.

Khối HTML:

- Hero đăng ký.
- Alert thành công/thất bại.
- Form thông tin sinh viên.
- Dropdown đối tượng ưu tiên.
- Sidebar giải thích quy trình xét duyệt.
- Script nhỏ đổi mô tả ưu tiên theo dropdown.

Điểm cần nhớ: đăng ký public chỉ tạo sinh viên `Chờ duyệt`, admin mới duyệt và gán phòng.

### 7.4 `public/bill-inquiry.php`

Trang này cho sinh viên tra cứu hóa đơn. Luồng thường là chọn/nhập phòng, gọi Repository hóa đơn theo phòng, hiển thị danh sách.

Điểm cần nhớ: đây là chức năng public, không cần admin.

### 7.5 `public/contact.php`

Trang liên hệ public. Chủ yếu là giao diện để sinh viên gửi hoặc xem thông tin liên hệ ban quản lý.

### 7.6 `public/login.php`

File này hiện chuyển hướng về trang đăng nhập admin. Luồng đăng nhập sinh viên riêng chưa phải trọng tâm trong code hiện tại.

## 8. Giải thích các trang admin

Mọi trang admin đều có mẫu chung:

```php
require_once __DIR__ . '/../../config/app.php';
Security::requireAdminAuth();
```

Nghĩa là: nạp app và bắt buộc đã đăng nhập admin.

### 8.1 `public/admin/login.php`

Vai trò: đăng nhập admin.

Luồng:

- Nếu đã POST, lấy username/password.
- So sánh với tài khoản demo.
- Nếu đúng, gọi `Security::loginAdmin(...)`.
- Chuyển sang `admin/index.php`.
- Nếu sai, hiển thị lỗi.

### 8.2 `public/admin/index.php`

Vai trò: dashboard admin.

Hiển thị:

- Thống kê phòng, sinh viên, sức chứa, thông báo.
- Danh sách sinh viên nợ tiền phòng.
- Danh sách phòng nổi bật.
- Bộ lọc trên bảng.
- Modal thêm phòng.

Điểm cần nhớ: dashboard là trang tổng quan, không phải nơi xử lý nghiệp vụ sâu nhất.

### 8.3 `public/admin/rooms.php`

Vai trò: quản lý phòng.

Giao diện:

- Bảng phòng có filter theo tầng, loại, trạng thái, mức sử dụng, điểm TB.
- Nút thêm phòng.
- Nút sửa phòng mang theo `data-*` để modal tự điền dữ liệu.
- Mã phòng tự hiển thị real time từ tầng và số phòng.

Logic lưu:

- JS gọi `/api/rooms/save.php`.
- API gọi `RoomRepository::save()`.
- Backend tự tính lại mã phòng.

### 8.4 `public/admin/room.php`

Vai trò: chi tiết một phòng.

Luồng:

- Lấy `id` từ query string.
- Nếu id sai hoặc phòng không tồn tại thì redirect về `rooms.php`.
- Lấy sinh viên trong phòng bằng `RoomRepository::studentsByRoom($roomId)`.
- Hiển thị thông tin phòng và danh sách sinh viên.
- Có filter nhỏ cho danh sách sinh viên trong phòng.

### 8.5 `public/admin/students.php`

Vai trò: quản lý sinh viên.

Giao diện:

- Bảng sinh viên đang ở.
- Bảng hồ sơ chờ duyệt.
- Filter theo ngành, phòng, ưu tiên, điểm.
- Modal thêm/sửa sinh viên.
- Modal duyệt hồ sơ.
- Modal chuyển phòng.

Luồng sửa:

- Nút sửa chứa `data-full-name`, `data-student-code`, `data-email`, `data-priority-level`, `data-boarding-score`.
- JS đọc các `data-*` này và điền vào form.

Luồng duyệt:

- Admin chọn phòng trong modal duyệt.
- JS gọi `/api/students/approve.php`.
- API cập nhật sinh viên và tạo hợp đồng.

### 8.6 `public/admin/contracts.php`

Vai trò: quản lý hợp đồng.

Giao diện:

- Bảng hợp đồng có filter trạng thái, phòng, công nợ, hạn hợp đồng.
- Modal thêm hợp đồng.
- Modal gia hạn.
- Modal kết thúc.

Luồng tính tiền ở giao diện:

- Khi chọn sinh viên/phòng/ngày, JS tính tạm tiền phòng, giảm giá, công nợ.
- Khi lưu, backend vẫn xử lý lại ở `ContractRepository::save()`.

### 8.7 `public/admin/contract-detail.php`

Vai trò: chi tiết hợp đồng.

Giao diện:

- Thông tin sinh viên.
- Form ngày kết thúc.
- Nút lưu, gia hạn, kết thúc.
- Danh sách hóa đơn liên quan.
- Khối thanh toán hợp đồng.

Luồng thanh toán:

- Nhập số tiền.
- JS gọi `/api/contracts/pay.php`.
- API gọi `ContractRepository::addPayment()`.
- `deposit` tăng lên, nợ giảm đi.

### 8.8 `public/admin/bills.php`

Vai trò: quản lý hóa đơn.

Giao diện:

- Bảng hóa đơn có filter phòng, tháng, năm, trạng thái.
- Modal thêm/sửa hóa đơn.
- Nút đánh dấu đã thu tiền.

Luồng:

- Lưu hóa đơn gọi `/api/bills/save.php`.
- Đánh dấu đã thu gọi `/api/bills/mark-paid.php`.

### 8.9 `public/admin/meter-reading.php`

Vai trò: nhập chỉ số điện nước để tạo hóa đơn.

Giao diện:

- Chọn phòng đang có sinh viên.
- Nhập điện cũ/mới, nước cũ/mới.
- Nhập đơn giá điện/nước.
- JS tính lượng sử dụng và tổng tiền real time.

Luồng:

- JS gửi FormData đến `/api/bills/meter-reading.php`.
- API kiểm tra chỉ số mới không nhỏ hơn chỉ số cũ.
- API kiểm tra chưa có hóa đơn trùng tháng/năm.
- API lưu hóa đơn.

### 8.10 `public/admin/notices.php`

Vai trò: quản lý thông báo.

Giao diện:

- Bảng thông báo có filter loại, đối tượng, ảnh hưởng điểm.
- Form thêm/sửa.
- Nếu chọn `Cá nhân`, bắt chọn phòng rồi chỉ hiện sinh viên trong phòng đó.
- Nếu chọn `Phòng` hoặc `Cả tòa`, trường sinh viên và điểm bị khóa theo logic hiện tại.

Luồng:

- JS gọi `/api/notices/save.php`.
- Repository validate target và cập nhật điểm nếu cần.

### 8.11 `public/admin/analytics.php`

Vai trò: phân tích dữ liệu.

Hiển thị:

- KPI tổng quan.
- Tỷ lệ lấp đầy.
- Tình trạng phòng.
- Công nợ.
- Phòng/sinh viên cần chú ý.

Điểm cần nhớ: trang này chủ yếu đọc dữ liệu và trình bày, không phải nơi ghi dữ liệu.

## 9. Giải thích API endpoints

Mẫu chung của API:

```php
Api::boot();
$data = Api::input();
// validate cơ bản
// gọi Repository
Api::json(['ok' => true]);
```

### 9.1 `api/students/`

- `save.php`: thêm/sửa sinh viên.
- `delete.php`: xóa sinh viên.
- `approve.php`: duyệt sinh viên, gán phòng, tạo hợp đồng.
- `by-room.php`: lấy sinh viên theo phòng, dùng cho form thông báo.

### 9.2 `api/rooms/`

- `save.php`: thêm/sửa phòng.
- `delete.php`: xóa phòng.
- `switch.php`: chuyển sinh viên sang phòng khác.
- `upload-image.php`: còn tồn tại nhưng upload ảnh không còn là luồng chính.

### 9.3 `api/contracts/`

- `save.php`: tạo/sửa hợp đồng.
- `delete.php`: xóa hợp đồng.
- `extend.php`: gia hạn hợp đồng.
- `terminate.php`: kết thúc hợp đồng.
- `pay.php`: ghi nhận thanh toán.

### 9.4 `api/bills/`

- `save.php`: thêm/sửa hóa đơn.
- `delete.php`: xóa hóa đơn.
- `mark-paid.php`: đánh dấu đã thanh toán.
- `meter-reading.php`: tạo hóa đơn từ chỉ số điện/nước.

### 9.5 `api/notices/`

- `save.php`: thêm/sửa thông báo.
- `delete.php`: xóa thông báo và hoàn tác điểm nếu có.

## 10. JavaScript trong `assets/js/app.js`

File này gom nhiều logic giao diện admin.

Các khối chính:

- Khởi tạo DataTables cho `.datatable`.
- Bộ lọc bảng dùng `data-filter-target` và `data-filter-key`.
- Tooltip Bootstrap.
- Nút copy.
- `populateForm(form, data)`: điền dữ liệu vào form theo tên field.
- Logic phòng:
  - đọc `floor_number` và `room_sequence`;
  - tính mã phòng `P + floor * 100 + sequence`;
  - tự cập nhật khi gõ;
  - nút sửa phòng điền dữ liệu vào modal.
- Logic sinh viên:
  - nút sửa sinh viên điền form;
  - cập nhật điểm nội trú hiển thị;
  - đổi tiêu đề modal theo sinh viên đang sửa.
- Logic thông báo:
  - target `Cả tòa`, `Phòng`, `Cá nhân`;
  - lọc sinh viên theo phòng;
  - khóa/mở trường điểm, phòng, sinh viên theo target.
- Logic chuyển phòng:
  - điền modal chuyển phòng;
  - gọi `/api/rooms/switch.php`.
- `bindSave(...)`: dùng lại cho lưu phòng và thông báo.
- `bindDelete(...)`: dùng lại cho xóa phòng, sinh viên, thông báo.

Điểm cần nhớ: nhiều nút admin không submit form truyền thống, mà dùng `fetch()` gọi API.

## 11. CSS trong `assets/css/app.css`

File CSS chia thành các nhóm:

- Biến màu trong `:root`.
- Style chung: body, link, button, navbar, card/panel.
- Style public: hero, section, room list, notice list, bill panel.
- Style admin: sidebar, topbar, table panel, filter bar, DataTables.
- Style analytics: các khối `.analysis-*`.
- Responsive: media query cho tablet/mobile.

Trang chủ sinh viên mới dùng các class:

- `.public-home`
- `.public-hero`
- `.home-metric-strip`
- `.home-section`
- `.home-room-list`
- `.home-notice-list`
- `.home-ranking-table`
- `.home-bill-panel`
- `.home-final-cta`

Mục tiêu thiết kế: giao diện phẳng, rõ ràng, ít card nổi, phù hợp trang thông tin doanh nghiệp.

## 12. Views partials

### 12.1 `views/partials/public_header.php`

Tạo đầu trang public:

- Mở HTML.
- Nạp Bootstrap, Bootstrap Icons, CSS app.
- Navbar public.
- Link trang chủ, phòng, thông báo, xếp hạng, đăng ký, liên hệ, khu admin.
- Mở thẻ `<main>`.

### 12.2 `views/partials/public_footer.php`

Tạo cuối trang public:

- Đóng `</main>`.
- Footer thông tin hệ thống.
- Link nhanh.
- Thông tin liên hệ.
- Google Maps iframe.
- Nạp jQuery, Bootstrap JS, DataTables, `assets/js/app.js`.
- Đóng HTML.

### 12.3 `views/partials/admin_header.php`

Tạo layout admin:

- Nạp Bootstrap, DataTables, Chart.js, CSS.
- Sidebar admin.
- Topbar.
- Mở vùng nội dung.

### 12.4 `views/partials/admin_footer.php`

Đóng layout admin:

- Nạp JS thư viện.
- Đặt `window.APP_BASE_URL`.
- Nạp `assets/js/app.js`.
- Có toast container và override alert.

## 13. Các luồng nghiệp vụ quan trọng

### 13.1 Sinh viên đăng ký nội trú

```text
public/register.php
-> StudentRepository::validate()
-> StudentRepository::register()
-> StudentRepository::save()
-> INSERT Student status = Chờ duyệt
```

Giải thích: sinh viên chỉ tạo hồ sơ, chưa có phòng.

### 13.2 Admin duyệt sinh viên

```text
public/admin/students.php
-> click Duyệt
-> api/students/approve.php
-> kiểm tra sinh viên, phòng, sức chứa
-> update Student status = Đang ở
-> insert Contract
-> trả JSON ok
```

Giải thích: hợp đồng là bằng chứng sinh viên đã được phân phòng.

### 13.3 Tạo/sửa phòng

```text
public/admin/rooms.php
-> modal phòng
-> JS render mã phòng real time
-> api/rooms/save.php
-> RoomRepository::save()
-> room_number = floor_number * 100 + room_sequence
```

Giải thích: tầng 1, số phòng 2 sẽ thành `102`.

### 13.4 Chuyển phòng

```text
public/admin/students.php
-> modal chuyển phòng
-> api/rooms/switch.php
-> RoomRepository::getOccupancy()
-> StudentRepository::transferRoom()
-> update Contract.room_id
```

Giải thích: chuyển phòng thực chất là đổi phòng trong hợp đồng đang ở.

### 13.5 Tạo hợp đồng

```text
public/admin/contracts.php
-> api/contracts/save.php
-> ContractRepository::save()
-> lấy priority_level
-> tính discount_percent
-> INSERT/UPDATE Contract
```

Giải thích: công nợ sẽ được tính từ hợp đồng, giá phòng và tiền đã đóng.

### 13.6 Thanh toán hợp đồng

```text
public/admin/contract-detail.php
-> api/contracts/pay.php
-> ContractRepository::addPayment()
-> UPDATE Contract SET deposit = deposit + amount
```

Giải thích: `deposit` đại diện cho số tiền đã thu.

### 13.7 Tạo hóa đơn điện nước

```text
public/admin/meter-reading.php
-> nhập chỉ số cũ/mới
-> JS tính thử
-> api/bills/meter-reading.php
-> kiểm tra hợp lệ
-> UtilityBillRepository::save()
```

Giải thích: chỉ số mới phải lớn hơn hoặc bằng chỉ số cũ.

### 13.8 Thông báo cá nhân và điểm nội trú

```text
public/admin/notices.php
-> chọn Cá nhân
-> chọn phòng
-> JS lọc sinh viên trong phòng
-> api/notices/save.php
-> NoticeRepository::save()
-> NoticeRepository::applyPointChange()
```

Giải thích: điểm chỉ áp dụng cho cá nhân theo logic hiện tại.

## 14. Cách đọc một file PHP chưa quen

Khi mở một file PHP trong dự án, đọc theo thứ tự:

1. Dòng `declare(strict_types=1);`: bật kiểm tra kiểu.
2. Các dòng `require_once`: file này phụ thuộc vào đâu.
3. Dòng `Security::requireAdminAuth()`: đây có phải trang admin không.
4. Các biến đầu file: dữ liệu lấy từ Repository.
5. `require_once ... header.php`: bắt đầu render giao diện.
6. HTML có `<?= ... ?>`: PHP in dữ liệu ra HTML.
7. `Security::e(...)`: dữ liệu được escape an toàn.
8. Các `data-*`: dữ liệu gửi sang JavaScript.
9. `footer.php`: nạp JavaScript.
10. Script inline nếu có: logic riêng của trang.

Ví dụ:

```php
<td><?= Security::e((string) $room['capacity']); ?></td>
```

Đọc là: trong một ô bảng, in sức chứa phòng; ép dữ liệu sang string; escape HTML trước khi in.

## 15. Cách giải thích SQL/PDO đơn giản

Ví dụ trong Repository:

```php
$stmt = Database::connection()->prepare('SELECT * FROM Student WHERE student_id = :id LIMIT 1');
$stmt->execute([':id' => $studentId]);
$student = $stmt->fetch();
```

Giải thích:

- `Database::connection()` lấy kết nối MySQL.
- `prepare(...)` chuẩn bị câu SQL có placeholder `:id`.
- `execute([':id' => $studentId])` gắn giá trị thật vào placeholder.
- `fetch()` lấy một dòng dữ liệu.

Lợi ích: không nối chuỗi SQL trực tiếp, giảm nguy cơ SQL injection.

## 16. Các điểm mạnh có thể đưa vào báo cáo

- PHP thuần, dễ giải thích, không phụ thuộc framework.
- Tách lớp rõ: public/admin pages, API, Repository, Core.
- Dùng PDO prepared statement.
- Dùng session để bảo vệ admin.
- Dùng `Security::e()` để escape output.
- Có luồng đăng ký sinh viên, duyệt hồ sơ, phân phòng, hợp đồng.
- Có nghiệp vụ công nợ và thanh toán.
- Có nghiệp vụ thông báo và điểm nội trú.
- Có filter bảng, DataTables, dashboard, analytics.
- Giao diện public đã được thiết kế lại theo hướng chuyên nghiệp và dễ dùng hơn.

## 17. Các điểm cần nói cẩn thận

- Tài khoản admin demo đang hardcode `admin/admin`, phù hợp bài tập lớn nhưng không dùng cho production.
- Tài liệu cũ có nhắc CSRF/password hashing/Validator rộng hơn code hiện tại; khi báo cáo nên nói đây là định hướng hoặc phần framework sẵn có, không phải tất cả endpoint đã tích hợp.
- `database/schema.sql` có một đoạn tạo `Student` lặp lại ở đầu do lịch sử refactor; phần cuối schema mới là cấu trúc 5 bảng chính đang dùng.
- Một số endpoint cũ như upload ảnh phòng còn tồn tại nhưng không phải luồng chính.

## 18. Checklist thuyết trình nhanh

Nếu chỉ có 3 phút:

1. Giới thiệu: hệ thống quản lý KTX bằng PHP thuần, MySQL, Bootstrap.
2. Kiến trúc: `public` hiển thị, `api` xử lý request, `models` thao tác DB, `core` hạ tầng.
3. Database: 5 bảng `Student`, `Room`, `Contract`, `UtilityBill`, `Notice`.
4. Luồng chính: sinh viên đăng ký -> admin duyệt -> tạo hợp đồng -> theo dõi hóa đơn/công nợ.
5. Bảo mật: session admin, escape output, PDO prepared statement.
6. Giao diện: public cho sinh viên, admin CRUD, analytics.

Nếu bị hỏi "vì sao phải có Contract":

> Vì sinh viên và phòng không nên gắn cứng trực tiếp. Contract lưu sinh viên ở phòng nào, từ ngày nào đến ngày nào, trạng thái ra sao và đã thanh toán bao nhiêu. Nhờ đó hệ thống quản lý được lịch sử và công nợ.

Nếu bị hỏi "vì sao dùng Repository":

> Repository gom toàn bộ SQL vào một lớp riêng. View và API không cần biết SQL chi tiết, chỉ gọi hàm như `StudentRepository::save()`. Cách này dễ bảo trì và dễ giải thích.

Nếu bị hỏi "frontend lưu dữ liệu thế nào":

> Form trong admin được JavaScript đọc bằng FormData hoặc JSON, gửi bằng fetch đến API. API trả JSON. Nếu thành công, trang reload để thấy dữ liệu mới.

## 19. Bảng file cần nhớ

| File | Cần nhớ gì |
| --- | --- |
| `config/app.php` | Khởi động app, nạp env, autoload, session |
| `core/Database.php` | Kết nối PDO MySQL |
| `core/Security.php` | Session admin, escape HTML, chặn admin page |
| `core/Api.php` | Đọc input, trả JSON |
| `models/StudentRepository.php` | Sinh viên, đăng ký, duyệt, điểm |
| `models/RoomRepository.php` | Phòng, sức chứa, mã phòng tự động |
| `models/ContractRepository.php` | Hợp đồng, công nợ, thanh toán |
| `models/UtilityBillRepository.php` | Hóa đơn điện nước |
| `models/NoticeRepository.php` | Thông báo, cộng/trừ điểm |
| `public/index.php` | Trang chủ sinh viên |
| `public/register.php` | Form đăng ký nội trú |
| `public/admin/students.php` | Quản lý sinh viên, duyệt hồ sơ |
| `public/admin/rooms.php` | Quản lý phòng |
| `public/admin/contracts.php` | Quản lý hợp đồng |
| `public/admin/bills.php` | Quản lý hóa đơn |
| `public/admin/notices.php` | Quản lý thông báo |
| `assets/js/app.js` | Modal, fetch API, filter bảng |
| `assets/css/app.css` | Giao diện public/admin |

## 20. Kết luận

Hệ thống hiện tại đủ các luồng cốt lõi của quản lý ký túc xá:

- Sinh viên xem thông tin, đăng ký nội trú, tra cứu hóa đơn.
- Admin quản lý phòng, sinh viên, hợp đồng, hóa đơn, thông báo.
- Dữ liệu được lưu trong 5 bảng chính.
- Code tách theo lớp đủ rõ để học, báo cáo và bảo trì.

Thông điệp cuối khi báo cáo:

> Dự án không dùng framework nhưng vẫn tổ chức theo hướng chuyên nghiệp: có tầng giao diện, tầng API, tầng Repository và tầng Core. Nhờ vậy mỗi chức năng có đường đi rõ ràng từ form đến database, dễ kiểm tra và dễ giải thích.
