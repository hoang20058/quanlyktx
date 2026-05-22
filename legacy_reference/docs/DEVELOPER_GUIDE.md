# Hướng Dẫn Phát Triển - Quick Reference

## 🎯 Trước Khi Bắt Đầu

Đọc theo thứ tự này:
1. [README.md](README.md) - Tổng quan hệ thống
2. [SETUP.md](SETUP.md) - Cài đặt lần đầu
3. [ARCHITECTURE.md](ARCHITECTURE.md) - Thiết kế hệ thống
4. [CODE_STANDARDS.md](CODE_STANDARDS.md) - Cách viết code
5. [API_GUIDE.md](API_GUIDE.md) - API endpoints

## 🚀 Workflow Điển Hình

## 🧹 Quy Ước Clean Code Cho `public/`

- Tránh viết script inline trong file `public/*.php` và `public/admin/*.php`; ưu tiên gom logic vào `assets/js/app.js`.
- Chỉ giữ markup và dữ liệu `data-*` ở view; xử lý sự kiện và gọi API đặt ở JS tập trung.
- Với modal CRUD, ưu tiên một luồng submit rõ ràng: form → endpoint chính → reload hoặc cập nhật UI.
- Khi bỏ tính năng, xóa cả 3 lớp liên quan: UI field, JS handler, data attribute để tránh dead code.

Checklist review nhanh trước khi commit:
1. Có còn ID/class không được JS sử dụng?
2. Có endpoint nào không còn được gọi?
3. Console có lỗi JS khi mở modal và submit?

### 1. Thêm Tính Năng Mới

**Bước 1: Xác định cần gì**
```
Cần: Tính năng mới để quản lý... (vd: thêm các nhận xét)
Cần API hay UI? → Cả hai
Cần database? → Có/Không
```

**Bước 2: Design database (nếu cần)**
```sql
-- database/schema.sql
ALTER TABLE comments ADD (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL REFERENCES Student(id),
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Bước 3: Tạo Model/Repository**
```php
// models/CommentRepository.php
<?php
declare(strict_types=1);

class CommentRepository {
    public static function find(int $id): ?array { ... }
    public static function all(): array { ... }
    public static function save(array $data): int { ... }
    public static function delete(int $id): bool { ... }
}
```

**Bước 4: Tạo API Endpoint**
```php
// api/comments/save.php
<?php
require_once '../../config/app.php';
Security::requireAdminAuth();

$data = Api::input();
(new Validator($data))->required('student_id')->required('content')->validate();

$id = CommentRepository::save($data);
Api::json(['ok' => true, 'id' => $id]);
```

**Bước 5: Tạo UI Page/Modal**
```php
// public/admin/comments.php
<?php
require_once '../../config/app.php';
Security::requireAdminAuth();
?>
<?php include '../../views/partials/admin_header.php'; ?>
<!-- UI here -->
<?php include '../../views/partials/admin_footer.php'; ?>
```

**Bước 6: Thêm JavaScript (nếu cần)**
```javascript
// Trong public/admin/comments.php hoặc assets/js/app.js
document.getElementById('saveBtn').addEventListener('click', async function() {
    const data = {
        student_id: document.getElementById('studentId').value,
        content: document.getElementById('content').value
    };
    
    const response = await fetch('/quanlyktx/api/comments/save.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    });
    
    const result = await response.json();
    if (result.ok) {
        alert('Lưu thành công');
        location.reload();
    } else {
        alert('Lỗi: ' + result.message);
    }
});
```

**Bước 7: Test**
```bash
# Chạy system check
php tools/system_check.php

# Test API với curl
curl -X POST http://localhost/quanlyktx/api/comments/save.php \
  -H "Content-Type: application/json" \
  -d '{"student_id":1,"content":"Test"}'

# Kiểm tra UI
# - Mở http://localhost/quanlyktx/public/admin/comments.php
# - Test create, update, delete
```

### 2. Sửa Bug

**Bước 1: Tìm lỗi**
```bash
# Chạy system check
php tools/system_check.php

# Xem browser console (F12)
# Xem network tab để check API responses
```

**Bước 2: Định vị vấn đề**
```
Lỗi ở Frontend? (Browser console)
  → Kiểm tra JavaScript, HTML
  
Lỗi ở Backend? (API response)
  → Kiểm tra API endpoint, database query
  
Lỗi ở Database? (Connection/Data)
  → Kiểm tra schema, data integrity
```

**Bước 3: Fix**
```php
// Ví dụ: API endpoint không trả về dữ liệu
// Trước (sai):
$student = StudentRepository::find($id);
Api::json(['ok' => true, 'data' => $student]); // Có thể null!

// Sau (đúng):
$student = StudentRepository::find($id);
if (!$student) {
    throw new NotFoundException('Student', $id);
}
Api::json(['ok' => true, 'data' => $student]);
```

**Bước 4: Test**
```bash
# Kiểm tra lỗi syntax
php -l path/to/file.php

# Chạy system check
php tools/system_check.php

# Test lại chức năng
```

## 📝 Code Snippets

### Tạo Class/Repository Mới

```php
<?php

declare(strict_types=1);

/**
 * [Resource] Repository
 *
 * Handles all [resource]-related database operations.
 *
 * @package App\Models
 */
class [Resource]Repository
{
    /**
     * Find by ID
     *
     * @param int $id
     * @return array|null
     */
    public static function find(int $id): ?array
    {
        $db = Database::connection();
        $stmt = $db->prepare("SELECT * FROM [table] WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get all records
     *
     * @return array
     */
    public static function all(): array
    {
        $db = Database::connection();
        $stmt = $db->prepare("SELECT * FROM [table]");
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Save (create or update)
     *
     * @param array $data
     * @return int Inserted or updated ID
     */
    public static function save(array $data): int
    {
        $db = Database::connection();

        if (!empty($data['id'])) {
            // Update
            $stmt = $db->prepare("UPDATE [table] SET ... WHERE id = ?");
            $stmt->execute([...]);
            return $data['id'];
        } else {
            // Insert
            $stmt = $db->prepare("INSERT INTO [table] (...) VALUES (...)");
            $stmt->execute([...]);
            return (int) $db->lastInsertId();
        }
    }

    /**
     * Delete
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $db = Database::connection();
        $stmt = $db->prepare("DELETE FROM [table] WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
```

### Tạo API Endpoint

```php
<?php

declare(strict_types=1);

/**
 * Save [Resource]
 *
 * POST /api/[resource]/save.php
 *
 * Create or update a [resource].
 */

require_once '../../../config/app.php';

try {
    // 1. Check authentication
    Security::requireAdminAuth();

    // 2. Parse input
    $data = Api::input();

    // 3. Validate
    $validator = new Validator($data);
    $validator->required('field1')
              ->required('field2')
              ->email('email')
              ->validate();

    // 4. Process
    $id = [Resource]Repository::save($data);

    // 5. Response
    Api::json([
        'ok' => true,
        'message' => '[Resource] saved successfully',
        'id' => $id
    ]);

} catch (ValidationException $e) {
    http_response_code(422);
    Api::json([
        'ok' => false,
        'message' => 'Validation failed',
        'errors' => $e->getErrors()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    Api::json([
        'ok' => false,
        'message' => $e->getMessage()
    ]);
}
```

### Tạo Admin Page

```php
<?php

require_once '../../config/app.php';
Security::requireAdminAuth();

// Fetch data
$[resources] = [Resource]Repository::all();

?>
<?php include '../../views/partials/admin_header.php'; ?>

<div class="container-fluid">
    <h1>[Resources]</h1>
    
    <!-- Add button -->
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal[Resource]">
        Thêm mới
    </button>

    <!-- Table -->
    <table id="table[Resources]" class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($[resources] as $[resource]): ?>
                <tr>
                    <td><?= Security::e($[resource]['id']) ?></td>
                    <td><?= Security::e($[resource]['name']) ?></td>
                    <td>
                        <button class="btn btn-sm btn-warning" onclick="edit(<?= $[resource]['id'] ?>)">Edit</button>
                        <button class="btn btn-sm btn-danger" onclick="delete(<?= $[resource]['id'] ?>)">Delete</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div class="modal fade" id="modal[Resource]" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">[Resource]</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Form here -->
            </div>
        </div>
    </div>
</div>

<?php include '../../views/partials/admin_footer.php'; ?>

<script>
// JavaScript logic
</script>
```

## 🔍 Debugging Tips

### 1. Enable Debug Mode
```php
// config/app.php
define('APP_DEBUG', true);

// Log errors to file
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/error.log');
```

### 2. Check Database
```bash
# Connect to MySQL
mysql -u root -p quanlyktx

# Check table structure
DESCRIBE students;

# Check data
SELECT * FROM students LIMIT 5;

# Check for errors
SHOW ENGINE INNODB STATUS;
```

### 3. Check PHP Syntax
```bash
php -l path/to/file.php
```

### 4. Browser Developer Tools
- **F12** - Open Developer Tools
- **Console** - Check JavaScript errors
- **Network** - Check API responses
- **Application** - Check cookies/storage

### 5. Check System
```bash
php tools/system_check.php
```

## 📊 Common Issues & Solutions

| Issue | Solution |
|-------|----------|
| **API returns 500** | Check `php -l`, check error log |
| **Database connection fails** | Check .env credentials, ensure MySQL running |
| **Page shows blank** | Check PHP syntax errors, enable error reporting |
| **JavaScript not working** | Check browser console (F12), check file paths |
| **Modal doesn't appear** | Check Bootstrap CSS loaded, check class names |
| **DataTable not sorting** | Check jQuery loaded, check data format |

## 🎯 Best Practices

✅ Always validate input before saving  
✅ Always check if resource exists before accessing  
✅ Always use prepared statements for SQL  
✅ Always escape HTML output  
✅ Always check authentication on admin pages  
✅ Always return consistent JSON format from API  
✅ Always add try-catch on API endpoints  
✅ Always add comments to complex logic  
✅ Always test after making changes  

❌ Don't hardcode database credentials  
❌ Don't concat SQL strings  
❌ Don't output unescaped variables  
❌ Don't trust user input  
❌ Don't mix PHP and JavaScript logic  
❌ Don't have long functions (> 50 lines)  
❌ Don't duplicate code  
❌ Don't commit without testing  

## 🔗 Useful Resources

- [PHP Manual](https://www.php.net/manual/)
- [MySQL Manual](https://dev.mysql.com/doc/)
- [Bootstrap 5 Docs](https://getbootstrap.com/docs/5.0/)
- [MDN Web Docs](https://developer.mozilla.org/)
- [DataTables Docs](https://datatables.net/)

## 📞 Need Help?

1. Read relevant doc in `/docs/`
2. Run `php tools/system_check.php`
3. Check browser console (F12)
4. Review similar code in the project
5. Read inline comments in code

---

**Phiên bản:** 1.0  
**Cập nhật:** 2024-05-14
