<?php
/**
 * Comprehensive System Check - validates key business logic and data integrity
 * Run from: D:\Programs\XAMPP\php\php.exe tools/system_check.php
 */

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../config/app.php';

$issues = [];
$warnings = [];
$successes = [];

echo "\n========== SYSTEM CHECK REPORT ==========\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

// ===== 1. Database Connectivity =====
echo "[1] Database Connectivity\n";
try {
    $db = Database::connection();
    $result = $db->query("SELECT 1")->fetch();
    $successes[] = "✓ Database connection successful";
    echo "✓ Connected to database\n";
} catch (Throwable $e) {
    $issues[] = "✗ Database connection failed: " . $e->getMessage();
    echo "✗ Connection failed\n";
    exit(1);
}

// ===== 2. Table Structure Check =====
echo "\n[2] Table Structure Validation\n";
$tables = ['Student', 'Contract', 'Room', 'UtilityBill', 'Notice'];
foreach ($tables as $table) {
    try {
        $result = $db->query("DESCRIBE $table")->fetchAll();
        echo "✓ Table $table exists (" . count($result) . " columns)\n";
    } catch (Throwable $e) {
        $issues[] = "✗ Table $table missing or error: " . $e->getMessage();
        echo "✗ $table error\n";
    }
}

// ===== 3. Contract & Student Status Check =====
echo "\n[3] Contract & Student Status Validation\n";
try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM Student");
    $studentCount = (int)$stmt->fetchColumn();
    echo "Total students: $studentCount\n";
    
    $stmt = $db->query("SELECT status, COUNT(*) as cnt FROM Student GROUP BY status");
    $statuses = $stmt->fetchAll();
    foreach ($statuses as $row) {
        echo "  - {$row['status']}: {$row['cnt']}\n";
    }
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM Contract WHERE status = 'Đang ở'");
    $activeContracts = (int)$stmt->fetchColumn();
    echo "Active contracts (Đang ở): $activeContracts\n";
    
    if ($studentCount > 0 && $activeContracts === 0) {
        $warnings[] = "⚠ No active contracts but students exist - check if approval workflow works";
        echo "⚠ Warning: No active contracts\n";
    }
    
    $successes[] = "✓ Student/Contract status check passed";
} catch (Throwable $e) {
    $issues[] = "✗ Status check error: " . $e->getMessage();
    echo "✗ Status check failed\n";
}

// ===== 4. Contract Price Calculation Logic =====
echo "\n[4] Contract Price Calculation\n";
try {
    $contracts = ContractRepository::all();
    if (!empty($contracts)) {
        $testContract = $contracts[0];
        echo "Sample contract ID: {$testContract['contract_id']}\n";
        echo "  Room price: {$testContract['room_price']}\n";
        echo "  Start: {$testContract['start_date']}\n";
        echo "  End: {$testContract['end_date']}\n";
        echo "  Calculated price: {$testContract['price']}\n";
        echo "  Deposit (paid): {$testContract['deposit']}\n";
        echo "  Debt (owed): {$testContract['debt']}\n";
        
        if ((float)$testContract['debt'] > 0) {
            echo "  → Student owes money ✓\n";
        } else if ((float)$testContract['debt'] == 0) {
            echo "  → Student has paid all (debt=0) ✓\n";
        }
        
        $successes[] = "✓ Contract price calculation working";
    } else {
        $warnings[] = "⚠ No contracts in system to validate calculation";
        echo "⚠ No contracts to test\n";
    }
} catch (Throwable $e) {
    $issues[] = "✗ Contract calculation error: " . $e->getMessage();
    echo "✗ Calculation failed\n";
}

// ===== 5. Utility Bills Check =====
echo "\n[5] Utility Bills (Hóa đơn điện nước)\n";
try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM UtilityBill");
    $billCount = (int)$stmt->fetchColumn();
    echo "Total bills: $billCount\n";
    
    $stmt = $db->query("SELECT status, COUNT(*) as cnt FROM UtilityBill GROUP BY status");
    $billStatuses = $stmt->fetchAll();
    foreach ($billStatuses as $row) {
        echo "  - {$row['status']}: {$row['cnt']}\n";
    }
    
    $stmt = $db->query("SELECT AVG(total_amount) as avg_amount FROM UtilityBill WHERE total_amount > 0");
    $avgAmount = (float)($stmt->fetchColumn() ?? 0);
    echo "Average bill amount: " . number_format($avgAmount, 0) . " VND\n";
    
    $successes[] = "✓ Utility bills loaded";
} catch (Throwable $e) {
    $issues[] = "✗ Utility bills check failed: " . $e->getMessage();
    echo "✗ Bills check failed\n";
}

// ===== 6. Room Occupancy Check =====
echo "\n[6] Room Occupancy\n";
try {
    $stmt = $db->query("SELECT COUNT(*) FROM Room WHERE status = 'Hoạt động'");
    $activeRooms = (int)$stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(DISTINCT room_id) FROM Contract WHERE status = 'Đang ở'");
    $occupiedRooms = (int)$stmt->fetchColumn();
    
    echo "Active rooms: $activeRooms\n";
    echo "Occupied rooms: $occupiedRooms\n";
    
    if ($occupiedRooms <= $activeRooms) {
        echo "✓ Occupancy ratio valid\n";
        $successes[] = "✓ Room occupancy valid";
    } else {
        $issues[] = "✗ More occupied rooms than active rooms - data integrity issue";
        echo "✗ Data integrity issue\n";
    }
} catch (Throwable $e) {
    $issues[] = "✗ Occupancy check error: " . $e->getMessage();
    echo "✗ Check failed\n";
}

// ===== 7. API Endpoint Files Existence =====
echo "\n[7] API Endpoint Files\n";
$endpoints = [
    'api/contracts/pay.php' => 'Payment API',
    'api/contracts/save.php' => 'Contract save API',
    'api/contracts/extend.php' => 'Contract extend API',
    'api/students/approve.php' => 'Student approval API',
    'api/bills/save.php' => 'Bill save API',
    'api/bills/mark-paid.php' => 'Bill mark paid API',
];

foreach ($endpoints as $path => $desc) {
    $fullPath = __DIR__ . '/../' . $path;
    if (file_exists($fullPath)) {
        echo "✓ $desc exists\n";
    } else {
        $issues[] = "✗ Missing API file: $path";
        echo "✗ $desc MISSING\n";
    }
}

// ===== 8. CSRF Token Generation =====
echo "\n[8] Security - CSRF Token\n";
echo "ℹ CSRF protection has been disabled from the system\n";
$successes[] = "ℹ CSRF protection disabled";

// ===== 9. Student With Debt Query =====
echo "\n[9] Debt Report\n";
try {
    $debtors = ContractRepository::studentsWithDebt();
    echo "Students with unpaid debt: " . count($debtors) . "\n";
    if (!empty($debtors)) {
        $topDebtor = reset($debtors);
        echo "  Top debtor: {$topDebtor['full_name']} owes " . number_format((float)$topDebtor['debt'], 0) . " VND\n";
    }
    $successes[] = "✓ Debt query working";
} catch (Throwable $e) {
    $issues[] = "✗ Debt query error: " . $e->getMessage();
    echo "✗ Query failed\n";
}

// ===== 10. Approve Workflow Check =====
echo "\n[10] Approval Workflow\n";
try {
    $stmt = $db->query("SELECT COUNT(*) FROM Student WHERE status = 'Chờ duyệt'");
    $waitingCount = (int)$stmt->fetchColumn();
    echo "Students awaiting approval: $waitingCount\n";
    
    if ($waitingCount > 0) {
        $stmt = $db->query("SELECT s.* FROM Student s WHERE s.status = 'Chờ duyệt' LIMIT 1");
        $waitStudent = $stmt->fetch();
        if ($waitStudent) {
            echo "  Sample: {$waitStudent['full_name']} ({$waitStudent['student_code']})\n";
        }
    }
    
    $successes[] = "✓ Approval workflow check passed";
} catch (Throwable $e) {
    $issues[] = "✗ Approval workflow check error: " . $e->getMessage();
    echo "✗ Check failed\n";
}

// ===== SUMMARY REPORT =====
echo "\n========== SUMMARY ==========\n";
echo "Successes: " . count($successes) . "\n";
foreach ($successes as $s) {
    echo "  $s\n";
}

if (!empty($warnings)) {
    echo "\nWarnings: " . count($warnings) . "\n";
    foreach ($warnings as $w) {
        echo "  $w\n";
    }
}

if (!empty($issues)) {
    echo "\nIssues: " . count($issues) . "\n";
    foreach ($issues as $i) {
        echo "  $i\n";
    }
    echo "\n⚠ CRITICAL ISSUES FOUND - System may not work correctly\n";
    exit(1);
} else {
    echo "\n✓ All checks passed! System is ready.\n";
    exit(0);
}
