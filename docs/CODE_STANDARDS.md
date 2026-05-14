# Tiêu Chuẩn Mã - Quản Lý Ký Túc Xá

## 📝 Mục Tiêu

Tất cả mã nguồn phải:
- ✅ **Có thể hiểu được** - Tên rõ ràng, logic dễ theo dõi
- ✅ **Có thể giải thích** - Người đọc có thể hiểu tại sao viết như vậy
- ✅ **Có thể bảo trì** - Dễ debug và thêm tính năng mới
- ✅ **Có thể test** - Dễ kiểm thử từng phần

## 🏗️ Cấu Trúc File

### PHP Files

**Cấu trúc file PHP tiêu chuẩn:**

```php
<?php

declare(strict_types=1);

/**
 * @file Brief description of what this file does
 * @package App\Category
 * @author Your Name
 * @since 1.0
 */

namespace App\Category;

// 1. Require config/dependencies
require_once __DIR__ . '/../../config/app.php';

// 2. Security checks
Security::requireAdminAuth();

// 3. Imports (if using namespaces)
use App\Models\StudentRepository;
use App\Validation\Validator;

// 4. Main logic
// ... code here ...

// 5. Output/Response
// ... render or json ...
```

## 📌 Naming Conventions

### Class Names (PascalCase)
```php
✅ StudentRepository
✅ UtilityBillRepository
✅ ValidationException
✅ DatabaseConnection

❌ studentRepository
❌ Utility_Bill_Repo
❌ validationexception
```

### Method Names (camelCase)
```php
✅ public function findById($id)
✅ public function saveStudent($data)
✅ private function validateInput($data)
✅ public static function login($user)

❌ public function Find_By_Id($id)
❌ public function saveSTUDENT($data)
❌ public function VALIDATEINPUT($data)
```

### Variable Names (snake_case or camelCase)
```php
✅ $student_id or $studentId
✅ $full_name or $fullName
✅ $boarding_score or $boardingScore
✅ $is_admin or $isAdmin

❌ $StudentID
❌ $student id
❌ $FULLNAME
```

### Database Names (snake_case)
```sql
✅ student_id
✅ full_name
✅ boarding_score
✅ created_at

❌ studentId
❌ fullName
❌ student-id
```

### Constants (UPPER_SNAKE_CASE)
```php
✅ const DB_HOST = 'localhost';
✅ const MAX_OCCUPANCY = 6;
✅ const STATUS_PENDING = 'Chờ duyệt';

❌ const dbHost = 'localhost';
❌ const maxOccupancy = 6;
```

## 📏 Code Style

### Indentation & Spacing

```php
// ✅ Good: 4 spaces per indent level
class StudentRepository
{
    public static function save(array $data): ?int
    {
        if (empty($data['name'])) {
            return null;
        }
        
        $db = Database::connection();
        $stmt = $db->prepare("INSERT INTO students (name) VALUES (?)");
        $stmt->execute([$data['name']]);
        
        return $db->lastInsertId();
    }
}

// ❌ Bad: Inconsistent indentation
class StudentRepository{
public static function save(array $data):?int{
if(empty($data['name'])){
return null;}
$db=Database::connection();
$stmt=$db->prepare("INSERT INTO students (name) VALUES (?)");
$stmt->execute([$data['name']]);
return $db->lastInsertId();}}
```

### Braces

```php
// ✅ Good: Opening brace on same line
if ($condition) {
    // code
} else {
    // code
}

// ✅ Good: Class definition
class MyClass
{
    public function method()
    {
        // code
    }
}

// ❌ Bad: Allman style (not consistent with PSR-12)
if ($condition)
{
    // code
}
else
{
    // code
}
```

### Operator Spacing

```php
// ✅ Good: Space around operators
$result = $a + $b;
$isActive = $status === 'Đang ở';
$count = array_count_values($array);

// ❌ Bad: No spacing
$result=$a+$b;
$isActive=$status==='Đang ở';
$count=array_count_values($array);
```

### Line Length

```php
// ✅ Good: Keep lines under 120 characters
$validator = new Validator($data);
$validator->required('name')
          ->email('email')
          ->minLength('password', 8)
          ->validate();

// ❌ Bad: Line too long
$validator = new Validator($data); $validator->required('name')->email('email')->minLength('password', 8)->validate();
```

## 📚 Documentation

### File Headers

```php
<?php

declare(strict_types=1);

/**
 * Student Management Repository
 *
 * Handles all student-related database operations including
 * CRUD operations, registration status tracking, and
 * boarding score management.
 *
 * @package App\Models
 * @author Your Name <email@example.com>
 * @since 1.0
 * @version 1.0
 * @copyright 2024
 */
```

### Class Documentation

```php
/**
 * Student Repository
 *
 * Provides data access layer for Student entity.
 * All methods are static and use prepared statements
 * to prevent SQL injection.
 *
 * @package App\Models
 */
class StudentRepository
{
    // ...
}
```

### Method Documentation

```php
/**
 * Find student by ID
 *
 * Retrieves a student record from the database using the provided ID.
 * Returns null if student not found.
 *
 * @param int $id Student ID
 * @return array|null Student data array or null if not found
 * @throws DatabaseException If query fails
 *
 * @example
 * $student = StudentRepository::find(5);
 * if ($student) {
 *     echo $student['full_name'];
 * }
 */
public static function find(int $id): ?array
{
    // ...
}
```

### Inline Comments

```php
// ✅ Good: Explain WHY, not WHAT

// Validate room capacity before transfer
// to prevent overbooking violations
if ($occupancy >= $maxCapacity) {
    throw new Exception("Phòng đã đầy");
}

// ❌ Bad: Comment repeats the code

// Check if occupancy greater than or equal to max capacity
if ($occupancy >= $maxCapacity) {
    throw new Exception("Phòng đã đầy");
}
```

## 🔒 Security Best Practices

### SQL Injection Prevention

```php
// ✅ Good: Use prepared statements
$db = Database::connection();
$stmt = $db->prepare("SELECT * FROM students WHERE id = ? AND status = ?");
$stmt->execute([$id, $status]);

// ❌ Bad: String concatenation
$db = Database::connection();
$result = $db->query("SELECT * FROM students WHERE id = " . $id);
```

### XSS Prevention

```php
// ✅ Good: Always escape output
<h1><?= Security::e($student['full_name']) ?></h1>

// ❌ Bad: Direct output without escaping
<h1><?= $student['full_name'] ?></h1>
```

### Authentication

```php
// ✅ Good: Check authentication on admin pages
<?php
require_once '../../config/app.php';
Security::requireAdminAuth();

// Rest of page...
?>

// ❌ Bad: No authentication check
<?php
// Direct access to admin functionality
?>
```

### Password Hashing

```php
// ✅ Good: Never store plaintext passwords
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// ❌ Bad: Storing plaintext
$user['password'] = $password;
```

## 🧪 Error Handling

### Validation

```php
// ✅ Good: Use Validator class
try {
    $validator = new Validator($_POST);
    $validator->required('name')
              ->email('email')
              ->numeric('age')
              ->validate();
} catch (ValidationException $e) {
    $errors = $e->getErrors();
    // Display errors to user
}

// ❌ Bad: No validation
$user['name'] = $_POST['name'];
$user['email'] = $_POST['email'];
// No type checking or validation
```

### Exception Handling

```php
// ✅ Good: Catch specific exceptions
try {
    $student = StudentRepository::find($id);
    if (!$student) {
        throw new NotFoundException('Student', $id);
    }
} catch (NotFoundException $e) {
    // Handle not found
    http_response_code(404);
    echo $e->getUserMessage();
} catch (DatabaseException $e) {
    // Handle database error
    http_response_code(500);
    echo "Có lỗi xảy ra. Vui lòng thử lại.";
}

// ❌ Bad: Catch all exceptions
try {
    $student = StudentRepository::find($id);
} catch (Exception $e) {
    // Too generic
    echo "Error occurred";
}
```

### API Responses

```php
// ✅ Good: Consistent JSON response format
Api::json([
    'ok' => true,
    'message' => 'Tạo sinh viên thành công',
    'data' => ['id' => 5, 'name' => 'Nguyễn A']
]);

// ✅ Good: Error response with status code
http_response_code(400);
Api::json([
    'ok' => false,
    'message' => 'Dữ liệu không hợp lệ',
    'errors' => ['email' => ['Email đã tồn tại']]
]);

// ❌ Bad: Inconsistent response format
echo json_encode(['status' => 'success', 'result' => $data]);
echo json_encode(['error' => 'Something went wrong']);
```

## 📋 Type Hints

### Always Use Type Hints

```php
// ✅ Good: Explicit types
public function save(array $data): ?int
{
    // ...
}

public function find(int $id): ?array
{
    // ...
}

public static function getAll(string $status): array
{
    // ...
}

// ❌ Bad: No type hints
public function save($data)
{
    // ...
}

public function find($id)
{
    // ...
}
```

### Return Type Declarations

```php
// ✅ Good: Declare return types
class StudentRepository
{
    public static function find(int $id): ?array
    {
        // Returns array or null
    }

    public static function all(): array
    {
        // Returns array (empty if no records)
    }

    public static function save(array $data): int
    {
        // Returns inserted ID
    }
}
```

## 🔄 Function Design

### Single Responsibility Principle

```php
// ✅ Good: Each method does one thing
class StudentRepository
{
    public static function findById(int $id): ?array
    {
        // Only finds by ID
    }

    public static function findByEmail(string $email): ?array
    {
        // Only finds by email
    }

    public static function save(array $data): int
    {
        // Only saves data
    }
}

// ❌ Bad: Method does too much
class Student
{
    public function processManyThings($data)
    {
        // Validates, saves, sends email, logs, etc.
        // Too many responsibilities
    }
}
```

### Keep Functions Small

```php
// ✅ Good: Focused, small functions (10-20 lines)
public static function save(array $data): int
{
    $validator = new Validator($data);
    $validator->required('name')->email('email')->validate();

    $db = Database::connection();
    $stmt = $db->prepare("INSERT INTO students ...");
    $stmt->execute([...]);

    return $db->lastInsertId();
}

// ❌ Bad: Large functions with nested logic
public static function processStudent($data, $options, $flags)
{
    // 50+ lines of nested if-else
    // Hard to understand and test
}
```

## 📁 File Organization

### One Class Per File

```
models/
├── StudentRepository.php     ← StudentRepository class
├── RoomRepository.php        ← RoomRepository class
├── ContractRepository.php    ← ContractRepository class
└── UtilityBillRepository.php ← UtilityBillRepository class

src/Exceptions/
├── ApplicationException.php   ← ApplicationException class
├── ValidationException.php    ← ValidationException class
└── DatabaseException.php      ← DatabaseException class
```

### API Endpoints by Domain

```
api/
├── students/
│   ├── save.php      ← POST /api/students/save.php
│   ├── delete.php    ← POST /api/students/delete.php
│   └── approve.php   ← POST /api/students/approve.php
├── rooms/
│   ├── save.php
│   ├── delete.php
│   └── switch.php
└── bills/
    ├── save.php
    ├── mark-paid.php
    └── meter-reading.php
```

## ✅ Checklist Before Commit

- [ ] No syntax errors (`php -l filename.php`)
- [ ] Follows naming conventions
- [ ] Has proper documentation (PHPDoc)
- [ ] Uses type hints
- [ ] No SQL injection vulnerabilities
- [ ] No XSS vulnerabilities
- [ ] Proper error handling
- [ ] Lines under 120 characters
- [ ] Proper indentation (4 spaces)
- [ ] Meaningful variable names

## 🔍 Code Review Questions

1. **Understandable?** - Can someone new to the project understand what this code does?
2. **Secure?** - Are there any potential security vulnerabilities?
3. **Maintainable?** - Will this be easy to modify later?
4. **Testable?** - Can this code be easily unit tested?
5. **Efficient?** - Could this be written more efficiently?
6. **Documented?** - Is the purpose and usage clear?

---

**Phiên bản:** 1.0  
**Cập nhật:** 2024-05-14
