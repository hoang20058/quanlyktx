<?php
/**
 * Fix Database: Mark living students' contracts as paid
 * Sets deposit = price for all "Đang ở" (living) contracts
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

try {
    $db = Database::connection();
    
    // Get all living students with their contract details
    $sql = "
        SELECT 
            c.contract_id,
            c.student_id,
            c.room_id,
            c.start_date,
            c.end_date,
            c.deposit,
            c.discount_percent,
            r.price AS room_price,
            s.priority_level,
            s.full_name
        FROM Contract c
        JOIN Student s ON s.student_id = c.student_id
        JOIN Room r ON r.room_id = c.room_id
        WHERE c.status = 'Đang ở'
    ";
    
    $contracts = $db->query($sql)->fetchAll();
    
    echo "=== KTX PAYMENT FIX ===\n";
    echo "Found " . count($contracts) . " living students.\n\n";
    
    $updateCount = 0;
    $totalAmount = 0;
    
    foreach ($contracts as $contract) {
        // Calculate room fee (price)
        $startDate = new DateTime($contract['start_date']);
        $endDate = new DateTime($contract['end_date']);
        $interval = $startDate->diff($endDate);
        $daysInContract = $interval->days;
        
        $discount = (int)$contract['discount_percent'];
        $basePrice = ($contract['room_price'] / 30) * $daysInContract;
        $finalPrice = $basePrice * (100 - $discount) / 100;
        $finalPrice = round($finalPrice, 2);
        
        $currentDeposit = (float)($contract['deposit'] ?? 0);
        $debt = $finalPrice - $currentDeposit;
        
        // Only update if there's debt to settle
        if ($debt > 0) {
            $stmt = $db->prepare('UPDATE Contract SET deposit = :amount WHERE contract_id = :id');
            $stmt->execute([
                ':amount' => $finalPrice,
                ':id' => $contract['contract_id']
            ]);
            
            $updateCount++;
            $totalAmount += $debt;
            
            printf(
                "✓ SV: %s | P%s | Nợ: %s đ → Đã thanh toán\n",
                str_pad($contract['full_name'], 30),
                str_pad((string)$contract['room_number'], 3),
                number_format($debt, 0, ',', '.')
            );
        }
    }
    
    echo "\n=== SUMMARY ===\n";
    echo "Records updated: " . $updateCount . "\n";
    echo "Total amount settled: " . number_format($totalAmount, 0, ',', '.') . " đ\n";
    echo "\n✓ Database fix completed successfully!\n";
    
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
