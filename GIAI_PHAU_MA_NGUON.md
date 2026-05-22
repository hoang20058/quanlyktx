# Giai phau ma nguon Quan ly Ky tuc xa

Tai lieu nay dung de on bao ve do an theo dung luong code **dang chay hien tai** cua project.

Kien truc hien tai khong con di theo API/Repository/AJAX cu. Project da duoc rut gon ve phong cach **Modern Procedural PHP / Page Controller / Top-Heavy**:

- Moi module admin la mot file page controller trong `public/admin`.
- Nua tren file: nap cau hinh, kiem tra quyen, khai bao ham `fetch...()` va `handle...()`, router POST.
- Nua duoi file: HTML + PHP render giao dien.
- JavaScript rieng cua page chi phu trach do du lieu vao modal, preview tinh toan, filter UI. Khong gui AJAX CRUD.
- Form gui POST ve chinh page bang truong hidden `action`.
- Sau khi xu ly POST thanh cong/that bai: set flash message va redirect de tranh submit lai khi F5.

## Mau vong doi Request hien tai

Khi doc tung luong ben duoi, hieu 5 buoc nhu sau:

1. **Giao dien (UI & Event)**  
   Nut bam, form, input hidden `action`, cac thuoc tinh `data-*`.

2. **Bat su kien & Gui Request (JavaScript / Browser Submit)**  
   Neu co JavaScript: JS chi dien du lieu vao form/modal.  
   Request CRUD duoc gui bang browser form submit: `method="post"` ve chinh page.

3. **Tiep nhan & Xu ly Logic (Page Controller)**  
   Cung file page, khoi:

   ```php
   if ($_SERVER['REQUEST_METHOD'] === 'POST') {
       $action = (string) ($_POST['action'] ?? '');
       ...
   }
   ```

4. **Thao tac CSDL (Ham fetch.../handle...)**  
   Thay vi `models/XxxRepository.php`, code hien tai dong goi SQL trong cac ham o dau file page controller.  
   Tat ca SQL ghi du lieu deu dung PDO `prepare()` va `execute([...])`.

5. **Tra ket qua & Cap nhat UI**  
   Khong tra JSON. Page dung:

   ```php
   setFlashMessage('success', '...');
   redirectTo(APP_URL . '/admin/xxx.php');
   ```

   Sau redirect, header doc flash message va hien alert.

## Nen tang chung

### Nap cau hinh va kiem tra quyen

Moi trang admin deu bat dau bang:

```php
require_once __DIR__ . '/../../config/app.php';
Security::requireAdminAuth();
```

Y nghia:

- `config/app.php`: nap `.env`, class core, helper, session, database.
- `Security::requireAdminAuth()`: neu chua dang nhap admin thi redirect ve trang login.

Code cot loi trong `core/Security.php`:

```php
public static function requireAdminAuth(): void
{
    if (!self::isAdmin()) {
        $url = defined('APP_URL') ? APP_URL : '/';
        header('Location: ' . rtrim($url, '/') . '/admin/login.php');
        exit;
    }
}
```

### Flash message va redirect

File: `core/Helpers.php`

```php
function setFlashMessage(string $type, string $message): void
{
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function redirectTo(string $url): never
{
    header('Location: ' . $url);
    exit;
}
```

Y nghia:

- POST xu ly xong se redirect.
- Nguoi dung bam F5 se reload trang GET, khong submit lai form.
- Day la co che chong trung du lieu khi reload.

---

# Module 1: Sinh vien

File chinh: `public/admin/students.php`

Chuc nang trong module:

- Lay danh sach sinh vien.
- Them / sua sinh vien.
- Xoa sinh vien.
- Duyet ho so sinh vien vao phong.
- Tu choi ho so.
- Chuyen phong sinh vien.

## SV-01. Lay danh sach sinh vien

### 1. Giao dien (UI & Event)

Nguoi dung vao menu **Quan ly sinh vien**, trinh duyet gui GET den:

```text
public/admin/students.php
```

File render 2 bang:

- `livingStudentsTable`: sinh vien dang o.
- `pendingStudentsTable`: ho so cho duyet.

Code render bang:

```php
<table id="livingStudentsTable" class="table datatable table-hover align-middle">
...
<?php foreach ($livingStudents as $row): ?>
    <tr data-department="<?= h($row['department']); ?>"
        data-room="<?= h($livingRoomNumber); ?>"
        data-priority="<?= h($row['priority_level']); ?>"
        data-score-band="<?= h($studentScoreBand); ?>">
```

### 2. Bat su kien & Gui Request

Khong co JS gui request. Day la request GET binh thuong khi mo trang.

`assets/js/app.js` chi khoi tao DataTable va filter:

```js
window.jQuery('.datatable').each(function () {
    const table = window.jQuery(this);

    if (!window.jQuery.fn.DataTable.isDataTable(table)) {
        table.DataTable({
            pageLength: 10,
            responsive: true
        });
    }
});
```

### 3. Tiep nhan & Xu ly Logic

File: `public/admin/students.php`

Sau khi qua auth, page lay du lieu:

```php
$pdo = Database::connection();
...
$students = fetchStudents($pdo);
$rooms = fetchStudentRooms($pdo);
$livingStudents = array_values(array_filter($students, static fn (array $student): bool => (($student['display_status'] ?? $student['status'] ?? '') === 'Đang ở')));
$pendingStudents = array_values(array_filter($students, static fn (array $student): bool => (($student['display_status'] ?? $student['status'] ?? '') === 'Chờ duyệt')));
```

### 4. Thao tac Co so du lieu

Ham: `fetchStudents(PDO $pdo): array`

```php
function fetchStudents(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT s.student_id, s.full_name, s.student_code, s.dob, s.phone, s.email, s.department,
               s.status, s.priority_level, s.boarding_score,
               r.room_id, r.room_number, r.floor_number,
               c.contract_id, c.start_date, c.end_date, c.status AS contract_status,
               CASE WHEN c.contract_id IS NOT NULL THEN 'Đang ở' ELSE s.status END AS display_status
          FROM Student s
     LEFT JOIN Contract c ON c.student_id = s.student_id AND c.status = 'Đang ở'
     LEFT JOIN Room r ON r.room_id = c.room_id
      ORDER BY s.student_id DESC
    ");

    return $stmt->fetchAll();
}
```

Giai thich SQL:

- `Student s`: bang chinh.
- `LEFT JOIN Contract c`: neu sinh vien co hop dong dang o thi lay hop dong.
- `LEFT JOIN Room r`: lay thong tin phong tu hop dong.
- `CASE WHEN c.contract_id IS NOT NULL THEN 'Đang ở' ELSE s.status END`: neu co hop dong dang o thi trang thai hien thi la `Đang ở`.
- Day la truy van doc, khong co input tu nguoi dung nen dung `$pdo->query()` la chap nhan duoc.

### 5. Tra ket qua & Cap nhat UI

Server tra ve HTML. Cac bien `$livingStudents`, `$pendingStudents` duoc render bang `foreach (...):`.

Moi bien dong deu boc `h()` de chong XSS:

```php
<td class="fw-semibold"><?= h($row['student_code']); ?></td>
<td><?= h($row['full_name']); ?></td>
```

## SV-02. Them / sua sinh vien

### 1. Giao dien (UI & Event)

Nut them:

```php
<button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#studentModal" data-student-id="0">Thêm sinh viên</button>
```

Nut sua truyen du lieu qua `data-*`:

```php
<button class="btn btn-sm btn-outline-primary btn-edit-student"
        data-bs-toggle="modal"
        data-bs-target="#studentModal"
        data-student-id="<?= h($row['student_id']); ?>"
        data-full-name="<?= h($row['full_name']); ?>"
        data-student-code="<?= h($row['student_code']); ?>"
        data-dob="<?= h($row['dob']); ?>"
        data-phone="<?= h($row['phone']); ?>"
        data-email="<?= h($row['email']); ?>"
        data-department="<?= h($row['department']); ?>"
        data-status="<?= h($row['status']); ?>"
        data-priority-level="<?= h($row['priority_level']); ?>"
        data-boarding-score="<?= h($row['boarding_score']); ?>">
    Sửa
</button>
```

Form:

```php
<form id="studentForm" method="post">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="student_id" value="0">
    <input type="hidden" name="boarding_score" value="100">
    ...
    <button type="submit" class="btn btn-primary">Lưu sinh viên</button>
</form>
```

### 2. Bat su kien & Gui Request

JS trong chinh `students.php` chi dien du lieu vao form:

```js
document.querySelectorAll('.btn-edit-student').forEach((button) => {
    button.addEventListener('click', () => {
        fillForm(studentForm, {
            student_id: button.dataset.studentId,
            student_code: button.dataset.studentCode,
            full_name: button.dataset.fullName,
            dob: button.dataset.dob,
            phone: button.dataset.phone,
            email: button.dataset.email,
            department: button.dataset.department,
            status: button.dataset.status,
            priority_level: button.dataset.priorityLevel,
            boarding_score: button.dataset.boardingScore
        });
        updateScoreDisplay();
    });
});
```

Khi bam submit, browser gui:

```text
POST public/admin/students.php
```

Du lieu nam trong `$_POST`, trong do `action=save`.

### 3. Tiep nhan & Xu ly Logic

Router POST:

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'save') {
            handleSaveStudent($pdo, $_POST);
            setFlashMessage('success', 'Lưu sinh viên thành công.');
            redirectTo(APP_URL . '/admin/students.php');
        }
        ...
    } catch (Throwable $e) {
        setFlashMessage('danger', $e->getMessage());
        redirectTo(APP_URL . '/admin/students.php');
    }
}
```

### 4. Thao tac Co so du lieu

Ham: `handleSaveStudent(PDO $pdo, array $input): void`

Kiem tra du lieu:

```php
$studentId = (int) ($input['student_id'] ?? 0);
$fullName = trim((string) ($input['full_name'] ?? ''));

if ($fullName === '') {
    throw new InvalidArgumentException('Vui lòng nhập họ tên sinh viên.');
}

validateStudentPayload($pdo, $input, $studentId);
```

Kiem tra trung ma sinh vien:

```php
$sql = $studentId > 0
    ? 'SELECT COUNT(*) FROM Student WHERE student_code = :student_code AND student_id <> :student_id'
    : 'SELECT COUNT(*) FROM Student WHERE student_code = :student_code';
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':student_code', $studentCode);
if ($studentId > 0) {
    $stmt->bindValue(':student_id', $studentId, PDO::PARAM_INT);
}
$stmt->execute();
```

Payload:

```php
$payload = [
    ':full_name' => $fullName,
    ':student_code' => nullableStudentText($input['student_code'] ?? null),
    ':dob' => nullableStudentText($input['dob'] ?? null),
    ':phone' => nullableStudentText($input['phone'] ?? null),
    ':email' => nullableStudentText($input['email'] ?? null),
    ':department' => nullableStudentText($input['department'] ?? null),
    ':status' => (string) ($input['status'] ?? 'Chờ duyệt'),
    ':priority_level' => max(1, min(8, (int) ($input['priority_level'] ?? 8))),
    ':boarding_score' => max(0, (int) ($input['boarding_score'] ?? 100)),
];
```

Neu sua:

```php
$stmt = $pdo->prepare('
    UPDATE Student
       SET full_name = :full_name,
           student_code = :student_code,
           dob = :dob,
           phone = :phone,
           email = :email,
           department = :department,
           status = :status,
           priority_level = :priority_level,
           boarding_score = :boarding_score
     WHERE student_id = :student_id
');
$stmt->execute($payload);
```

Neu them:

```php
$stmt = $pdo->prepare('
    INSERT INTO Student
        (full_name, student_code, dob, phone, email, department, status, priority_level, boarding_score)
    VALUES
        (:full_name, :student_code, :dob, :phone, :email, :department, :status, :priority_level, :boarding_score)
');
$stmt->execute($payload);
```

Chong SQL Injection:

- SQL dung placeholder `:full_name`, `:student_code`, `:email`.
- Du lieu that nam trong `$payload`.
- PDO gan bien khi `execute($payload)`, khong noi chuoi SQL thu cong.

### 5. Tra ket qua & Cap nhat UI

Thanh cong:

```php
setFlashMessage('success', 'Lưu sinh viên thành công.');
redirectTo(APP_URL . '/admin/students.php');
```

That bai:

```php
setFlashMessage('danger', $e->getMessage());
redirectTo(APP_URL . '/admin/students.php');
```

Trinh duyet reload lai danh sach, flash message hien o layout admin.

## SV-03. Xoa sinh vien

### 1. Giao dien (UI & Event)

Nut xoa la form POST rieng:

```php
<form method="post" onsubmit="return confirm('Bạn chắc chắn muốn xóa sinh viên này?');">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="student_id" value="<?= h($row['student_id']); ?>">
    <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
</form>
```

### 2. Bat su kien & Gui Request

Khong co JS AJAX. Browser hien `confirm()`, neu dong y thi gui:

```text
POST public/admin/students.php
action=delete
student_id=...
```

### 3. Tiep nhan & Xu ly Logic

```php
if ($action === 'delete') {
    handleDeleteStudent($pdo, (int) ($_POST['student_id'] ?? 0));
    setFlashMessage('success', 'Xóa sinh viên thành công.');
    redirectTo(APP_URL . '/admin/students.php');
}
```

### 4. Thao tac Co so du lieu

Ham: `handleDeleteStudent()`

```php
function handleDeleteStudent(PDO $pdo, int $studentId): void
{
    if ($studentId <= 0) {
        throw new InvalidArgumentException('Sinh viên không hợp lệ.');
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('DELETE FROM Student WHERE student_id = :student_id');
        $stmt->execute([':student_id' => $studentId]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
```

Giai thich:

- Dung transaction de dam bao neu co loi thi rollback.
- `:student_id` duoc bind bang `execute([':student_id' => $studentId])`.
- Khoa ngoai trong database se xu ly cac ban ghi lien quan theo schema.

### 5. Tra ket qua & Cap nhat UI

Sau xoa:

```php
setFlashMessage('success', 'Xóa sinh viên thành công.');
redirectTo(APP_URL . '/admin/students.php');
```

## SV-04. Duyet ho so sinh vien

### 1. Giao dien (UI & Event)

Nut duyet trong bang ho so cho duyet:

```php
<button class="btn btn-sm btn-success btn-approve-student"
        data-bs-toggle="modal"
        data-bs-target="#approveModal"
        data-student-id="<?= h($row['student_id']); ?>"
        data-student-name="<?= h($row['full_name']); ?>">
    Duyệt
</button>
```

Form modal:

```php
<form id="approveForm" method="post">
    <input type="hidden" name="action" value="approve">
    <input type="hidden" name="student_id" id="approve_student_id" value="0">
    <select id="approve_room_id" name="room_id" class="form-select">
        <?php foreach ($rooms as $room): ?>
            <option value="<?= h($room['room_id']); ?>">P<?= h($room['room_number']); ?> - Tầng <?= h($room['floor_number']); ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-success">Duyệt</button>
</form>
```

### 2. Bat su kien & Gui Request

JS dien id sinh vien vao modal:

```js
document.querySelectorAll('.btn-approve-student').forEach((button) => {
    button.addEventListener('click', () => {
        document.getElementById('approve_student_id').value = button.dataset.studentId || '0';
        document.getElementById('approve_student_name').value = button.dataset.studentName || '';
    });
});
```

Submit form gui POST ve `students.php` voi `action=approve`.

### 3. Tiep nhan & Xu ly Logic

```php
if ($action === 'approve') {
    handleApproveStudent($pdo, (int) ($_POST['student_id'] ?? 0), (int) ($_POST['room_id'] ?? 0));
    setFlashMessage('success', 'Duyệt hồ sơ sinh viên thành công.');
    redirectTo(APP_URL . '/admin/students.php');
}
```

### 4. Thao tac Co so du lieu

Ham: `handleApproveStudent()`

```php
$student = fetchStudentById($pdo, $studentId);
$room = fetchRoomById($pdo, $roomId);

if (!$student || !$room) {
    throw new InvalidArgumentException('Sinh viên hoặc phòng không tồn tại.');
}

if (countRoomOccupancy($pdo, $roomId) >= (int) $room['capacity']) {
    throw new RuntimeException('Phòng đã đầy, vui lòng chọn phòng khác.');
}
```

Transaction gom 3 viec:

```php
$pdo->beginTransaction();
try {
    $startDate = new DateTimeImmutable('today');
    $endDate = $startDate->modify('+5 months');

    $stmt = $pdo->prepare("UPDATE Student SET status = 'Đang ở' WHERE student_id = :student_id");
    $stmt->execute([':student_id' => $studentId]);

    $stmt = $pdo->prepare("
        INSERT INTO Contract (student_id, room_id, start_date, end_date, status)
        VALUES (:student_id, :room_id, :start_date, :end_date, 'Đang ở')
    ");
    $stmt->execute([
        ':student_id' => $studentId,
        ':room_id' => $roomId,
        ':start_date' => $startDate->format('Y-m-d'),
        ':end_date' => $endDate->format('Y-m-d'),
    ]);

    $stmt = $pdo->prepare("
        INSERT INTO Notice (target_type, category, point_change, room_id, student_id, description, date)
        VALUES ('Phòng', 'Khen thưởng', 0, :room_id, :student_id, :description, CURDATE())
    ");
    $stmt->execute([
        ':room_id' => $roomId,
        ':student_id' => $studentId,
        ':description' => sprintf('Sinh viên %s đã được phân vào phòng %s.', $student['full_name'], $room['room_number']),
    ]);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}
```

Giai thich:

- Cap nhat sinh vien sang `Đang ở`.
- Tao hop dong phong cho sinh vien.
- Tao thong bao ghi nhan phan phong.
- 3 thao tac lien quan nhau nen dung transaction.

### 5. Tra ket qua & Cap nhat UI

Thanh cong redirect ve danh sach sinh vien va hien flash.

## SV-05. Tu choi ho so

### 1. Giao dien (UI & Event)

```php
<form method="post" onsubmit="return confirm('Bạn chắc chắn muốn từ chối hồ sơ này?');">
    <input type="hidden" name="action" value="reject">
    <input type="hidden" name="student_id" value="<?= h($row['student_id']); ?>">
    <button class="btn btn-sm btn-danger" type="submit">Từ chối</button>
</form>
```

### 2. Bat su kien & Gui Request

Browser submit POST:

```text
action=reject
student_id=...
```

### 3. Tiep nhan & Xu ly Logic

```php
if ($action === 'reject') {
    handleRejectStudent($pdo, (int) ($_POST['student_id'] ?? 0));
    setFlashMessage('success', 'Từ chối hồ sơ thành công.');
    redirectTo(APP_URL . '/admin/students.php');
}
```

### 4. Thao tac Co so du lieu

```php
function handleRejectStudent(PDO $pdo, int $studentId): void
{
    $stmt = $pdo->prepare("UPDATE Student SET status = 'Đã từ chối' WHERE student_id = :student_id AND status = 'Chờ duyệt'");
    $stmt->execute([':student_id' => $studentId]);

    if ($stmt->rowCount() === 0) {
        throw new RuntimeException('Chỉ có thể từ chối hồ sơ đang chờ duyệt.');
    }
}
```

Giai thich:

- Chi cho tu choi sinh vien dang `Chờ duyệt`.
- Neu `rowCount() === 0`, nghia la khong co dong nao hop le de cap nhat.

### 5. Tra ket qua & Cap nhat UI

Redirect ve `students.php`, flash thanh cong hoac loi.

## SV-06. Chuyen phong sinh vien

### 1. Giao dien (UI & Event)

Nut chuyen phong:

```php
<button class="btn btn-sm btn-outline-success btn-switch-room"
        data-bs-toggle="modal"
        data-bs-target="#switchRoomModal"
        data-student-id="<?= h($row['student_id']); ?>"
        data-student-name="<?= h($row['full_name']); ?>"
        data-current-room-id="<?= h($row['room_id'] ?? 0); ?>">
    Chuyển phòng
</button>
```

Form:

```php
<form id="switchRoomForm" method="post">
    <input type="hidden" name="action" value="switch_room">
    <input type="hidden" name="student_id" id="switch_student_id">
    <select id="switch_new_room_id" name="new_room_id" class="form-select">
        ...
    </select>
    <button type="submit" class="btn btn-success">Chuyển</button>
</form>
```

### 2. Bat su kien & Gui Request

JS dien thong tin vao modal:

```js
document.querySelectorAll('.btn-switch-room').forEach((button) => {
    button.addEventListener('click', () => {
        document.getElementById('switch_student_id').value = button.dataset.studentId || '';
        document.getElementById('switch_student_name').value = button.dataset.studentName || '';
        const select = document.getElementById('switch_new_room_id');
        if (select && button.dataset.currentRoomId) select.value = button.dataset.currentRoomId;
    });
});
```

Submit gui POST `action=switch_room`.

### 3. Tiep nhan & Xu ly Logic

```php
if ($action === 'switch_room') {
    handleSwitchStudentRoom($pdo, (int) ($_POST['student_id'] ?? 0), (int) ($_POST['new_room_id'] ?? 0));
    setFlashMessage('success', 'Chuyển phòng thành công.');
    redirectTo(APP_URL . '/admin/students.php');
}
```

### 4. Thao tac Co so du lieu

```php
$room = fetchRoomById($pdo, $newRoomId);
if (!$room) {
    throw new InvalidArgumentException('Phòng không tồn tại.');
}

$current = currentStudentContract($pdo, $studentId);
if ($current && (int) $current['room_id'] === $newRoomId) {
    return;
}

if (countRoomOccupancy($pdo, $newRoomId) >= (int) $room['capacity']) {
    throw new RuntimeException('Phòng đã đầy, không thể chuyển sinh viên.');
}
```

Ghi database trong transaction:

```php
if ($current) {
    $stmt = $pdo->prepare('UPDATE Contract SET room_id = :room_id WHERE contract_id = :contract_id');
    $stmt->execute([
        ':room_id' => $newRoomId,
        ':contract_id' => (int) $current['contract_id'],
    ]);
} else {
    $stmt = $pdo->prepare("
        INSERT INTO Contract (student_id, room_id, start_date, status)
        VALUES (:student_id, :room_id, CURDATE(), 'Đang ở')
    ");
    $stmt->execute([
        ':student_id' => $studentId,
        ':room_id' => $newRoomId,
    ]);
}

$stmt = $pdo->prepare("UPDATE Student SET status = 'Đang ở' WHERE student_id = :student_id");
$stmt->execute([':student_id' => $studentId]);
```

### 5. Tra ket qua & Cap nhat UI

Redirect ve `students.php`, bang sinh vien duoc load lai voi phong moi.

---

# Module 2: Phong

File chinh: `public/admin/rooms.php`

Chuc nang:

- Lay danh sach phong.
- Them / sua phong.
- Xoa phong.

## ROOM-01. Lay danh sach phong

### 1. Giao dien (UI & Event)

Nguoi dung mo menu **Quan ly phong**. Page render bang:

```php
<table id="roomsTable" class="table datatable table-striped table-hover align-middle">
```

### 2. Bat su kien & Gui Request

Day la request GET. JS global `assets/js/app.js` chi khoi tao DataTable/filter.

### 3. Tiep nhan & Xu ly Logic

```php
$rooms = fetchRooms($pdo);
$roomFloors = array_values(array_unique(array_map(static fn (array $room): int => (int) $room['floor_number'], $rooms)));
...
```

### 4. Thao tac Co so du lieu

Ham: `fetchRooms(PDO $pdo): array`

```php
$stmt = $pdo->query("
    SELECT r.*,
           COUNT(c.contract_id) AS occupied_count,
           ROUND(COALESCE(AVG(s.boarding_score), 0), 2) AS avg_boarding_score
      FROM Room r
 LEFT JOIN Contract c ON c.room_id = r.room_id AND c.status = 'Đang ở'
 LEFT JOIN Student s ON s.student_id = c.student_id
  GROUP BY r.room_id
  ORDER BY r.room_number ASC
");
```

Giai thich:

- Lay tat ca phong.
- Dem so hop dong dang o de tinh `occupied_count`.
- Tinh diem noi tru trung binh cua sinh vien trong phong.
- `LEFT JOIN` giup phong chua co sinh vien van hien thi.

### 5. Tra ket qua & Cap nhat UI

HTML bang phong duoc render. Cac bien dong dung `h()`:

```php
<td class="fw-semibold">P<?= h($room['room_number']); ?></td>
```

## ROOM-02. Them / sua phong

### 1. Giao dien (UI & Event)

Nut them:

```php
<button class="btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="#roomModal" data-room-id="0">
    <i class="bi bi-plus-lg me-1"></i>Thêm phòng
</button>
```

Nut sua:

```php
<button class="btn btn-sm btn-outline-primary btn-edit-room"
        data-bs-toggle="modal"
        data-bs-target="#roomModal"
        data-room-id="<?= h($room['room_id']); ?>"
        data-room-sequence="<?= h($roomSequence); ?>"
        data-floor-number="<?= h($room['floor_number']); ?>"
        data-capacity="<?= h($room['capacity']); ?>"
        data-room-type="<?= h($room['room_type']); ?>"
        data-status="<?= h($room['status']); ?>"
        data-price="<?= h($room['price']); ?>">
    Sửa
</button>
```

Form:

```php
<form id="roomForm" method="post">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="room_id" value="0">
    ...
    <button type="submit" class="btn btn-primary">Lưu phòng</button>
</form>
```

### 2. Bat su kien & Gui Request

JS trong `rooms.php`:

```js
document.querySelectorAll('.btn-edit-room').forEach((button) => {
    button.addEventListener('click', () => {
        fillForm({
            room_id: button.dataset.roomId,
            floor_number: button.dataset.floorNumber,
            room_sequence: button.dataset.roomSequence,
            capacity: button.dataset.capacity,
            room_type: button.dataset.roomType,
            status: button.dataset.status,
            price: button.dataset.price
        });
        updateRoomNumber();
    });
});
```

Request gui bang browser form submit:

```text
POST public/admin/rooms.php
action=save
```

### 3. Tiep nhan & Xu ly Logic

```php
if ($action === 'save') {
    handleSaveRoom($pdo, $_POST);
    setFlashMessage('success', 'Lưu phòng thành công.');
    redirectTo(APP_URL . '/admin/rooms.php');
}
```

### 4. Thao tac Co so du lieu

Ham: `handleSaveRoom()`

Xu ly ma phong:

```php
$roomId = (int) ($input['room_id'] ?? 0);
$floor = (int) ($input['floor_number'] ?? 0);
$sequence = resolveRoomSequence($input, $floor);
$roomNumber = ($floor * 100) + $sequence;
```

Payload:

```php
$payload = [
    ':room_number' => $roomNumber,
    ':floor_number' => $floor,
    ':capacity' => max(1, (int) ($input['capacity'] ?? 1)),
    ':room_type' => (string) ($input['room_type'] ?? 'Thường'),
    ':status' => (string) ($input['status'] ?? 'Hoạt động'),
    ':price' => max(0, (float) ($input['price'] ?? 0)),
];
```

Neu sua:

```php
$stmt = $pdo->prepare('
    UPDATE Room
       SET room_number = :room_number,
           floor_number = :floor_number,
           capacity = :capacity,
           room_type = :room_type,
           status = :status,
           price = :price
     WHERE room_id = :room_id
');
$stmt->execute($payload);
```

Neu them:

```php
$stmt = $pdo->prepare('
    INSERT INTO Room (room_number, floor_number, capacity, room_type, status, price)
    VALUES (:room_number, :floor_number, :capacity, :room_type, :status, :price)
');
$stmt->execute($payload);
```

Chong SQL Injection:

- Khong ghep `$roomNumber` vao chuoi SQL.
- Gia tri duoc truyen qua placeholder `:room_number`, `:capacity`, `:price`.

### 5. Tra ket qua & Cap nhat UI

Sau POST, page redirect ve `rooms.php`, DataTable hien du lieu moi.

## ROOM-03. Xoa phong

### 1. Giao dien (UI & Event)

```php
<form method="post" onsubmit="return confirm('Bạn chắc chắn muốn xóa phòng này?');">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="room_id" value="<?= h($room['room_id']); ?>">
    <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
</form>
```

### 2. Bat su kien & Gui Request

Form POST ve `rooms.php` voi `action=delete`.

### 3. Tiep nhan & Xu ly Logic

```php
if ($action === 'delete') {
    handleDeleteRoom($pdo, (int) ($_POST['room_id'] ?? 0));
    setFlashMessage('success', 'Xóa phòng thành công.');
    redirectTo(APP_URL . '/admin/rooms.php');
}
```

### 4. Thao tac Co so du lieu

```php
function handleDeleteRoom(PDO $pdo, int $roomId): void
{
    if ($roomId <= 0) {
        throw new InvalidArgumentException('Phòng không hợp lệ.');
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('DELETE FROM Room WHERE room_id = :room_id');
        $stmt->execute([':room_id' => $roomId]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
```

### 5. Tra ket qua & Cap nhat UI

Flash + redirect ve danh sach phong.

---

# Module 3: Hop dong

File chinh: `public/admin/contracts.php`  
File chi tiet: `public/admin/contract-detail.php`

Chuc nang:

- Lay danh sach hop dong.
- Them / sua hop dong.
- Xoa hop dong.
- Gia han hop dong.
- Ket thuc hop dong.
- Xem chi tiet hop dong va hoa don lien quan.

## CONTRACT-01. Lay danh sach hop dong

### 1. Giao dien (UI & Event)

Mo trang:

```text
public/admin/contracts.php
```

Bang:

```php
<table id="contractsTable" class="table datatable table-hover align-middle">
```

### 2. Bat su kien & Gui Request

GET request binh thuong. JS global chi DataTable/filter.

### 3. Tiep nhan & Xu ly Logic

```php
$contracts = fetchContracts($pdo);
$students = fetchContractStudents($pdo);
$rooms = fetchContractRooms($pdo);
```

### 4. Thao tac Co so du lieu

Ham: `fetchContracts()`

```php
$stmt = $pdo->query('
    SELECT c.contract_id, c.student_id, c.room_id, c.start_date, c.end_date, c.status,
           s.full_name, s.student_code,
           r.room_number
      FROM Contract c
      JOIN Student s ON s.student_id = c.student_id
      JOIN Room r ON r.room_id = c.room_id
  ORDER BY c.contract_id DESC
');
```

Giai thich:

- `Contract` la bang chinh.
- Join `Student` de hien ten va ma sinh vien.
- Join `Room` de hien so phong.
- Day la SELECT doc du lieu, khong co input nen dung `query()`.

### 5. Tra ket qua & Cap nhat UI

HTML render bang hop dong, cot thao tac co sua/gia han/ket thuc/xoa.

## CONTRACT-02. Them / sua hop dong

### 1. Giao dien (UI & Event)

Nut them:

```php
<button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#contractModal" data-contract-id="0">
    <i class="bi bi-plus-lg me-1"></i>Thêm hợp đồng
</button>
```

Nut sua:

```php
<button class="dropdown-item btn-edit-contract"
        data-bs-toggle="modal"
        data-bs-target="#contractModal"
        data-contract-id="<?= h($contract['contract_id']); ?>"
        data-student-id="<?= h($contract['student_id']); ?>"
        data-room-id="<?= h($contract['room_id']); ?>"
        data-start-date="<?= h($contract['start_date']); ?>"
        data-end-date="<?= h($contract['end_date']); ?>"
        data-status="<?= h($contractStatus); ?>">
    Sửa
</button>
```

Form:

```php
<form id="contractForm" method="post">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="contract_id" value="0">
    ...
    <button type="submit" class="btn btn-primary">Lưu</button>
</form>
```

### 2. Bat su kien & Gui Request

JS trong `contracts.php`:

```js
document.querySelectorAll('.btn-edit-contract').forEach((button) => {
    button.addEventListener('click', () => {
        fillForm(contractForm, {
            contract_id: button.dataset.contractId,
            student_id: button.dataset.studentId,
            room_id: button.dataset.roomId,
            start_date: button.dataset.startDate,
            end_date: button.dataset.endDate,
            status: button.dataset.status
        });
    });
});
```

Luu y: `data-end-date` tren HTML se thanh `button.dataset.endDate` trong JavaScript.

Request POST:

```text
POST public/admin/contracts.php
action=save
```

### 3. Tiep nhan & Xu ly Logic

```php
if ($action === 'save') {
    handleSaveContract($pdo, $_POST);
    setFlashMessage('success', 'Lưu hợp đồng thành công.');
    redirectTo(APP_URL . '/admin/contracts.php');
}
```

### 4. Thao tac Co so du lieu

Ham: `handleSaveContract()`

Kiem tra du lieu:

```php
$studentId = (int) ($input['student_id'] ?? 0);
$roomId = (int) ($input['room_id'] ?? 0);
$startDate = trim((string) ($input['start_date'] ?? ''));
$endDate = trim((string) ($input['end_date'] ?? ''));
$status = normalizeContractStatus((string) ($input['status'] ?? 'Đang ở'));

if ($studentId <= 0 || $roomId <= 0 || $startDate === '' || $endDate === '') {
    throw new InvalidArgumentException('Vui lòng nhập đủ sinh viên, phòng, ngày vào và ngày ra.');
}
```

Kiem tra sinh vien va phong ton tai:

```php
assertStudentAndRoomExist($pdo, $studentId, $roomId);
```

Neu sua:

```php
$stmt = $pdo->prepare('
    UPDATE Contract
       SET student_id = :student_id,
           room_id = :room_id,
           start_date = :start_date,
           end_date = :end_date,
           status = :status
     WHERE contract_id = :contract_id
');
$stmt->execute([
    ':student_id' => $studentId,
    ':room_id' => $roomId,
    ':start_date' => $startDate,
    ':end_date' => $endDate,
    ':status' => $status,
    ':contract_id' => $contractId,
]);
```

Neu them:

```php
$stmt = $pdo->prepare('
    INSERT INTO Contract (student_id, room_id, start_date, end_date, status)
    VALUES (:student_id, :room_id, :start_date, :end_date, :status)
');
$stmt->execute([
    ':student_id' => $studentId,
    ':room_id' => $roomId,
    ':start_date' => $startDate,
    ':end_date' => $endDate,
    ':status' => $status,
]);
```

Dong bo trang thai sinh vien:

```php
syncStudentStatusByContract($pdo, $studentId, $status);
```

Transaction:

```php
$pdo->beginTransaction();
try {
    ...
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}
```

### 5. Tra ket qua & Cap nhat UI

Redirect ve danh sach hop dong, hien flash.

## CONTRACT-03. Xoa hop dong

### 1. Giao dien (UI & Event)

```php
<form method="post" onsubmit="return confirm('Bạn chắc chắn muốn xóa hợp đồng này?');">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="contract_id" value="<?= h($contract['contract_id']); ?>">
    <button class="dropdown-item text-danger" type="submit">Xóa</button>
</form>
```

### 2. Bat su kien & Gui Request

Form POST `action=delete`.

### 3. Tiep nhan & Xu ly Logic

```php
if ($action === 'delete') {
    handleDeleteContract($pdo, (int) ($_POST['contract_id'] ?? 0));
    setFlashMessage('success', 'Xóa hợp đồng thành công.');
    redirectTo(APP_URL . '/admin/contracts.php');
}
```

### 4. Thao tac Co so du lieu

```php
$contract = fetchContractById($pdo, $contractId);
if (!$contract) {
    throw new InvalidArgumentException('Hợp đồng không hợp lệ.');
}

$stmt = $pdo->prepare('DELETE FROM Contract WHERE contract_id = :contract_id');
$stmt->execute([':contract_id' => $contractId]);

syncStudentStatusByContract($pdo, (int) $contract['student_id'], 'Đã chuyển ra');
```

Giai thich:

- Lay hop dong truoc de biet sinh vien nao can cap nhat trang thai.
- Xoa hop dong.
- Cap nhat sinh vien sang `Đã chuyển đi`.

### 5. Tra ket qua & Cap nhat UI

Flash + redirect ve `contracts.php`.

## CONTRACT-04. Gia han hop dong

### 1. Giao dien (UI & Event)

Nut gia han:

```php
<button class="dropdown-item btn-action-extend"
        data-bs-toggle="modal"
        data-bs-target="#extendModal"
        data-contract-id="<?= h($contract['contract_id']); ?>">
    Gia hạn
</button>
```

Form:

```php
<form id="extendForm" method="post">
    <input type="hidden" name="action" value="extend">
    <input type="hidden" name="contract_id" value="0">
    <input name="new_end_date" type="date" class="form-control" required>
</form>
```

### 2. Bat su kien & Gui Request

```js
document.querySelectorAll('.btn-action-extend').forEach((button) => {
    button.addEventListener('click', () => {
        fillForm(document.getElementById('extendForm'), { contract_id: button.dataset.contractId });
    });
});
```

Submit POST `action=extend`.

### 3. Tiep nhan & Xu ly Logic

```php
if ($action === 'extend') {
    handleExtendContract($pdo, (int) ($_POST['contract_id'] ?? 0), (string) ($_POST['new_end_date'] ?? ''));
    setFlashMessage('success', 'Gia hạn hợp đồng thành công.');
    redirectTo(APP_URL . '/admin/contracts.php');
}
```

### 4. Thao tac Co so du lieu

```php
$stmt = $pdo->prepare("UPDATE Contract SET end_date = :end_date, status = 'Đang ở' WHERE contract_id = :contract_id");
$stmt->execute([
    ':end_date' => $newEndDate,
    ':contract_id' => $contractId,
]);

syncStudentStatusByContract($pdo, (int) $contract['student_id'], 'Đang ở');
```

### 5. Tra ket qua & Cap nhat UI

Redirect ve danh sach hop dong.

## CONTRACT-05. Ket thuc hop dong

### 1. Giao dien (UI & Event)

Nut:

```php
<button class="dropdown-item btn-action-terminate"
        data-bs-toggle="modal"
        data-bs-target="#terminateModal"
        data-contract-id="<?= h($contract['contract_id']); ?>">
    Kết thúc
</button>
```

Form:

```php
<form id="terminateForm" method="post">
    <input type="hidden" name="action" value="terminate">
    <input type="hidden" name="contract_id" value="0">
    <select name="reason" class="form-select">
        <option value="Chuyển đi">Chuyển đi</option>
        <option value="Vi phạm">Vi phạm</option>
        <option value="Khác">Khác</option>
    </select>
    <input name="end_date" type="date" class="form-control" value="<?= h(date('Y-m-d')); ?>">
</form>
```

### 2. Bat su kien & Gui Request

```js
document.querySelectorAll('.btn-action-terminate').forEach((button) => {
    button.addEventListener('click', () => {
        fillForm(document.getElementById('terminateForm'), { contract_id: button.dataset.contractId });
    });
});
```

### 3. Tiep nhan & Xu ly Logic

```php
if ($action === 'terminate') {
    handleTerminateContract($pdo, $_POST);
    setFlashMessage('success', 'Kết thúc hợp đồng thành công.');
    redirectTo(APP_URL . '/admin/contracts.php');
}
```

### 4. Thao tac Co so du lieu

```php
$stmt = $pdo->prepare("UPDATE Contract SET end_date = :end_date, status = 'Đã chuyển ra' WHERE contract_id = :contract_id");
$stmt->execute([
    ':end_date' => $endDate !== '' ? $endDate : date('Y-m-d'),
    ':contract_id' => $contractId,
]);

syncStudentStatusByContract($pdo, (int) $contract['student_id'], 'Đã chuyển ra');
```

Neu co ly do, tao thong bao:

```php
$stmt = $pdo->prepare("
    INSERT INTO Notice (target_type, category, point_change, room_id, student_id, description, date)
    VALUES ('Cá nhân', 'Thông báo chung', 0, :room_id, :student_id, :description, CURDATE())
");
$stmt->execute([
    ':room_id' => (int) $contract['room_id'],
    ':student_id' => (int) $contract['student_id'],
    ':description' => $reason,
]);
```

### 5. Tra ket qua & Cap nhat UI

Hop dong duoc cap nhat, sinh vien chuyen trang thai, page redirect ve danh sach.

## CONTRACT-06. Xem chi tiet hop dong

### 1. Giao dien (UI & Event)

Link:

```php
<a class="dropdown-item" href="./contract-detail.php?id=<?= h($contract['contract_id']); ?>">Chi tiết</a>
```

### 2. Bat su kien & Gui Request

GET request:

```text
public/admin/contract-detail.php?id=...
```

### 3. Tiep nhan & Xu ly Logic

```php
$contractId = (int) ($_GET['id'] ?? $_POST['contract_id'] ?? 0);
$contract = fetchContractDetail($pdo, $contractId);
if (!$contract) {
    header('HTTP/1.1 404 Not Found');
    echo 'Hợp đồng không tồn tại';
    exit;
}

$bills = fetchContractBills($pdo, (int) $contract['room_id']);
```

### 4. Thao tac Co so du lieu

Lay chi tiet:

```php
$stmt = $pdo->prepare('
    SELECT c.contract_id, c.student_id, c.room_id, c.start_date, c.end_date, c.status,
           s.full_name, s.student_code, s.department, s.phone, s.email,
           r.room_number, r.floor_number, r.capacity, r.room_type, r.status AS room_status
      FROM Contract c
      JOIN Student s ON s.student_id = c.student_id
      JOIN Room r ON r.room_id = c.room_id
     WHERE c.contract_id = :contract_id
     LIMIT 1
');
$stmt->execute([':contract_id' => $contractId]);
```

Lay hoa don lien quan:

```php
$stmt = $pdo->prepare('
    SELECT bill_id, room_id, billing_month, billing_year, total_amount, status
      FROM UtilityBill
     WHERE room_id = :room_id
  ORDER BY billing_year DESC, billing_month DESC, bill_id DESC
');
$stmt->execute([':room_id' => $roomId]);
```

### 5. Tra ket qua & Cap nhat UI

Render thong tin sinh vien, phong, hop dong va bang hoa don lien quan.

---

# Module 4: Hoa don

File chinh: `public/admin/bills.php`  
File nhap chi so: `public/admin/meter-reading.php`

Chuc nang:

- Lay danh sach hoa don.
- Sua / them hoa don thu cong.
- Danh dau da thanh toan.
- Xoa hoa don.
- Tao hoa don tu chi so dien nuoc.

## BILL-01. Lay danh sach hoa don

### 1. Giao dien (UI & Event)

Mo trang `public/admin/bills.php`, bang:

```php
<table id="billsTable" class="table datatable table-hover align-middle">
```

### 2. Bat su kien & Gui Request

GET request. JS global DataTable/filter.

### 3. Tiep nhan & Xu ly Logic

```php
$bills = fetchBills($pdo);
$rooms = fetchBillRooms($pdo);
```

### 4. Thao tac Co so du lieu

```php
$stmt = $pdo->query('
    SELECT b.bill_id, b.room_id, b.billing_month, b.billing_year, b.total_amount, b.status,
           r.room_number, r.floor_number
      FROM UtilityBill b
      JOIN Room r ON b.room_id = r.room_id
  ORDER BY b.billing_year DESC, b.billing_month DESC, b.bill_id DESC
');
```

Giai thich:

- Lay hoa don tu `UtilityBill`.
- Join `Room` de hien so phong.
- Sap xep nam/thang moi nhat truoc.

### 5. Tra ket qua & Cap nhat UI

Render bang hoa don, badge trang thai:

```php
<td><span class="badge <?= $billStatus === 'Đã thanh toán' ? 'text-bg-success' : 'text-bg-warning'; ?>"><?= h($billStatus); ?></span></td>
```

## BILL-02. Sua / them hoa don thu cong

### 1. Giao dien (UI & Event)

Nut sua:

```php
<button class="btn btn-sm btn-outline-primary btn-edit-bill"
        data-bs-toggle="modal"
        data-bs-target="#billModal"
        data-bill-id="<?= h($bill['bill_id']); ?>"
        data-room-id="<?= h($bill['room_id']); ?>"
        data-billing-month="<?= h($bill['billing_month']); ?>"
        data-billing-year="<?= h($bill['billing_year']); ?>"
        data-total-amount="<?= h($bill['total_amount']); ?>"
        data-status="<?= h($billStatus); ?>">
    Sửa
</button>
```

Form:

```php
<form id="billForm" method="post">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="bill_id" value="0">
    ...
    <button type="submit" class="btn btn-primary">Lưu</button>
</form>
```

### 2. Bat su kien & Gui Request

```js
document.querySelectorAll('.btn-edit-bill').forEach((button) => {
    button.addEventListener('click', () => {
        fillForm({
            bill_id: button.dataset.billId,
            room_id: button.dataset.roomId,
            billing_month: button.dataset.billingMonth,
            billing_year: button.dataset.billingYear,
            total_amount: button.dataset.totalAmount,
            status: button.dataset.status || 'Chưa thanh toán'
        });
    });
});
```

Luu y: `data-billing-year` tren HTML se thanh `button.dataset.billingYear` trong JavaScript. Request submit ve `bills.php` voi `action=save`.

### 3. Tiep nhan & Xu ly Logic

```php
if ($action === 'save') {
    handleSaveBill($pdo, $_POST);
    setFlashMessage('success', 'Lưu hóa đơn thành công.');
    redirectTo(APP_URL . '/admin/bills.php');
}
```

### 4. Thao tac Co so du lieu

Ham: `handleSaveBill()`

Payload:

```php
$payload = [
    ':room_id' => (int) ($input['room_id'] ?? 0),
    ':billing_month' => max(1, min(12, (int) ($input['billing_month'] ?? date('n')))),
    ':billing_year' => (int) ($input['billing_year'] ?? date('Y')),
    ':total_amount' => max(0, (float) ($input['total_amount'] ?? 0)),
    ':status' => (string) ($input['status'] ?? 'Chưa thanh toán'),
    ':new_electric_index' => (float) ($existing['new_electric_index'] ?? 0),
    ':new_water_index' => (float) ($existing['new_water_index'] ?? 0),
];
```

Neu sua:

```php
$stmt = $pdo->prepare('
    UPDATE UtilityBill
       SET room_id = :room_id,
           billing_month = :billing_month,
           billing_year = :billing_year,
           total_amount = :total_amount,
           status = :status,
           new_electric_index = :new_electric_index,
           new_water_index = :new_water_index
     WHERE bill_id = :bill_id
');
$stmt->execute($payload);
```

Neu them:

```php
$stmt = $pdo->prepare('
    INSERT INTO UtilityBill (room_id, billing_month, billing_year, total_amount, status, new_electric_index, new_water_index)
    VALUES (:room_id, :billing_month, :billing_year, :total_amount, :status, :new_electric_index, :new_water_index)
');
$stmt->execute($payload);
```

### 5. Tra ket qua & Cap nhat UI

Flash + redirect ve `bills.php`.

## BILL-03. Danh dau da thanh toan

### 1. Giao dien (UI & Event)

Chi hien neu hoa don chua thanh toan:

```php
<?php if ($billStatus !== 'Đã thanh toán'): ?>
    <form method="post" onsubmit="return confirm('Xác nhận đã thu tiền cho hóa đơn này?');">
        <input type="hidden" name="action" value="mark_paid">
        <input type="hidden" name="bill_id" value="<?= h($bill['bill_id']); ?>">
        <button class="btn btn-sm btn-success" type="submit">Đã thu tiền</button>
    </form>
<?php endif; ?>
```

### 2. Bat su kien & Gui Request

POST `action=mark_paid`.

### 3. Tiep nhan & Xu ly Logic

```php
if ($action === 'mark_paid') {
    handleMarkBillPaid($pdo, (int) ($_POST['bill_id'] ?? 0));
    setFlashMessage('success', 'Cập nhật trạng thái thanh toán thành công.');
    redirectTo(APP_URL . '/admin/bills.php');
}
```

### 4. Thao tac Co so du lieu

```php
$stmt = $pdo->prepare("UPDATE UtilityBill SET status = 'Đã thanh toán' WHERE bill_id = :bill_id");
$stmt->execute([':bill_id' => $billId]);
```

### 5. Tra ket qua & Cap nhat UI

Hoa don doi badge tu `Chưa thanh toán` sang `Đã thanh toán` sau redirect.

## BILL-04. Xoa hoa don

### 1. Giao dien (UI & Event)

```php
<form method="post" onsubmit="return confirm('Bạn chắc chắn muốn xóa hóa đơn này?');">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="bill_id" value="<?= h($bill['bill_id']); ?>">
    <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
</form>
```

### 2. Bat su kien & Gui Request

POST `action=delete`.

### 3. Tiep nhan & Xu ly Logic

```php
if ($action === 'delete') {
    handleDeleteBill($pdo, (int) ($_POST['bill_id'] ?? 0));
    setFlashMessage('success', 'Xóa hóa đơn thành công.');
    redirectTo(APP_URL . '/admin/bills.php');
}
```

### 4. Thao tac Co so du lieu

```php
$stmt = $pdo->prepare('DELETE FROM UtilityBill WHERE bill_id = :bill_id');
$stmt->execute([':bill_id' => $billId]);
```

### 5. Tra ket qua & Cap nhat UI

Flash + redirect ve danh sach hoa don.

## BILL-05. Tao hoa don tu chi so dien nuoc

### 1. Giao dien (UI & Event)

File: `public/admin/meter-reading.php`

Form:

```php
<form id="meterForm" method="post" class="row g-4 mb-4 p-4 border-top">
    <input type="hidden" name="action" value="create_bill">
    <select name="room_id" id="roomSelect" class="form-select" required>
        ...
        <option value="<?= h($room['room_id']); ?>"
                data-electric="<?= h($room['latest_electric_index']); ?>"
                data-water="<?= h($room['latest_water_index']); ?>">
            P<?= h($room['room_number']); ?>
        </option>
    </select>
    ...
    <button type="submit" class="btn btn-primary btn-lg">Tạo hóa đơn</button>
</form>
```

### 2. Bat su kien & Gui Request

JS preview tinh tien:

```js
const updateCalculation = () => {
    const oldE = parseFloat(form.old_electric.value) || 0;
    const newE = parseFloat(form.new_electric.value) || 0;
    const unitE = parseFloat(form.unit_price_electric.value) || 4000;
    const oldW = parseFloat(form.old_water.value) || 0;
    const newW = parseFloat(form.new_water.value) || 0;
    const unitW = parseFloat(form.unit_price_water.value) || 50000;
    const usageE = Math.max(0, newE - oldE);
    const usageW = Math.max(0, newW - oldW);
    const total = (usageE * unitE) + (usageW * unitW);

    totalDisplay.textContent = total.toLocaleString('vi-VN') + ' đ';
};
```

Khi chon phong, JS lay chi so moi nhat:

```js
const syncLatestIndices = () => {
    const option = roomSelect?.options[roomSelect.selectedIndex];
    form.old_electric.value = option?.dataset.electric || '';
    form.old_water.value = option?.dataset.water || '';
    form.new_electric.focus();
    updateCalculation();
};
```

Submit POST ve `meter-reading.php`.

### 3. Tiep nhan & Xu ly Logic

```php
if ($action === 'create_bill') {
    $result = handleCreateMeterBill($pdo, $_POST);
    setFlashMessage(
        'success',
        'Tạo hóa đơn thành công. Điện: ' . number_format($result['usage_electric'], 2, ',', '.') .
        ' kWh, nước: ' . number_format($result['usage_water'], 2, ',', '.') .
        ' m³, tổng: ' . number_format($result['total_amount'], 0, ',', '.') . ' đ.'
    );
    redirectTo(APP_URL . '/admin/meter-reading.php');
}
```

### 4. Thao tac Co so du lieu

Ham: `handleCreateMeterBill()`

Kiem tra:

```php
if ($newElectric < $oldElectric || $newWater < $oldWater) {
    throw new InvalidArgumentException('Chỉ số mới phải lớn hơn hoặc bằng chỉ số cũ.');
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM Contract WHERE room_id = :room_id AND status = 'Đang ở'");
$stmt->execute([':room_id' => $roomId]);
if ((int) $stmt->fetchColumn() === 0) {
    throw new RuntimeException('Phòng này hiện không có sinh viên ở.');
}
```

Tinh tien:

```php
$usageElectric = max(0, $newElectric - $oldElectric);
$usageWater = max(0, $newWater - $oldWater);
$totalAmount = ($usageElectric * $unitElectric) + ($usageWater * $unitWater);
```

Kiem tra trung hoa don thang:

```php
if (billExistsForMonth($pdo, $roomId, $month, $year)) {
    throw new RuntimeException('Hóa đơn tháng này đã tồn tại.');
}
```

Insert hoa don:

```php
$stmt = $pdo->prepare("
    INSERT INTO UtilityBill (room_id, billing_month, billing_year, total_amount, new_electric_index, new_water_index, status)
    VALUES (:room_id, :billing_month, :billing_year, :total_amount, :new_electric_index, :new_water_index, 'Chưa thanh toán')
");
$stmt->execute([
    ':room_id' => $roomId,
    ':billing_month' => $month,
    ':billing_year' => $year,
    ':total_amount' => $totalAmount,
    ':new_electric_index' => $newElectric,
    ':new_water_index' => $newWater,
]);
```

### 5. Tra ket qua & Cap nhat UI

Flash hien ro dien, nuoc, tong tien. Page redirect ve form nhap chi so.

---

# Module 5: Thong bao

File chinh: `public/admin/notices.php`

Chuc nang:

- Lay danh sach thong bao.
- Them / sua thong bao.
- Xoa thong bao.
- Tu dong cong/tru diem noi tru khi thong bao la ca nhan.

## NOTICE-01. Lay danh sach thong bao

### 1. Giao dien (UI & Event)

Mo trang:

```text
public/admin/notices.php
```

Bang:

```php
<table id="noticesTable" class="table datatable table-hover align-middle">
```

### 2. Bat su kien & Gui Request

GET request. JS global DataTable/filter.

### 3. Tiep nhan & Xu ly Logic

```php
$notices = fetchNotices($pdo);
$rooms = fetchNoticeRooms($pdo);
$students = fetchNoticeStudents($pdo);
```

### 4. Thao tac Co so du lieu

```php
$stmt = $pdo->query('
    SELECT n.*, r.room_number, s.full_name AS student_name
      FROM Notice n
 LEFT JOIN Room r ON r.room_id = n.room_id
 LEFT JOIN Student s ON s.student_id = n.student_id
  ORDER BY n.date DESC, n.notice_id DESC
');
```

Giai thich:

- Lay thong bao tu `Notice`.
- Join `Room` neu thong bao gan voi phong.
- Join `Student` neu thong bao gan voi ca nhan.

### 5. Tra ket qua & Cap nhat UI

HTML hien ngay, loai, doi tuong, diem, noi dung.

## NOTICE-02. Them / sua thong bao

### 1. Giao dien (UI & Event)

Nut them:

```php
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#noticeModal" data-notice-id="0">Thêm thông báo</button>
```

Nut sua:

```php
<button class="btn btn-sm btn-outline-primary btn-edit-notice"
        data-bs-toggle="modal"
        data-bs-target="#noticeModal"
        data-notice-id="<?= h($notice['notice_id']); ?>"
        data-target-type="<?= h($notice['target_type']); ?>"
        data-category="<?= h($notice['category']); ?>"
        data-point-change="<?= h($notice['point_change']); ?>"
        data-room-id="<?= h($notice['room_id'] ?? ''); ?>"
        data-student-id="<?= h($notice['student_id'] ?? ''); ?>"
        data-description="<?= h($notice['description']); ?>"
        data-date="<?= h($notice['date']); ?>">
    Sửa
</button>
```

Form:

```php
<form id="noticeForm" method="post">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="notice_id" value="0">
    ...
    <button type="submit" class="btn btn-primary">Lưu thông báo</button>
</form>
```

### 2. Bat su kien & Gui Request

JS dien du lieu va dong bo form:

```js
document.querySelectorAll('.btn-edit-notice').forEach((button) => {
    button.addEventListener('click', () => {
        fillForm({
            notice_id: button.dataset.noticeId,
            target_type: button.dataset.targetType,
            category: button.dataset.category,
            point_change: button.dataset.pointChange,
            room_id: button.dataset.roomId,
            student_id: button.dataset.studentId,
            description: button.dataset.description,
            date: button.dataset.date
        });
        syncForm(button.dataset.studentId || '');
    });
});
```

JS khoa/mo input theo doi tuong:

```js
roomInput.disabled = isBuilding;
roomInput.required = isRoom || isStudent;

pointInput.disabled = !isStudent;
if (!isStudent) pointInput.value = '0';

studentInput.disabled = !isStudent || !roomInput.value;
studentInput.required = isStudent;
```

Submit POST `action=save`.

### 3. Tiep nhan & Xu ly Logic

```php
if ($action === 'save') {
    handleSaveNotice($pdo, $_POST);
    setFlashMessage('success', 'Lưu thông báo thành công.');
    redirectTo(APP_URL . '/admin/notices.php');
}
```

### 4. Thao tac Co so du lieu

Chuan hoa payload:

```php
$payload = normalizeNoticePayload($pdo, $input);
```

Kiem tra doi tuong:

```php
if ($targetType === 'Cả tòa') {
    $roomId = null;
    $studentId = null;
    $pointChange = 0;
} elseif ($targetType === 'Phòng') {
    if (!$roomId) {
        throw new InvalidArgumentException('Vui lòng chọn phòng.');
    }
    $studentId = null;
    $pointChange = 0;
} elseif ($targetType === 'Cá nhân') {
    if (!$roomId || !$studentId) {
        throw new InvalidArgumentException('Vui lòng chọn phòng và sinh viên.');
    }

    if (!noticeStudentBelongsToRoom($pdo, $studentId, $roomId)) {
        throw new InvalidArgumentException('Sinh viên không thuộc phòng đã chọn.');
    }
}
```

Neu sua thong bao, tru nguoc diem cu truoc:

```php
if ($existing) {
    applyNoticePointChange(
        $pdo,
        (string) $existing['target_type'],
        -((int) $existing['point_change']),
        nullableNoticeInt($existing['room_id'] ?? null),
        nullableNoticeInt($existing['student_id'] ?? null)
    );
```

Update:

```php
$stmt = $pdo->prepare('
    UPDATE Notice
       SET target_type = :target_type,
           category = :category,
           point_change = :point_change,
           room_id = :room_id,
           student_id = :student_id,
           description = :description,
           date = :date
     WHERE notice_id = :notice_id
');
$stmt->execute([
    ':target_type' => $payload['target_type'],
    ':category' => $payload['category'],
    ':point_change' => $payload['point_change'],
    ':room_id' => $payload['room_id'],
    ':student_id' => $payload['student_id'],
    ':description' => $payload['description'],
    ':date' => $payload['date'],
    ':notice_id' => $noticeId,
]);
```

Insert:

```php
$stmt = $pdo->prepare('
    INSERT INTO Notice (target_type, category, point_change, room_id, student_id, description, date)
    VALUES (:target_type, :category, :point_change, :room_id, :student_id, :description, :date)
');
$stmt->execute([
    ':target_type' => $payload['target_type'],
    ':category' => $payload['category'],
    ':point_change' => $payload['point_change'],
    ':room_id' => $payload['room_id'],
    ':student_id' => $payload['student_id'],
    ':description' => $payload['description'],
    ':date' => $payload['date'],
]);
```

Cong/tru diem:

```php
applyNoticePointChange($pdo, $payload['target_type'], $payload['point_change'], $payload['room_id'], $payload['student_id']);
```

Ham cong/tru diem:

```php
if ($targetType === 'Cá nhân' && $studentId) {
    $stmt = $pdo->prepare('UPDATE Student SET boarding_score = boarding_score + :point_change WHERE student_id = :student_id');
    $stmt->execute([
        ':point_change' => $pointChange,
        ':student_id' => $studentId,
    ]);
}
```

### 5. Tra ket qua & Cap nhat UI

Thanh cong redirect ve `notices.php`, diem sinh vien duoc cap nhat neu thong bao ca nhan co `point_change`.

## NOTICE-03. Xoa thong bao

### 1. Giao dien (UI & Event)

```php
<form method="post" onsubmit="return confirm('Bạn chắc chắn muốn xóa thông báo này?');">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="notice_id" value="<?= h($notice['notice_id']); ?>">
    <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
</form>
```

### 2. Bat su kien & Gui Request

POST `action=delete`.

### 3. Tiep nhan & Xu ly Logic

```php
if ($action === 'delete') {
    handleDeleteNotice($pdo, (int) ($_POST['notice_id'] ?? 0));
    setFlashMessage('success', 'Xóa thông báo thành công.');
    redirectTo(APP_URL . '/admin/notices.php');
}
```

### 4. Thao tac Co so du lieu

```php
$notice = fetchNoticeById($pdo, $noticeId);
if (!$notice) {
    throw new InvalidArgumentException('Thông báo không tồn tại.');
}

$pdo->beginTransaction();
try {
    applyNoticePointChange(
        $pdo,
        (string) $notice['target_type'],
        -((int) $notice['point_change']),
        nullableNoticeInt($notice['room_id'] ?? null),
        nullableNoticeInt($notice['student_id'] ?? null)
    );

    $stmt = $pdo->prepare('DELETE FROM Notice WHERE notice_id = :notice_id');
    $stmt->execute([':notice_id' => $noticeId]);
    $pdo->commit();
}
```

Giai thich:

- Neu thong bao cu tung cong/tru diem, khi xoa phai dao nguoc diem.
- Sau do xoa ban ghi Notice.
- Dung transaction de tranh loi nua chung: diem bi doi nhung thong bao chua xoa.

### 5. Tra ket qua & Cap nhat UI

Redirect ve danh sach thong bao, flash thanh cong.

---

# Module Public: Trang chu va Dang ky noi tru

Phan nay la cau chuyen nghiep vu quan trong nhat khi bao ve:

```text
Sinh vien xem trang chu -> bam Dang ky noi tru -> gui ho so
-> ho so vao bang Student voi status = 'Chờ duyệt'
-> admin vao Quan ly sinh vien -> Duyet hoac Tu choi
-> neu duyet: cap nhat sinh vien Dang o + tao hop dong
```

## PUBLIC-01. Trang chu lay du lieu hien thi

File chinh: `public/index.php`

### 1. Giao dien (UI & Event)

Nguoi dung truy cap trang chu:

```text
public/index.php
```

Trang chu co cac nut dieu huong quan trong:

```php
<a class="btn btn-primary btn-lg" href="<?= Security::e(APP_URL); ?>/register.php">
    <i class="bi bi-pencil-square me-2"></i>Đăng ký nội trú
</a>

<a class="btn btn-light btn-lg" href="#rooms">
    <i class="bi bi-door-open me-2"></i>Xem phòng
</a>

<a class="btn btn-outline-light btn-lg" href="<?= Security::e(APP_URL); ?>/bill-inquiry.php">
    <i class="bi bi-receipt me-2"></i>Tra cứu hóa đơn
</a>
```

Y nghia:

- `Đăng ký nội trú`: di den form dang ky.
- `Xem phòng`: cuon den danh sach phong tren trang chu.
- `Tra cứu hóa đơn`: di den trang tra cuu hoa don public.

### 2. Bat su kien & Gui Request

Khong co AJAX. Day la request GET binh thuong.

JS global chi khoi tao thanh phan giao dien nhu DataTable/filter neu co. Rieng trang chu render san HTML tu PHP.

### 3. Tiep nhan & Xu ly Logic

File `public/index.php` nap config:

```php
require_once __DIR__ . '/../config/app.php';
```

Khac voi trang admin, trang public **khong goi**:

```php
Security::requireAdminAuth();
```

Vi sinh vien/khach co the xem trang chu tu do.

Sau do page lay toan bo du lieu can hien thi:

```php
$pdo = Database::connection();
$pageTitle = 'Trang chủ - ' . APP_NAME;

$rooms = fetchHomeRooms($pdo);
$notices = fetchHomeNotices($pdo);
$roomStats = fetchHomeRoomStats($pdo);
$studentStats = fetchHomeStudentStats($pdo);
$topRooms = fetchHomeTopRooms($pdo, 5);
$topStudents = fetchHomeTopStudents($pdo, 5);
$unpaidBills = fetchHomeUnpaidBills($pdo);
```

### 4. Thao tac Co so du lieu

Lay danh sach phong:

```php
function fetchHomeRooms(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT r.*,
               COUNT(c.contract_id) AS occupied_count,
               ROUND(COALESCE(AVG(s.boarding_score), 0), 2) AS avg_boarding_score
          FROM Room r
     LEFT JOIN Contract c ON c.room_id = r.room_id AND c.status = 'Đang ở'
     LEFT JOIN Student s ON s.student_id = c.student_id
      GROUP BY r.room_id
      ORDER BY r.room_number ASC
    ");

    return $stmt->fetchAll();
}
```

Giai thich:

- Lay tat ca phong tu `Room`.
- Dem so sinh vien dang o qua `Contract`.
- Tinh diem noi tru trung binh cua phong qua `Student.boarding_score`.
- Dung `LEFT JOIN` de phong chua co sinh vien van hien thi.

Lay thong bao:

```php
function fetchHomeNotices(PDO $pdo): array
{
    $stmt = $pdo->query('
        SELECT n.*, r.room_number, s.full_name AS student_name
          FROM Notice n
     LEFT JOIN Room r ON r.room_id = n.room_id
     LEFT JOIN Student s ON s.student_id = n.student_id
      ORDER BY n.date DESC, n.notice_id DESC
    ');

    return $stmt->fetchAll();
}
```

Lay thong ke phong:

```php
function fetchHomeRoomStats(PDO $pdo): array
{
    return [
        'totalRooms' => (int) $pdo->query('SELECT COUNT(*) FROM Room')->fetchColumn(),
        'activeRooms' => (int) $pdo->query("SELECT COUNT(*) FROM Room WHERE status = 'Hoạt động'")->fetchColumn(),
        'totalCapacity' => (int) $pdo->query('SELECT COALESCE(SUM(capacity), 0) FROM Room')->fetchColumn(),
        'occupied' => (int) $pdo->query("SELECT COUNT(*) FROM Contract WHERE status = 'Đang ở'")->fetchColumn(),
    ];
}
```

Lay thong ke sinh vien:

```php
function fetchHomeStudentStats(PDO $pdo): array
{
    return [
        'waiting' => (int) $pdo->query("SELECT COUNT(*) FROM Student WHERE status = 'Chờ duyệt'")->fetchColumn(),
        'living' => (int) $pdo->query("SELECT COUNT(*) FROM Student WHERE status = 'Đang ở'")->fetchColumn(),
    ];
}
```

Lay top phong co gioi han:

```php
$stmt = $pdo->prepare("
    SELECT r.room_id, r.room_number, r.floor_number, r.capacity, r.price, r.status,
           COUNT(c.contract_id) AS occupancy,
           ROUND(COALESCE(AVG(s.boarding_score), 0), 2) AS avg_boarding_score
      FROM Room r
 LEFT JOIN Contract c ON c.room_id = r.room_id AND c.status = 'Đang ở'
 LEFT JOIN Student s ON s.student_id = c.student_id
  GROUP BY r.room_id
  ORDER BY occupancy DESC, avg_boarding_score DESC, r.room_number ASC
     LIMIT :limit
");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
```

Vi sao dung `prepare()` o day:

- `$limit` la tham so truyen vao ham.
- Dung `bindValue(':limit', $limit, PDO::PARAM_INT)` de dam bao limit la so nguyen.
- Tranh viec dua truc tiep bien vao SQL.

### 5. Tra ket qua & Cap nhat UI

Trang chu tinh toan them tren mang da lay:

```php
$availableRooms = array_values(array_filter($activeRooms, static fn (array $room): bool => (int) ($room['occupied_count'] ?? 0) < (int) ($room['capacity'] ?? 0)));
$latestNotices = array_slice($notices, 0, 5);
$highlightRooms = array_slice($rooms, 0, 8);
```

Sau do render HTML:

```php
<?php foreach ($highlightRooms as $room): ?>
    <article class="home-room-row">
        <div class="home-room-id">
            <span>Tầng <?= Security::e((string) $room['floor_number']); ?></span>
            <strong>P<?= Security::e((string) $room['room_number']); ?></strong>
        </div>
```

Tat ca du lieu dong deu dung `Security::e()` de chong XSS.

## PUBLIC-02. Sinh vien gui ho so dang ky noi tru

File chinh: `public/register.php`

### 1. Giao dien (UI & Event)

Tu trang chu, sinh vien bam:

```php
<a class="btn btn-primary btn-lg" href="<?= Security::e(APP_URL); ?>/register.php">
    <i class="bi bi-pencil-square me-2"></i>Đăng ký nội trú
</a>
```

Trang dang ky render form:

```php
<form method="post" class="row g-4" novalidate>
    <input type="hidden" name="action" value="create">

    <input name="full_name" class="form-control form-control-lg" value="<?= h($formData['full_name']); ?>" required>
    <input name="student_code" class="form-control form-control-lg" value="<?= h($formData['student_code']); ?>">
    <input name="dob" type="date" class="form-control form-control-lg" value="<?= h($formData['dob']); ?>">
    <input name="phone" class="form-control form-control-lg" value="<?= h($formData['phone']); ?>">
    <input name="email" type="email" class="form-control form-control-lg" value="<?= h($formData['email']); ?>" required>
    <input name="department" class="form-control form-control-lg" value="<?= h($formData['department']); ?>" required>

    <select name="priority_level" id="priority_level" class="form-select form-select-lg">
        ...
    </select>

    <button class="btn btn-primary btn-lg rounded-pill px-5" type="submit">
        <i class="bi bi-send me-2"></i>Gửi đăng ký
    </button>
</form>
```

### 2. Bat su kien & Gui Request

Khong co AJAX. Form submit ve chinh `register.php` bang POST:

```text
action=create
full_name=...
student_code=...
email=...
department=...
priority_level=...
```

JavaScript duy nhat tren trang chi doi mo ta doi tuong uu tien:

```js
document.getElementById('priority_level')?.addEventListener('change', function () {
    const descriptions = <?= json_encode($priorityDescriptions, JSON_UNESCAPED_UNICODE); ?>;
    document.getElementById('priority_desc').textContent = descriptions[this.value] || '';
});
```

### 3. Tiep nhan & Xu ly Logic

Khoi POST:

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'create');
    $formData = cleanRegistrationInput($_POST);

    try {
        if ($action === 'create') {
            handleCreateRegistration($pdo, $formData);
            setFlashMessage('success', 'Hồ sơ đăng ký đã được gửi. Ban quản lý sẽ xem xét và phản hồi sau khi duyệt.');
            redirectTo(APP_URL . '/register.php');
        }

        throw new InvalidArgumentException('Thao tác không hợp lệ.');
    } catch (Throwable $e) {
        $errorMessage = $e->getMessage();
    }
}
```

Khac trang admin:

- Neu thanh cong: flash + redirect.
- Neu loi validation: khong redirect, giu `$errorMessage` va hien loi ngay tren form.

### 4. Thao tac Co so du lieu

Lam sach input:

```php
function cleanRegistrationInput(array $input): array
{
    $data = defaultRegistrationForm();

    foreach ($data as $key => $_) {
        $data[$key] = trim((string) ($input[$key] ?? ''));
    }

    $data['priority_level'] = (string) max(1, min(8, (int) ($data['priority_level'] ?: 8)));

    return $data;
}
```

Kiem tra du lieu:

```php
if ($input['full_name'] === '' || $input['email'] === '' || $input['department'] === '') {
    throw new InvalidArgumentException('Vui lòng nhập đầy đủ họ tên, email và ngành/khoa.');
}

if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    throw new InvalidArgumentException('Email không hợp lệ.');
}
```

Kiem tra trung ma sinh vien:

```php
if ($input['student_code'] !== '') {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM Student WHERE student_code = :student_code');
    $stmt->execute([':student_code' => $input['student_code']]);

    if ((int) $stmt->fetchColumn() > 0) {
        throw new InvalidArgumentException('Mã sinh viên đã tồn tại.');
    }
}
```

Insert ho so vao `Student`:

```php
$stmt = $pdo->prepare('
    INSERT INTO Student
        (full_name, student_code, dob, phone, email, department, status, priority_level, boarding_score)
    VALUES
        (:full_name, :student_code, :dob, :phone, :email, :department, :status, :priority_level, :boarding_score)
');

$stmt->execute([
    ':full_name' => $input['full_name'],
    ':student_code' => nullableText($input['student_code']),
    ':dob' => nullableText($input['dob']),
    ':phone' => nullableText($input['phone']),
    ':email' => nullableText($input['email']),
    ':department' => nullableText($input['department']),
    ':status' => 'Chờ duyệt',
    ':priority_level' => (int) $input['priority_level'],
    ':boarding_score' => 100,
]);
```

Giai thich:

- Ho so public khong tao hop dong ngay.
- Sinh vien moi duoc luu voi `status = 'Chờ duyệt'`.
- Day la diem noi voi module admin: admin se thay ho so nay trong bang **Ho so cho duyet**.
- SQL dung PDO placeholder `:full_name`, `:email`, `:priority_level` de chong SQL Injection.

### 5. Tra ket qua & Cap nhat UI

Thanh cong:

```php
setFlashMessage('success', 'Hồ sơ đăng ký đã được gửi. Ban quản lý sẽ xem xét và phản hồi sau khi duyệt.');
redirectTo(APP_URL . '/register.php');
```

Loi validation:

```php
catch (Throwable $e) {
    $errorMessage = $e->getMessage();
}
```

Render loi:

```php
<?php if ($errorMessage !== ''): ?>
    <section class="container my-4">
        <div class="alert alert-danger border-0 rounded-4 mb-0">
            <i class="bi bi-exclamation-circle me-2"></i><?= h($errorMessage); ?>
        </div>
    </section>
<?php endif; ?>
```

## PUBLIC-03. Admin duyet hoac tu choi ho so dang ky

File admin: `public/admin/students.php`

Day la noi ket thuc luong dang ky public.

### 1. Giao dien (UI & Event)

Ho so public sau khi insert `status = 'Chờ duyệt'` se hien trong bang:

```php
<h4>Hồ sơ chờ duyệt</h4>
<table id="pendingStudentsTable" class="table datatable table-sm table-hover align-middle">
```

Nut duyet:

```php
<button class="btn btn-sm btn-success btn-approve-student"
        data-bs-toggle="modal"
        data-bs-target="#approveModal"
        data-student-id="<?= h($row['student_id']); ?>"
        data-student-name="<?= h($row['full_name']); ?>">
    Duyệt
</button>
```

Nut tu choi:

```php
<form method="post" onsubmit="return confirm('Bạn chắc chắn muốn từ chối hồ sơ này?');">
    <input type="hidden" name="action" value="reject">
    <input type="hidden" name="student_id" value="<?= h($row['student_id']); ?>">
    <button class="btn btn-sm btn-danger" type="submit">Từ chối</button>
</form>
```

### 2. Bat su kien & Gui Request

Voi duyet, JS dien sinh vien vao modal:

```js
document.querySelectorAll('.btn-approve-student').forEach((button) => {
    button.addEventListener('click', () => {
        document.getElementById('approve_student_id').value = button.dataset.studentId || '0';
        document.getElementById('approve_student_name').value = button.dataset.studentName || '';
    });
});
```

Form duyet gui:

```text
POST public/admin/students.php
action=approve
student_id=...
room_id=...
```

Form tu choi gui:

```text
POST public/admin/students.php
action=reject
student_id=...
```

### 3. Tiep nhan & Xu ly Logic

Router duyet:

```php
if ($action === 'approve') {
    handleApproveStudent($pdo, (int) ($_POST['student_id'] ?? 0), (int) ($_POST['room_id'] ?? 0));
    setFlashMessage('success', 'Duyệt hồ sơ sinh viên thành công.');
    redirectTo(APP_URL . '/admin/students.php');
}
```

Router tu choi:

```php
if ($action === 'reject') {
    handleRejectStudent($pdo, (int) ($_POST['student_id'] ?? 0));
    setFlashMessage('success', 'Từ chối hồ sơ thành công.');
    redirectTo(APP_URL . '/admin/students.php');
}
```

### 4. Thao tac Co so du lieu

Duyet ho so:

```php
$student = fetchStudentById($pdo, $studentId);
$room = fetchRoomById($pdo, $roomId);

if (!$student || !$room) {
    throw new InvalidArgumentException('Sinh viên hoặc phòng không tồn tại.');
}

if (countRoomOccupancy($pdo, $roomId) >= (int) $room['capacity']) {
    throw new RuntimeException('Phòng đã đầy, vui lòng chọn phòng khác.');
}
```

Transaction khi duyet:

```php
$pdo->beginTransaction();
try {
    $startDate = new DateTimeImmutable('today');
    $endDate = $startDate->modify('+5 months');

    $stmt = $pdo->prepare("UPDATE Student SET status = 'Đang ở' WHERE student_id = :student_id");
    $stmt->execute([':student_id' => $studentId]);

    $stmt = $pdo->prepare("
        INSERT INTO Contract (student_id, room_id, start_date, end_date, status)
        VALUES (:student_id, :room_id, :start_date, :end_date, 'Đang ở')
    ");
    $stmt->execute([
        ':student_id' => $studentId,
        ':room_id' => $roomId,
        ':start_date' => $startDate->format('Y-m-d'),
        ':end_date' => $endDate->format('Y-m-d'),
    ]);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}
```

Tu choi ho so:

```php
$stmt = $pdo->prepare("UPDATE Student SET status = 'Đã từ chối' WHERE student_id = :student_id AND status = 'Chờ duyệt'");
$stmt->execute([':student_id' => $studentId]);

if ($stmt->rowCount() === 0) {
    throw new RuntimeException('Chỉ có thể từ chối hồ sơ đang chờ duyệt.');
}
```

Giai thich:

- Duyet: tu `Chờ duyệt` sang `Đang ở`, dong thoi tao hop dong.
- Tu choi: chi update status thanh `Đã từ chối`, khong tao hop dong.
- Duyet can transaction vi ghi nhieu bang.
- Tu choi chi ghi mot bang nen khong bat buoc transaction.

### 5. Tra ket qua & Cap nhat UI

Duyet thanh cong:

```php
setFlashMessage('success', 'Duyệt hồ sơ sinh viên thành công.');
redirectTo(APP_URL . '/admin/students.php');
```

Tu choi thanh cong:

```php
setFlashMessage('success', 'Từ chối hồ sơ thành công.');
redirectTo(APP_URL . '/admin/students.php');
```

Sau redirect:

- Ho so duyet thanh cong bien mat khoi bang cho duyet va xuat hien o bang sinh vien dang o.
- Ho so tu choi khong nam trong bang cho duyet nua.

## Tom tat luong dang ky de noi truoc hoi dong

Co the noi ngan gon:

> Trang chu lay du lieu tu cac bang Room, Contract, Student, Notice, UtilityBill de hien tinh trang ky tuc xa. Khi sinh vien bam Dang ky noi tru, form public gui POST ve `register.php`. Ham `handleCreateRegistration()` validate du lieu, kiem tra trung ma sinh vien, roi insert vao bang Student voi status la `Chờ duyệt`. Admin vao module Sinh vien se thay ho so trong bang cho duyet. Neu admin bam Duyet, he thong cap nhat Student sang `Đang ở` va tao Contract trong mot transaction. Neu bam Tu choi, he thong chi cap nhat status thanh `Đã từ chối`.

---

# Ket luan de thuyet trinh

Neu hoi ve luong CRUD hien tai, co the tra loi ngan gon:

> Du an hien tai dung Page Controller. Moi module admin la mot file PHP doc lap. Dau file nap config, kiem tra quyen admin, khai bao ham doc du lieu `fetch...()` va ham xu ly nghiep vu `handle...()`. Form tren giao dien submit POST ve chinh page, mang theo hidden `action`. Router POST dua vao `action` de goi ham tuong ung. Tat ca cau lenh ghi database deu dung PDO `prepare()` va `execute()` voi named parameter de chong SQL Injection. Cac thao tac lien quan nhieu bang nhu duyet sinh vien, chuyen phong, ket thuc hop dong, thong bao diem deu dung transaction. Xu ly xong thi set flash message va redirect de tranh submit lai khi F5.
