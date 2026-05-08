# CODE_EXPLANATION

Tài liệu này được viết theo kiểu "đàn anh khóa trên kèm đàn em ôn bảo vệ đồ án". Mục tiêu là giải thích dự án theo cách dễ hiểu nhất, kể cả khi bạn chưa biết nhiều về PHP.

---

## 1. Nhìn tổng quát: dự án này chạy theo mô hình gì?

Dự án **Quản lý Ký túc xá** là ứng dụng **PHP thuần** + **MySQL**. Không dùng framework như Laravel hay CodeIgniter. Nghĩa là mọi thứ được chia thủ công thành các phần rõ ràng:

- **`public/`**: nơi chứa các trang người dùng nhìn thấy.
- **`views/`**: giao diện dùng chung, layout, header, footer, form.
- **`api/`**: nơi xử lý request AJAX hoặc submit dữ liệu từ form.
- **`models/`**: nơi nói chuyện trực tiếp với database bằng PDO.
- **`core/`**: lớp lõi như kết nối DB, bảo mật, CSRF, JSON response.
- **`database/`**: schema, seed, script import.
- **`tools/`**: các script hỗ trợ kiểm tra, import, sửa dữ liệu.

Nói ngắn gọn:
- **Giao diện** nằm ở `public/` và `views/`.
- **Logic xử lý dữ liệu** nằm ở `api/` và `models/`.
- **Database** nằm ở MySQL.

---

## 2. File `index.php` có vai trò gì?

### `index.php` ở thư mục gốc
File [index.php](index.php) chỉ làm một việc rất đơn giản:
- chuyển hướng người dùng sang thư mục `public/`.

Nó giống như cổng vào của dự án. Khi người dùng mở website gốc, hệ thống không cho đi thẳng vào code bên trong mà đẩy về trang public.

### `public/index.php`
File [public/index.php](public/index.php) là **trang chủ public**.
Nó là nơi sinh viên hoặc khách xem:
- phòng trống,
- thông báo,
- bảng xếp hạng,
- hóa đơn chưa thanh toán,
- thông tin giới thiệu ký túc xá.

### `public/admin/index.php`
File [public/admin/index.php](public/admin/index.php) là **dashboard admin**.
Nó hiển thị:
- số sinh viên nội trú,
- số phòng,
- sinh viên nợ tiền phòng,
- các thống kê nhanh.

Tóm lại:
- `index.php` gốc = điều hướng.
- `public/index.php` = trang chủ.
- `public/admin/index.php` = trang tổng quan quản trị.

---

## 3. Luồng chạy cơ bản của một request

Mỗi khi bạn bấm nút hoặc mở trang, trình duyệt sẽ gửi **request** lên server.

Có 2 kiểu request hay gặp:
- **GET**: thường dùng để mở trang, xem dữ liệu.
- **POST**: thường dùng để gửi dữ liệu, lưu form, xóa, sửa.

### Ví dụ rất đơn giản
Khi bạn mở trang danh sách sinh viên:
- trình duyệt gửi **GET** đến `public/admin/students.php`
- file này đọc dữ liệu từ `StudentRepository`
- sau đó render ra HTML

Khi bạn bấm **Lưu sinh viên**:
- JavaScript thu dữ liệu form
- gửi **POST** tới `api/students/save.php`
- API kiểm tra CSRF
- kiểm tra dữ liệu
- gọi `StudentRepository::save()`
- repository dùng PDO để INSERT hoặc UPDATE
- trả JSON về cho giao diện

---

## 4. Nền tảng lõi của dự án

### 4.1 `config/app.php`
File [config/app.php](config/app.php) là file khởi động chung.
Nó làm các việc quan trọng:
- nạp biến môi trường từ `.env`,
- khai báo autoload class,
- tạo hằng số như `APP_URL`, `DB_HOST`, `DB_NAME`,
- đặt múi giờ,
- khởi động session.

Nói dễ hiểu: đây là file "mở máy" của toàn bộ hệ thống.

### 4.2 `core/Database.php`
File [core/Database.php](core/Database.php) tạo kết nối PDO tới MySQL.
Điểm quan trọng:
- dùng `PDO::ERRMODE_EXCEPTION` để khi lỗi thì ném exception,
- dùng `PDO::FETCH_ASSOC` để lấy dữ liệu dạng mảng kết hợp,
- `PDO::ATTR_EMULATE_PREPARES = false` để prepared statement đúng nghĩa.

Đây là lớp giúp hệ thống nói chuyện an toàn với database.

### 4.3 `core/Security.php`
File [core/Security.php](core/Security.php) xử lý:
- session,
- CSRF token,
- escape HTML,
- login/logout,
- kiểm tra quyền admin.

Các hàm quan trọng:
- `startSession()` - mở session,
- `csrfToken()` - sinh token chống giả mạo,
- `verifyCsrfToken()` - kiểm tra token,
- `login()` / `loginAdmin()` - ghi user vào session,
- `requireAuth()` - buộc phải đăng nhập,
- `requireAdminAuth()` - buộc phải là admin.

### 4.4 `core/Api.php`
File [core/Api.php](core/Api.php) giúp các endpoint JSON làm việc gọn hơn.
Nó có:
- `input()` - đọc dữ liệu từ JSON hoặc `$_POST`,
- `requireCsrf()` - chặn request không hợp lệ,
- `json()` - trả JSON và kết thúc request.

Tức là thay vì mỗi API phải viết lặp lại hàng đống code, chỉ cần gọi helper này.

---

## 5. Dữ liệu trong database đang được tổ chức như thế nào?

Dự án dùng 5 bảng chính:
- `Student`
- `Room`
- `Contract`
- `UtilityBill`
- `Notice`

### Ý nghĩa dễ hiểu
- `Student`: thông tin sinh viên.
- `Room`: thông tin phòng.
- `Contract`: hợp đồng ghép sinh viên với phòng.
- `UtilityBill`: hóa đơn điện nước theo phòng và tháng.
- `Notice`: thông báo, khen thưởng, kỷ luật, thay đổi điểm.

Bạn có thể nhớ theo công thức:
- **Student** là người,
- **Room** là nơi ở,
- **Contract** là mối liên kết,
- **UtilityBill** là tiền phải trả,
- **Notice** là điểm cộng/trừ và thông báo.

---

## 6. Vòng đời của một request cụ thể: nút "Lưu sinh viên"

Đây là phần rất quan trọng vì giảng viên hay hỏi.

### 6.1 Ở giao diện
Trong [public/admin/students.php](public/admin/students.php), form sinh viên nằm trong modal.
Khi admin bấm **Lưu sinh viên**:
- JavaScript lấy dữ liệu từ form,
- gửi request bằng `fetch()` đến `api/students/save.php`,
- request là **POST**,
- dữ liệu được gửi kèm `csrf_token`.

### 6.2 Ở API
File [api/students/save.php](api/students/save.php) làm 3 việc:
1. đọc dữ liệu bằng `Api::input()`,
2. kiểm tra CSRF bằng `Api::requireCsrf()`,
3. gọi `StudentRepository::validate()` rồi `StudentRepository::save()`.

### 6.3 Ở repository
File [models/StudentRepository.php](models/StudentRepository.php) là nơi xử lý thật sự.
Nó sẽ:
- kiểm tra email có đúng định dạng không,
- kiểm tra `student_code` có bị trùng không,
- nếu hợp lệ thì dùng PDO để `INSERT` hoặc `UPDATE`.

### 6.4 Câu chuyện dễ hiểu
Bạn có thể hiểu như sau:
- **HTML form** là tờ giấy xin lưu,
- **JavaScript** là người cầm tờ giấy đi nộp,
- **API** là cổng tiếp nhận hồ sơ,
- **Repository** là nhân viên nhập dữ liệu vào sổ,
- **MySQL** là cuốn sổ chính thức.

### 6.5 Luồng dữ liệu ngắn gọn
```text
Admin bấm Lưu sinh viên
→ JavaScript lấy FormData
→ POST đến api/students/save.php
→ Api::input()
→ Api::requireCsrf()
→ StudentRepository::validate()
→ StudentRepository::save()
→ PDO INSERT/UPDATE vào bảng Student
→ Trả JSON ok
→ Giao diện reload
```

---

## 7. Vòng đời của request: nút "Lưu hợp đồng"

### 7.1 Ở giao diện
Trong [public/admin/contracts.php](public/admin/contracts.php), khi admin bấm **Lưu**, JavaScript sẽ:
- lấy dữ liệu từ form hợp đồng,
- gửi `FormData` bằng `POST` đến `api/contracts/save.php`.

### 7.2 Ở API
File [api/contracts/save.php](api/contracts/save.php) hiện làm:
- đọc input,
- kiểm tra CSRF,
- kiểm tra `student_id` và `room_id` có tồn tại hay không,
- nếu đúng thì gọi `ContractRepository::save()`.

### 7.3 Ở repository
File [models/ContractRepository.php](models/ContractRepository.php) xử lý:
- lấy `student_id`, `room_id`, `start_date`, `end_date`, `deposit`, `discount_percent`, `status`,
- tính tiền hợp đồng bằng PHP,
- lưu vào `Contract`.

### 7.4 Ý nghĩa nghiệp vụ
Hợp đồng không chỉ là lưu vài cột.
Nó là nơi gắn:
- sinh viên nào,
- ở phòng nào,
- ở từ ngày nào đến ngày nào,
- giá bao nhiêu,
- đã đóng bao nhiêu,
- còn nợ bao nhiêu.

---

## 8. Tính tiền động trong hợp đồng hoạt động ra sao?

Đây là phần rất hay bị hỏi.

Trong [models/ContractRepository.php](models/ContractRepository.php):
- `calculateRoomFee()` nhận vào giá phòng, ngày bắt đầu, ngày kết thúc và phần trăm giảm giá.
- Nó tính số ngày ở thực tế.
- Sau đó tính tiền theo công thức:

$$
\text{price} = \frac{\text{roomPrice}}{30} \times \text{daysInContract} \times \frac{100 - \text{discount}}{100}
$$

Nói đơn giản:
- giá phòng theo tháng được quy đổi ra theo ngày,
- nhân với số ngày ở,
- rồi trừ ưu đãi theo `priority_level`.

### Vì sao không lưu cứng toàn bộ kết quả?
Vì nếu giá phòng thay đổi hoặc logic giảm giá đổi, việc tính động sẽ chính xác và dễ bảo trì hơn.

---

## 9. Vòng đời của request: admin duyệt sinh viên

Đây là luồng từ đăng ký giữ chỗ đến tạo hợp đồng.

### 9.1 Sinh viên đăng ký
File [public/register.php](public/register.php):
- sinh viên nhập form,
- request là **POST**,
- kiểm tra CSRF,
- gọi `StudentRepository::register()`.

### 9.2 Lưu hồ sơ chờ duyệt
`StudentRepository::register()` sẽ:
- đặt `status = 'Chờ duyệt'`,
- gán `priority_level`, `boarding_score`,
- gọi lại `save()` để INSERT vào bảng `Student`.

### 9.3 Admin duyệt
File [api/students/approve.php](api/students/approve.php):
- nhận `student_id`, `room_id`, `csrf_token`,
- kiểm tra sinh viên có tồn tại không,
- kiểm tra phòng có tồn tại không,
- kiểm tra phòng còn chỗ trống không,
- mở transaction,
- cập nhật trạng thái sinh viên thành `Đang ở`,
- insert contract mới,
- insert notice chúc mừng.

### 9.4 Tại sao dùng transaction?
Để tránh tình huống:
- đã đổi trạng thái sinh viên,
- nhưng chưa tạo được hợp đồng,
- hoặc tạo hợp đồng xong nhưng insert notice lỗi.

Khi đó dữ liệu sẽ lệch. Transaction giúp tất cả cùng thành công hoặc cùng thất bại.

---

## 10. Vòng đời của request: chấm điểm nội trú

### 10.1 Ở giao diện
Admin tạo notice trong màn hình quản lý thông báo.
Notice có thể là:
- `Cả tòa`,
- `Phòng`,
- `Cá nhân`.

### 10.2 Ở API
File [api/notices/save.php](api/notices/save.php) gọi `NoticeRepository::save()`.

### 10.3 Ở repository
File [models/NoticeRepository.php](models/NoticeRepository.php) làm 2 việc lớn:
- nếu là sửa notice cũ, nó hoàn tác điểm cũ trước,
- sau đó áp dụng `point_change` mới lên sinh viên phù hợp.

### 10.4 Ý nghĩa của rollback điểm
Giả sử notice cũ cộng 5 điểm.
Sau đó admin sửa notice thành trừ 10 điểm.
Nếu không trừ lại 5 điểm cũ trước, sinh viên sẽ bị cộng lệch.

Do đó khi xóa/sửa notice, code phải làm:
1. hoàn tác điểm cũ,
2. áp dụng điểm mới.

---

## 11. Vòng đời của request: nhập chỉ số điện nước

### 11.1 Ở giao diện
File [public/admin/meter-reading.php](public/admin/meter-reading.php) cho admin nhập:
- điện cũ,
- điện mới,
- nước cũ,
- nước mới,
- đơn giá điện,
- đơn giá nước.

### 11.2 Ở API
File [api/bills/meter-reading.php](api/bills/meter-reading.php) sẽ:
- kiểm tra phòng có tồn tại không,
- kiểm tra phòng có sinh viên đang ở không,
- kiểm tra chỉ số mới >= chỉ số cũ,
- tính lượng điện và nước tiêu thụ,
- tính tổng tiền,
- lưu vào `UtilityBill`.

### 11.3 Vì sao tách điện và nước?
Vì nghiệp vụ thật của ký túc xá thường tính riêng:
- điện: kWh,
- nước: m³.

Điều này giúp hóa đơn đúng thực tế hơn là gộp chung một chỉ số.

---

## 12. Kỹ thuật quan trọng đang dùng trong dự án

### 12.1 PDO chống SQL Injection
Trong toàn bộ repository, hệ thống dùng PDO và prepared statement.
Ví dụ:
- `prepare()`
- `execute()`
- `bindValue()`

Lợi ích:
- dữ liệu người dùng không được nối thẳng vào SQL,
- giảm nguy cơ SQL Injection.

### 12.2 Session để kiểm tra đăng nhập
`Security::startSession()` mở session.
`Security::loginAdmin()` lưu user vào session.
`Security::requireAdminAuth()` chặn người chưa đăng nhập.

Hiểu đơn giản: session là "thẻ ra vào" của người dùng.

### 12.3 CSRF để chống request giả mạo
Mỗi form quan trọng đều có `csrf_token`.
API sẽ kiểm tra token này trước khi xử lý.

Ý nghĩa:
- tránh việc trang khác gửi request giả vào hệ thống của mình.

### 12.4 Tính tiền động bằng PHP
Hợp đồng và hóa đơn không phải lúc nào cũng nhập tay.
Hệ thống tính động để:
- giảm sai sót,
- dễ thay đổi quy tắc,
- dễ giải thích trong báo cáo.

### 12.5 AJAX / fetch cho thao tác nhanh
Các nút lưu trong admin nhiều chỗ không reload toàn trang.
JavaScript dùng `fetch()` gửi request tới API và nhận JSON.

### 12.6 Escape dữ liệu khi hiển thị
`Security::e()` dùng `htmlspecialchars()` để chống XSS.

Nghĩa là dữ liệu hiển thị ra HTML sẽ an toàn hơn, tránh chèn mã độc vào trang.

---

## 13. File nào là file "trung tâm" cần nhớ khi bảo vệ?

Nếu chỉ nhớ vài file, nên nhớ các file sau:

- [config/app.php](config/app.php): khởi động hệ thống.
- [core/Security.php](core/Security.php): session, login, CSRF, phân quyền.
- [core/Database.php](core/Database.php): kết nối MySQL bằng PDO.
- [core/Api.php](core/Api.php): nhận input và trả JSON.
- [models/StudentRepository.php](models/StudentRepository.php): lưu sinh viên.
- [models/ContractRepository.php](models/ContractRepository.php): lưu hợp đồng, tính tiền.
- [models/NoticeRepository.php](models/NoticeRepository.php): lưu thông báo và cộng/trừ điểm.
- [models/UtilityBillRepository.php](models/UtilityBillRepository.php): lưu hóa đơn.
- [public/admin/students.php](public/admin/students.php): màn hình quản lý sinh viên.
- [public/admin/contracts.php](public/admin/contracts.php): màn hình quản lý hợp đồng.
- [public/admin/meter-reading.php](public/admin/meter-reading.php): nhập điện nước.

---

## 14. Tư duy trình bày cho người chưa biết PHP

Nếu giảng viên hỏi, bạn có thể nói theo kiểu này:

> "Em chia dự án thành 3 lớp: giao diện, xử lý và dữ liệu. Người dùng thao tác trên giao diện, JavaScript hoặc form sẽ gửi request tới API, API kiểm tra đăng nhập và CSRF rồi gọi repository để thao tác database bằng PDO. Nhờ vậy code dễ hiểu hơn, tránh viết SQL trực tiếp lung tung và dễ bảo trì hơn."

Đó là câu trả lời ngắn mà vẫn đúng bản chất.

---

## 15. 5 câu hỏi hóc búa giảng viên có thể hỏi

### Câu 1: Tại sao không lưu tiền hợp đồng cứng mà lại tính động?
**Gợi ý trả lời:**
Vì giá phòng, số ngày ở và mức ưu tiên có thể thay đổi. Tính động giúp dữ liệu nhất quán, dễ bảo trì, và tránh sai lệch khi sửa quy tắc.

### Câu 2: Vì sao cần CSRF token trong form admin?
**Gợi ý trả lời:**
Vì nếu không có CSRF, một trang khác có thể giả mạo request gửi vào hệ thống. Token giúp xác minh request này thật sự đến từ phiên làm việc hợp lệ.

### Câu 3: Tại sao dùng transaction khi duyệt sinh viên?
**Gợi ý trả lời:**
Vì duyệt sinh viên là thao tác nhiều bước: cập nhật sinh viên, tạo hợp đồng, tạo thông báo. Transaction đảm bảo nếu một bước lỗi thì toàn bộ sẽ rollback để dữ liệu không bị lệch.

### Câu 4: Khi xóa notice, vì sao phải hoàn tác boarding_score?
**Gợi ý trả lời:**
Vì notice đã làm thay đổi điểm trước đó. Nếu xóa mà không rollback, điểm nội trú sẽ không còn đúng với lịch sử nghiệp vụ.

### Câu 5: PDO khác gì so với nối chuỗi SQL trực tiếp?
**Gợi ý trả lời:**
PDO hỗ trợ prepared statement, an toàn hơn trước SQL Injection, dễ bind dữ liệu, và chuẩn hóa cách làm việc với database hơn.

---

## 16. Tóm tắt siêu ngắn để học thuộc

- `public/` là nơi người dùng mở trang.
- `api/` là nơi nhận request lưu/sửa/xóa.
- `models/` là nơi chạy SQL bằng PDO.
- `Security` lo session, CSRF, phân quyền.
- `Database` lo kết nối MySQL.
- Hợp đồng, hóa đơn và điểm nội trú đều được tính động bằng PHP.

Nếu cần nhớ một câu:

> **Giao diện gửi request, API kiểm tra và chuyển tiếp, repository nói chuyện với database, còn Security bảo vệ toàn bộ luồng.**
