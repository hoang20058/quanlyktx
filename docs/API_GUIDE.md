# API Endpoints Guide - Quản Lý Ký Túc Xá

## 📡 API Overview

All API endpoints:
- Use **POST** method
- Return **JSON** responses
- Require **Authentication** (admin user)
- Use **Prepared Statements** (SQL injection safe)
- Validate input with **Validator** class

## 📋 Response Format

### Success Response
```json
{
  "ok": true,
  "message": "Tạo sinh viên thành công",
  "data": {
    "id": 5,
    "name": "Nguyễn Văn A"
  }
}
```

### Error Response
```json
{
  "ok": false,
  "message": "Lỗi xác thực",
  "errors": {
    "email": ["Email đã tồn tại"],
    "password": ["Mật khẩu quá yếu"]
  }
}
```

## 👥 Student Endpoints

### 1. Create/Update Student
**Endpoint:** `POST /api/students/save.php`

**Required Fields:**
- `code` (string, unique) - Student ID code
- `full_name` (string) - Full name
- `email` (string, unique) - Email address
- `priority_level` (integer, 1-8) - Priority level

**Optional Fields:**
- `id` (integer) - If provided, updates existing student

**Example Request:**
```javascript
fetch('/api/students/save.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        code: 'SV001',
        full_name: 'Nguyễn Văn A',
        email: 'student@example.com',
        priority_level: 3
    })
})
```

**Example Response:**
```json
{
  "ok": true,
  "message": "Tạo sinh viên thành công",
  "data": {"id": 5}
}
```

---

### 2. Delete Student
**Endpoint:** `POST /api/students/delete.php`

**Required Fields:**
- `id` (integer) - Student ID

**Example:**
```javascript
fetch('/api/students/delete.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({id: 5})
})
```

---

### 3. Approve Student Registration
**Endpoint:** `POST /api/students/approve.php`

**Required Fields:**
- `id` (integer) - Student ID to approve

**Changes:** Sets student status to "Đang ở"

**Example:**
```javascript
fetch('/api/students/approve.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({id: 5})
})
```

---

### 4. Get Students by Room
**Endpoint:** `POST /api/students/by-room.php`

**Required Fields:**
- `room_id` (integer) - Room ID

**Returns:** Array of students currently in the room

**Example:**
```javascript
fetch('/api/students/by-room.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({room_id: 3})
})
```

---

## 🏠 Room Endpoints

### 1. Create/Update Room
**Endpoint:** `POST /api/rooms/save.php`

**Required Fields:**
- `room_number` (string, unique) - Room identifier (e.g., "A101")
- `max_occupancy` (integer) - Maximum students allowed
- `status` (string) - "Còn trống", "Đang ở", "Đầy", "Sửa chữa"

**Optional Fields:**
- `id` (integer) - If provided, updates existing room

**Example:**
```javascript
fetch('/api/rooms/save.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        room_number: 'A101',
        max_occupancy: 4,
        status: 'Còn trống'
    })
})
```

---

### 2. Delete Room
**Endpoint:** `POST /api/rooms/delete.php`

**Required Fields:**
- `id` (integer) - Room ID

---

### 3. Switch Student to Room
**Endpoint:** `POST /api/rooms/switch.php`

**Required Fields:**
- `student_id` (integer) - Student ID
- `room_id` (integer) - New room ID

**Validation:**
- Student must exist
- Room must exist
- Room must not be full (occupancy < max_occupancy)
- Student must have an active contract

**Example:**
```javascript
fetch('/api/rooms/switch.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        student_id: 5,
        room_id: 2
    })
})
```

---

## 📋 Contract Endpoints

### 1. Create/Update Contract
**Endpoint:** `POST /api/contracts/save.php`

**Required Fields:**
- `student_id` (integer)
- `room_id` (integer)
- `start_date` (string, YYYY-MM-DD)
- `end_date` (string, YYYY-MM-DD)
- `status` (string) - "Chưa bắt đầu", "Đang ở", "Đã kết thúc"

---

### 2. Delete Contract
**Endpoint:** `POST /api/contracts/delete.php`

**Required Fields:**
- `id` (integer) - Contract ID

---

### 3. Extend Contract
**Endpoint:** `POST /api/contracts/extend.php`

**Required Fields:**
- `id` (integer) - Contract ID
- `new_end_date` (string, YYYY-MM-DD) - New end date

**Example:**
```javascript
fetch('/api/contracts/extend.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        id: 3,
        new_end_date: '2025-12-31'
    })
})
```

---

### 4. Terminate Contract
**Endpoint:** `POST /api/contracts/terminate.php`

**Required Fields:**
- `id` (integer) - Contract ID
- `reason` (string) - Reason for termination
- `end_date` (string, YYYY-MM-DD) - Termination date

---

### 5. Process Payment
**Endpoint:** `POST /api/contracts/pay.php`

**Required Fields:**
- `id` (integer) - Contract ID
- `amount` (number) - Payment amount

---

## 💰 Bill Endpoints

### 1. Create/Update Bill
**Endpoint:** `POST /api/bills/save.php`

**Required Fields:**
- `contract_id` (integer)
- `month` (integer, 1-12)
- `year` (integer)
- `water_usage` (number) - Water consumption (m³)
- `electric_usage` (number) - Electric consumption (kWh)
- `amount` (number) - Total bill amount
- `status` (string) - "Chưa thanh toán", "Đã thanh toán"

---

### 2. Delete Bill
**Endpoint:** `POST /api/bills/delete.php`

**Required Fields:**
- `id` (integer) - Bill ID

---

### 3. Mark Bill as Paid
**Endpoint:** `POST /api/bills/mark-paid.php`

**Required Fields:**
- `id` (integer) - Bill ID

**Changes:** Sets bill status to "Đã thanh toán"

---

### 4. Record Meter Reading
**Endpoint:** `POST /api/bills/meter-reading.php`

**Purpose:** Create utility bills from meter readings

**Required Fields:**
- `room_id` (integer)
- `month` (integer)
- `year` (integer)
- `water_reading` (number) - Current water meter reading
- `electric_reading` (number) - Current electric meter reading

**Logic:**
1. Calculate usage: current_reading - previous_reading
2. Calculate charges: usage × unit_price
3. Create UtilityBill record

---

## 📢 Notice Endpoints

### 1. Create/Update Notice
**Endpoint:** `POST /api/notices/save.php`

**Required Fields:**
- `title` (string) - Notice title
- `content` (string) - Notice content
- `status` (string) - "draft", "published"
- `point_change` (integer) - Points to add/subtract from students (-100 to +100)

**Optional Fields:**
- `id` (integer) - If provided, updates existing notice

---

### 2. Delete Notice
**Endpoint:** `POST /api/notices/delete.php`

**Required Fields:**
- `id` (integer) - Notice ID

---

## 🔑 Common Error Codes

| Code | Meaning | Solution |
|------|---------|----------|
| 400 | Bad Request | Check required fields, verify data format |
| 401 | Unauthorized | Login first, check session |
| 404 | Not Found | Resource doesn't exist, check ID |
| 422 | Validation Error | Check error messages, fix input data |
| 500 | Server Error | Check server logs, contact admin |

---

## 🧪 Testing Endpoints

### Using cURL (Command Line)

```bash
# Create student
curl -X POST http://localhost/quanlyktx/api/students/save.php \
  -H "Content-Type: application/json" \
  -d '{"code":"SV001","full_name":"Nguyen A","email":"a@example.com","priority_level":3}'

# Delete student
curl -X POST http://localhost/quanlyktx/api/students/delete.php \
  -H "Content-Type: application/json" \
  -d '{"id":5}'
```

### Using JavaScript Fetch

```javascript
// Helper function
async function apiCall(endpoint, data) {
    const response = await fetch(`/quanlyktx/api/${endpoint}`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    });
    return response.json();
}

// Usage
const result = await apiCall('students/save.php', {
    code: 'SV001',
    full_name: 'Nguyễn A',
    email: 'a@example.com',
    priority_level: 3
});

if (result.ok) {
    console.log('Student created:', result.data);
} else {
    console.error('Error:', result.errors);
}
```

### Using Postman

1. Open Postman
2. Create new request
3. Method: **POST**
4. URL: `http://localhost/quanlyktx/api/students/save.php`
5. Headers: `Content-Type: application/json`
6. Body (raw JSON):
   ```json
   {
     "code": "SV001",
     "full_name": "Nguyễn A",
     "email": "a@example.com",
     "priority_level": 3
   }
   ```
7. Click **Send**

---

## 📊 Data Validation Rules

### Students
- `code`: Required, unique, max 50 chars
- `full_name`: Required, string, max 255 chars
- `email`: Required, unique, valid email format
- `priority_level`: Required, integer 1-8

### Rooms
- `room_number`: Required, unique, max 20 chars
- `max_occupancy`: Required, positive integer
- `status`: Required, one of: "Còn trống", "Đang ở", "Đầy", "Sửa chữa"

### Contracts
- `student_id`: Required, must exist
- `room_id`: Required, must exist
- `start_date`: Required, valid date YYYY-MM-DD
- `end_date`: Required, valid date YYYY-MM-DD, must be after start_date

### Bills
- `contract_id`: Required, must exist
- `month`: Required, 1-12
- `year`: Required, 4-digit year
- `water_usage`: Required, non-negative number
- `electric_usage`: Required, non-negative number
- `amount`: Required, non-negative number

---

**Phiên bản:** 1.0  
**Cập nhật:** 2024-05-14
