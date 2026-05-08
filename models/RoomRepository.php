<?php

declare(strict_types=1);

final class RoomRepository
{
    public static function all(): array
    {
        $sql = '
            SELECT r.*,
                   COUNT(c.contract_id) AS occupied_count,
                   ROUND(COALESCE(AVG(s.boarding_score), 0), 2) AS avg_boarding_score
              FROM Room r
         LEFT JOIN Contract c ON c.room_id = r.room_id AND c.status = \'Đang ở\'
         LEFT JOIN Student s ON s.student_id = c.student_id
          GROUP BY r.room_id
          ORDER BY r.room_number ASC
        ';

        return Database::connection()->query($sql)->fetchAll();
    }

    public static function selectOptions(): array
    {
        $stmt = Database::connection()->query('SELECT room_id, room_number, floor_number, capacity, room_type, status, price FROM Room ORDER BY room_number ASC');
        return $stmt->fetchAll();
    }

    public static function occupiedSelectOptions(): array
    {
        $sql = "
            SELECT r.room_id, r.room_number, r.floor_number, r.capacity, r.room_type, r.status, r.price
              FROM Room r
             WHERE r.status = 'Hoạt động'
               AND EXISTS (
                   SELECT 1
                     FROM Contract c
                    WHERE c.room_id = r.room_id
                      AND c.status = 'Đang ở'
               )
             ORDER BY r.room_number ASC
        ";

        $stmt = Database::connection()->query($sql);
        return $stmt->fetchAll();
    }

    public static function find(int $roomId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM Room WHERE room_id = :id LIMIT 1');
        $stmt->execute([':id' => $roomId]);
        $room = $stmt->fetch();
        return $room ?: null;
    }

    public static function save(array $data): int
    {
        $db = Database::connection();
        $roomId = (int) ($data['room_id'] ?? 0);
        $floor = (int) ($data['floor_number'] ?? 0);
        $roomSeq = (int) ($data['room_sequence'] ?? ($data['room_number'] ?? 0));
        $roomNum = $floor * 100 + $roomSeq;
        
        $payload = [
            ':room_number' => $roomNum,
            ':floor_number' => $floor,
            ':capacity' => (int) ($data['capacity'] ?? 0),
            ':room_type' => (string) ($data['room_type'] ?? 'Thường'),
            ':status' => (string) ($data['status'] ?? 'Hoạt động'),
            ':price' => (float) ($data['price'] ?? 0),
        ];

        if ($roomId > 0) {
            $payload[':room_id'] = $roomId;
            $sql = 'UPDATE Room SET room_number = :room_number, floor_number = :floor_number, capacity = :capacity, room_type = :room_type, status = :status, price = :price WHERE room_id = :room_id';
            $stmt = $db->prepare($sql);
            $stmt->execute($payload);
            return $roomId;
        }

        $sql = 'INSERT INTO Room (room_number, floor_number, capacity, room_type, status, price) VALUES (:room_number, :floor_number, :capacity, :room_type, :status, :price)';
        $stmt = $db->prepare($sql);
        $stmt->execute($payload);
        return (int) $db->lastInsertId();
    }

    public static function delete(int $roomId): bool
    {
        $stmt = Database::connection()->prepare('DELETE FROM Room WHERE room_id = :id');
        return $stmt->execute([':id' => $roomId]);
    }

    public static function stats(): array
    {
        $db = Database::connection();
        $totalRooms = (int) $db->query('SELECT COUNT(*) FROM Room')->fetchColumn();
        $activeRooms = (int) $db->query("SELECT COUNT(*) FROM Room WHERE status = 'Hoạt động'")->fetchColumn();
        $totalCapacity = (int) $db->query('SELECT COALESCE(SUM(capacity), 0) FROM Room')->fetchColumn();
        $occupied = (int) $db->query("SELECT COUNT(*) FROM Contract WHERE status = 'Đang ở'")->fetchColumn();

        return compact('totalRooms', 'activeRooms', 'totalCapacity', 'occupied');
    }

    public static function topRooms(int $limit = 5): array
    {
        $sql = '
            SELECT r.room_id, r.room_number, r.floor_number, r.capacity, r.price, r.status,
                   COUNT(c.contract_id) AS occupancy,
                   ROUND(COALESCE(AVG(s.boarding_score), 0), 2) AS avg_boarding_score
              FROM Room r
         LEFT JOIN Contract c ON c.room_id = r.room_id AND c.status = \'Đang ở\'
         LEFT JOIN Student s ON s.student_id = c.student_id
          GROUP BY r.room_id
          ORDER BY occupancy DESC, avg_boarding_score DESC, r.room_number ASC
             LIMIT :limit
        ';
        $stmt = Database::connection()->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function studentsByRoom(int $roomId): array
    {
        $sql = '
            SELECT s.student_id, s.full_name, s.student_code, s.department, s.boarding_score,
                   c.contract_id, c.start_date, c.end_date, c.status
              FROM Student s
              JOIN Contract c ON c.student_id = s.student_id
             WHERE c.room_id = :room_id AND c.status = \'Đang ở\'
             ORDER BY c.start_date DESC
        ';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':room_id' => $roomId]);
        return $stmt->fetchAll();
    }

    public static function roomStatusDistribution(): array
    {
        $db = Database::connection();
        return [
            'empty' => (int) $db->query("SELECT COUNT(*) FROM Room r WHERE r.status = 'Hoạt động' AND r.room_id NOT IN (SELECT DISTINCT room_id FROM Contract WHERE status = 'Đang ở')")->fetchColumn(),
            'occupied' => (int) $db->query("SELECT COUNT(DISTINCT r.room_id) FROM Room r JOIN Contract c ON c.room_id = r.room_id AND c.status = 'Đang ở' WHERE r.status = 'Hoạt động' AND (SELECT COUNT(*) FROM Contract WHERE room_id = r.room_id AND status = 'Đang ở') < r.capacity")->fetchColumn(),
            'full' => (int) $db->query("SELECT COUNT(DISTINCT r.room_id) FROM Room r WHERE r.status = 'Hoạt động' AND (SELECT COUNT(*) FROM Contract WHERE room_id = r.room_id AND status = 'Đang ở') >= r.capacity")->fetchColumn(),
            'maintenance' => (int) $db->query("SELECT COUNT(*) FROM Room WHERE status = 'Đang sửa chữa'")->fetchColumn(),
        ];
    }
}
