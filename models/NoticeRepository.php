<?php

declare(strict_types=1);

final class NoticeRepository
{
    public static function all(): array
    {
        $sql = '
            SELECT n.*, r.room_number, s.full_name AS student_name
              FROM Notice n
         LEFT JOIN Room r ON r.room_id = n.room_id
         LEFT JOIN Student s ON s.student_id = n.student_id
          ORDER BY n.date DESC, n.notice_id DESC
        ';

        return Database::connection()->query($sql)->fetchAll();
    }

    public static function find(int $noticeId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM Notice WHERE notice_id = :id LIMIT 1');
        $stmt->execute([':id' => $noticeId]);
        $notice = $stmt->fetch();
        return $notice ?: null;
    }

    public static function save(array $data): int
    {
        $db = Database::connection();
        $noticeId = (int) ($data['notice_id'] ?? 0);
        $existing = $noticeId > 0 ? self::find($noticeId) : null;
        $payload = [
            ':target_type' => (string) ($data['target_type'] ?? 'Cả tòa'),
            ':category' => (string) ($data['category'] ?? 'Thông báo chung'),
            ':point_change' => (int) ($data['point_change'] ?? 0),
            ':room_id' => self::nullableInt($data['room_id'] ?? null),
            ':student_id' => self::nullableInt($data['student_id'] ?? null),
            ':description' => self::nullableString($data['description'] ?? null),
            ':date' => (string) ($data['date'] ?? date('Y-m-d')),
        ];

        if ($noticeId > 0) {
            if ($existing && (int) ($existing['point_change'] ?? 0) !== 0) {
                self::applyPointChange(
                    (string) $existing['target_type'],
                    -((int) $existing['point_change']),
                    isset($existing['room_id']) ? (int) $existing['room_id'] : null,
                    isset($existing['student_id']) ? (int) $existing['student_id'] : null
                );
            }

            $payload[':notice_id'] = $noticeId;
            $sql = 'UPDATE Notice SET target_type = :target_type, category = :category, point_change = :point_change, room_id = :room_id, student_id = :student_id, description = :description, date = :date WHERE notice_id = :notice_id';
            $stmt = $db->prepare($sql);
            $stmt->execute($payload);
        } else {
            $sql = 'INSERT INTO Notice (target_type, category, point_change, room_id, student_id, description, date) VALUES (:target_type, :category, :point_change, :room_id, :student_id, :description, :date)';
            $stmt = $db->prepare($sql);
            $stmt->execute($payload);
            $noticeId = (int) $db->lastInsertId();
        }

        if ($payload[':point_change'] !== 0) {
            self::applyPointChange($payload[':target_type'], $payload[':point_change'], $payload[':room_id'], $payload[':student_id']);
        }

        return $noticeId;
    }

    public static function delete(int $noticeId): bool
    {
        $db = Database::connection();
        
        // Lấy thông tin Notice trước khi xóa để revert point
        $notice = self::find($noticeId);
        if ($notice && (int) ($notice['point_change'] ?? 0) !== 0) {
            // Revert điểm (trừ đi điểm_change để quay lại trạng thái cũ)
            self::applyPointChange(
                (string) $notice['target_type'],
                -((int) $notice['point_change']),
                isset($notice['room_id']) ? (int) $notice['room_id'] : null,
                isset($notice['student_id']) ? (int) $notice['student_id'] : null
            );
        }
        
        // Sau đó xóa Notice
        $stmt = $db->prepare('DELETE FROM Notice WHERE notice_id = :id');
        return $stmt->execute([':id' => $noticeId]);
    }

    public static function applyPointChange(string $targetType, int $pointChange, ?int $roomId = null, ?int $studentId = null): void
    {
        $db = Database::connection();

        if ($targetType === 'Cá nhân' && $studentId) {
            $stmt = $db->prepare('UPDATE Student SET boarding_score = boarding_score + :change WHERE student_id = :id');
            $stmt->execute([':change' => $pointChange, ':id' => $studentId]);
            return;
        }

        if ($targetType === 'Phòng' && $roomId) {
            $stmt = $db->prepare('
                UPDATE Student s
                JOIN Contract c ON c.student_id = s.student_id AND c.status = \'Đang ở\'
                   SET s.boarding_score = s.boarding_score + :change
                 WHERE c.room_id = :room_id
            ');
            $stmt->execute([':change' => $pointChange, ':room_id' => $roomId]);
            return;
        }

        if ($targetType === 'Cả tòa') {
            $stmt = $db->prepare('UPDATE Student SET boarding_score = boarding_score + :change WHERE status = \'Đang ở\'');
            $stmt->execute([':change' => $pointChange]);
        }
    }

    private static function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (int) $value;
    }

    private static function nullableString(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';
        return $value === '' ? null : $value;
    }
}
