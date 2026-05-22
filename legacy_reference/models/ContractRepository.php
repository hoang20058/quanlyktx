<?php

declare(strict_types=1);

final class ContractRepository
{
    public static function all(): array
    {
        // 1. Đã xóa tính toán debt và c.price khỏi SQL
        $sql = '
            SELECT c.*, s.full_name, s.student_code, s.priority_level, r.room_number, r.price AS room_price
              FROM Contract c
              JOIN Student s ON s.student_id = c.student_id
              JOIN Room r ON r.room_id = c.room_id
          ORDER BY c.contract_id DESC
        ';

        $contracts = Database::connection()->query($sql)->fetchAll();

        // 2. Tính toán giá (price) và công nợ (debt) động bằng PHP
        foreach ($contracts as &$contract) {
            $discount = $contract['discount_percent'] ?? self::getDiscountByPriority((int)($contract['priority_level'] ?? 8));
            $price = self::calculateRoomFee((float)$contract['room_price'], $contract['start_date'], $contract['end_date'], (int)$discount);
            
            $contract['price'] = $price; // Gán lại vào mảng để View FE không bị lỗi
            $contract['debt'] = $price - (float)($contract['deposit'] ?? 0);
        }

        return $contracts;
    }

    public static function activeByStudent(int $studentId): ?array
    {
        $stmt = Database::connection()->prepare("SELECT c.*, r.price AS room_price FROM Contract c JOIN Room r ON r.room_id = c.room_id WHERE c.student_id = :id AND c.status = 'Đang ở' LIMIT 1");
        $stmt->execute([':id' => $studentId]);
        $contract = $stmt->fetch();
        
        if ($contract) {
            // Tính toán giá động
            $discount = $contract['discount_percent'] ?? 0;
            $contract['price'] = self::calculateRoomFee((float)$contract['room_price'], $contract['start_date'], $contract['end_date'], (int)$discount);
        }
        
        return $contract ?: null;
    }

    public static function find(int $contractId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT c.*, r.price AS room_price FROM Contract c JOIN Room r ON r.room_id = c.room_id WHERE c.contract_id = :id LIMIT 1');
        $stmt->execute([':id' => $contractId]);
        $contract = $stmt->fetch();
        
        if ($contract) {
            $discount = $contract['discount_percent'] ?? 0;
            $contract['price'] = self::calculateRoomFee((float)$contract['room_price'], $contract['start_date'], $contract['end_date'], (int)$discount);
        }
        
        return $contract ?: null;
    }

    public static function activeContracts(): array
    {
        $sql = "SELECT * FROM Contract WHERE status = 'Đang ở'";
        return Database::connection()->query($sql)->fetchAll();
    }

    public static function studentsWithDebt(): array
    {
        $sql = "
            SELECT c.*, s.full_name, s.student_code, s.priority_level, r.room_number, r.price AS room_price
              FROM Contract c
              JOIN Student s ON s.student_id = c.student_id
              JOIN Room r ON r.room_id = c.room_id
             WHERE c.status = 'Đang ở'
        ";

        $contracts = Database::connection()->query($sql)->fetchAll();
        $result = [];

        foreach ($contracts as $contract) {
            $discount = $contract['discount_percent'] ?? self::getDiscountByPriority((int)($contract['priority_level'] ?? 8));
            $price = self::calculateRoomFee((float)$contract['room_price'], $contract['start_date'], $contract['end_date'], (int)$discount);
            $deposit = (float)($contract['deposit'] ?? 0);
            $debt = $price - $deposit;

            // Nếu nợ > 0 thì mới đưa vào danh sách
            if ($debt > 0) {
                $contract['price'] = $price;
                $contract['debt'] = $debt;
                $result[] = $contract;
            }
        }

        // Sắp xếp mảng theo số nợ giảm dần (DESC)
        usort($result, function($a, $b) {
            return $b['debt'] <=> $a['debt'];
        });

        return $result;
    }

    /**
     * Nhận trực tiếp $roomPrice thay vì $roomId để tránh lỗi N+1 Query.
     */
    public static function calculateRoomFee(float $roomPrice, string $startDate, ?string $endDate, int $discountPercent = 0): float
    {
        if (!$endDate) {
            return 0;
        }

        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $interval = $start->diff($end);
        $daysInContract = $interval->days;
        
        // Công thức: (Giá phòng / 30 ngày * Số ngày ở) * Phần trăm còn lại sau khi giảm
        $basePrice = ($roomPrice / 30) * $daysInContract;
        $finalPrice = $basePrice * (100 - $discountPercent) / 100;

        return round($finalPrice, 2);
    }

    public static function getDiscountByPriority(int $priorityLevel): int
    {
        if ($priorityLevel <= 2) return 50;    // Hộ nghèo/Chính sách
        if ($priorityLevel <= 4) return 30;    // Hộ cận nghèo
        return 10;                             // Hộ khác/Bình thường
    }

    public static function save(array $data): int
    {
        $db = Database::connection();
        $contractId = (int) ($data['contract_id'] ?? 0);
        $studentId = (int) ($data['student_id'] ?? 0);
        $roomId = (int) ($data['room_id'] ?? 0);
        $startDate = (string) ($data['start_date'] ?? date('Y-m-d'));
        $endDate = self::normalizeNullableString($data['end_date'] ?? null);
        $deposit = (float) ($data['deposit'] ?? 0);
        $discountPercent = (int) ($data['discount_percent'] ?? 0);

        if ($discountPercent === 0 && $studentId > 0) {
            $stmt = $db->prepare('SELECT priority_level FROM Student WHERE student_id = :id');
            $stmt->execute([':id' => $studentId]);
            $priorityLevel = (int) ($stmt->fetchColumn() ?? 8);
            $discountPercent = self::getDiscountByPriority($priorityLevel);
        }

        $payload = [
            ':student_id' => $studentId,
            ':room_id' => $roomId,
            ':start_date' => $startDate,
            ':end_date' => $endDate,
            ':deposit' => $deposit,
            ':discount_percent' => $discountPercent,
            ':status' => (string) ($data['status'] ?? 'Đang ở'),
        ];

        if ($contractId > 0) {
            $payload[':contract_id'] = $contractId;
            $sql = 'UPDATE Contract SET student_id = :student_id, room_id = :room_id, start_date = :start_date, end_date = :end_date, deposit = :deposit, discount_percent = :discount_percent, status = :status WHERE contract_id = :contract_id';
            $stmt = $db->prepare($sql);
            $stmt->execute($payload);
            return $contractId;
        }

        $sql = 'INSERT INTO Contract (student_id, room_id, start_date, end_date, deposit, discount_percent, status) VALUES (:student_id, :room_id, :start_date, :end_date, :deposit, :discount_percent, :status)';
        $stmt = $db->prepare($sql);
        $stmt->execute($payload);
        return (int) $db->lastInsertId();
    }

    public static function delete(int $contractId): bool
    {
        $stmt = Database::connection()->prepare('DELETE FROM Contract WHERE contract_id = :id');
        return $stmt->execute([':id' => $contractId]);
    }

    /**
     * Thêm khoản thanh toán vào hợp đồng (tăng trường `deposit` để ghi nhận số tiền đã thu)
     */
    public static function addPayment(int $contractId, float $amount): bool
    {
        if ($amount <= 0) return false;
        $db = Database::connection();
        $stmt = $db->prepare('UPDATE Contract SET deposit = COALESCE(deposit, 0) + :amount WHERE contract_id = :id');
        return $stmt->execute([':amount' => $amount, ':id' => $contractId]);
    }

    private static function normalizeNullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        return is_string($value) ? $value : (string) $value;
    }
}