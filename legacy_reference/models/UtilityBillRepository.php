<?php

declare(strict_types=1);

final class UtilityBillRepository
{
    public static function all(): array
    {
        $sql = '
            SELECT b.bill_id, b.room_id, b.billing_month, b.billing_year, b.total_amount, b.status,
                   r.room_number, r.floor_number
              FROM UtilityBill b
              JOIN Room r ON b.room_id = r.room_id
             ORDER BY b.billing_year DESC, b.billing_month DESC, b.bill_id DESC
        ';
        return Database::connection()->query($sql)->fetchAll();
    }

    public static function find(int $billId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM UtilityBill WHERE bill_id = :id LIMIT 1');
        $stmt->execute([':id' => $billId]);
        return $stmt->fetch() ?: null;
    }

    public static function save(array $data): int
    {
        $db = Database::connection();
        $billId = (int) ($data['bill_id'] ?? 0);
        $existing = null;
        if ($billId > 0) {
            $existing = self::find($billId);
        }
        $payload = [
            ':room_id' => (int) ($data['room_id'] ?? 0),
            ':billing_month' => (int) ($data['billing_month'] ?? date('n')),
            ':billing_year' => (int) ($data['billing_year'] ?? date('Y')),
            ':total_amount' => (float) ($data['total_amount'] ?? 0),
            ':status' => (string) ($data['status'] ?? 'Chưa thanh toán'),
            ':new_electric_index' => (float) ($data['new_electric_index'] ?? ($existing['new_electric_index'] ?? 0)),
            ':new_water_index' => (float) ($data['new_water_index'] ?? ($existing['new_water_index'] ?? 0)),
        ];

        if ($billId > 0) {
            $payload[':bill_id'] = $billId;
            $sql = 'UPDATE UtilityBill SET room_id = :room_id, billing_month = :billing_month, billing_year = :billing_year, total_amount = :total_amount, status = :status, new_electric_index = :new_electric_index, new_water_index = :new_water_index WHERE bill_id = :bill_id';
            $stmt = $db->prepare($sql);
            $stmt->execute($payload);
            return $billId;
        }

        $sql = 'INSERT INTO UtilityBill (room_id, billing_month, billing_year, total_amount, status, new_electric_index, new_water_index) VALUES (:room_id, :billing_month, :billing_year, :total_amount, :status, :new_electric_index, :new_water_index)';
        $stmt = $db->prepare($sql);
        $stmt->execute($payload);
        return (int) $db->lastInsertId();
    }

    public static function getLatestIndicesByRoom(int $roomId): ?array
    {
        $stmt = Database::connection()->prepare('
            SELECT new_electric_index, new_water_index
              FROM UtilityBill
             WHERE room_id = :room_id
             ORDER BY billing_year DESC, billing_month DESC, bill_id DESC
             LIMIT 1
        ');
        $stmt->execute([':room_id' => $roomId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function delete(int $billId): bool
    {
        $stmt = Database::connection()->prepare('DELETE FROM UtilityBill WHERE bill_id = :id');
        return $stmt->execute([':id' => $billId]);
    }

    public static function existsForRoomAndMonthYear(int $roomId, int $month, int $year): bool
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(1) AS c FROM UtilityBill WHERE room_id = :room_id AND billing_month = :m AND billing_year = :y');
        $stmt->execute([':room_id' => $roomId, ':m' => $month, ':y' => $year]);
        $row = $stmt->fetch();
        return (int) ($row['c'] ?? 0) > 0;
    }

    public static function unpaidBills(): array
    {
        $sql = '
            SELECT b.*, r.room_number
              FROM UtilityBill b
              JOIN Room r ON b.room_id = r.room_id
             WHERE b.status = \'Chưa thanh toán\'
             ORDER BY b.billing_year DESC, b.billing_month DESC
        ';
        return Database::connection()->query($sql)->fetchAll();
    }

    public static function billsByRoom(int $roomId): array
    {
        $sql = '
            SELECT b.* FROM UtilityBill b
             WHERE b.room_id = :room_id
             ORDER BY b.billing_year DESC, b.billing_month DESC
        ';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':room_id' => $roomId]);
        return $stmt->fetchAll();
    }

    public static function latestUnpaidByRoom(int $roomId): ?array
    {
        $stmt = Database::connection()->prepare('
            SELECT b.*
              FROM UtilityBill b
             WHERE b.room_id = :room_id AND b.status = :status
             ORDER BY b.billing_year DESC, b.billing_month DESC, b.bill_id DESC
             LIMIT 1
        ');
        $stmt->execute([':room_id' => $roomId, ':status' => 'Chưa thanh toán']);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
