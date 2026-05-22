<?php

declare(strict_types=1);

final class StudentRepository
{
    public static function all(): array
    {
        $sql = '
            SELECT s.student_id, s.full_name, s.student_code, s.dob, s.phone, s.email, s.department, s.status, s.priority_level, s.boarding_score,
                   r.room_id, r.room_number, r.floor_number,
                                     c.contract_id, c.start_date, c.end_date, c.status AS contract_status,
                                     CASE WHEN c.contract_id IS NOT NULL THEN \'Đang ở\' ELSE s.status END AS display_status
              FROM Student s
         LEFT JOIN Contract c ON c.student_id = s.student_id AND c.status = \'Đang ở\'
         LEFT JOIN Room r ON r.room_id = c.room_id
          ORDER BY s.student_id DESC
        ';

        return Database::connection()->query($sql)->fetchAll();
    }

    public static function find(int $studentId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM Student WHERE student_id = :id LIMIT 1');
        $stmt->execute([':id' => $studentId]);
        $student = $stmt->fetch();
        return $student ?: null;
    }

    /**
     * Kiểm tra mã sinh viên đã tồn tại 
     */
    public static function isStudentCodeDuplicate(string $code, int $excludeStudentId = 0): bool
    {
        $db = Database::connection();
        if ($excludeStudentId > 0) {
            $stmt = $db->prepare('SELECT COUNT(*) FROM Student WHERE student_code = :code AND student_id != :id');
            $stmt->execute([':code' => $code, ':id' => $excludeStudentId]);
        } else {
            $stmt = $db->prepare('SELECT COUNT(*) FROM Student WHERE student_code = :code');
            $stmt->execute([':code' => $code]);
        }
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Kiểm tra định dạng email hợp lệ
     */
    public static function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate dữ liệu sinh viên trước lưu
     * Trả về array ['ok' => bool, 'errors' => []]
     */
    public static function validate(array $data, int $studentId = 0): array
    {
        $errors = [];

        $email = self::normalizeNullableString($data['email'] ?? null);
        if ($email && !self::isValidEmail($email)) {
            $errors[] = 'Email không hợp lệ';
        }

        $studentCode = self::normalizeNullableString($data['student_code'] ?? null);
        if ($studentCode && self::isStudentCodeDuplicate($studentCode, $studentId)) {
            $errors[] = 'Mã sinh viên đã tồn tại';
        }

        return [
            'ok' => empty($errors),
            'errors' => $errors
        ];
    }

    public static function save(array $data): int
    {
        $db = Database::connection();
        $studentId = (int) ($data['student_id'] ?? 0);
        $payload = [
            ':full_name' => (string) ($data['full_name'] ?? ''),
            ':student_code' => self::normalizeNullableString($data['student_code'] ?? null),
            ':dob' => self::normalizeNullableString($data['dob'] ?? null),
            ':phone' => self::normalizeNullableString($data['phone'] ?? null),
            ':email' => self::normalizeNullableString($data['email'] ?? null),
            ':department' => self::normalizeNullableString($data['department'] ?? null),
            ':status' => (string) ($data['status'] ?? 'Chờ duyệt'),
            ':priority_level' => (int) ($data['priority_level'] ?? 8),
            ':boarding_score' => (int) ($data['boarding_score'] ?? 100),
        ];

        if ($studentId > 0) {
            $payload[':student_id'] = $studentId;
            $sql = 'UPDATE Student SET full_name = :full_name, student_code = :student_code, dob = :dob, phone = :phone, email = :email, department = :department, status = :status, priority_level = :priority_level, boarding_score = :boarding_score WHERE student_id = :student_id';
            $stmt = $db->prepare($sql);
            $stmt->execute($payload);
            return $studentId;
        }

        $sql = 'INSERT INTO Student (full_name, student_code, dob, phone, email, department, status, priority_level, boarding_score) VALUES (:full_name, :student_code, :dob, :phone, :email, :department, :status, :priority_level, :boarding_score)';
        $stmt = $db->prepare($sql);
        $stmt->execute($payload);
        return (int) $db->lastInsertId();
    }

    public static function delete(int $studentId): bool
    {
        $stmt = Database::connection()->prepare('DELETE FROM Student WHERE student_id = :id');
        return $stmt->execute([':id' => $studentId]);
    }

    public static function register(array $data): int
    {
        $data['status'] = 'Chờ duyệt';
        $data['priority_level'] = (int) ($data['priority_level'] ?? 8);
        $data['boarding_score'] = (int) ($data['boarding_score'] ?? 100);
        return self::save($data);
    }

    public static function rejectRegistration(int $studentId): bool
    {
        $stmt = Database::connection()->prepare("UPDATE Student SET status = 'Đã từ chối' WHERE student_id = :id AND status = 'Chờ duyệt'");
        return $stmt->execute([':id' => $studentId]);
    }

    public static function transferRoom(int $studentId, int $newRoomId): void
    {
        $db = Database::connection();
        $db->beginTransaction();

        try {
            $current = self::currentContract($studentId);
            if ($current) {
                $updateContract = $db->prepare("UPDATE Contract SET room_id = :room_id WHERE contract_id = :contract_id");
                $updateContract->execute([':room_id' => $newRoomId, ':contract_id' => $current['contract_id']]);
            } else {
                $insertContract = $db->prepare("INSERT INTO Contract (student_id, room_id, start_date, status) VALUES (:student_id, :room_id, CURDATE(), 'Đang ở')");
                $insertContract->execute([':student_id' => $studentId, ':room_id' => $newRoomId]);
            }

            $updateStudent = $db->prepare("UPDATE Student SET status = 'Đang ở' WHERE student_id = :id");
            $updateStudent->execute([':id' => $studentId]);

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function currentContract(int $studentId): ?array
    {
        $stmt = Database::connection()->prepare("SELECT * FROM Contract WHERE student_id = :id AND status = 'Đang ở' ORDER BY contract_id DESC LIMIT 1");
        $stmt->execute([':id' => $studentId]);
        $contract = $stmt->fetch();
        return $contract ?: null;
    }

    public static function registrationStats(): array
    {
        $db = Database::connection();
        return [
            'waiting' => (int) $db->query("SELECT COUNT(*) FROM Student WHERE status = 'Chờ duyệt'")->fetchColumn(),
            'living' => (int) $db->query("SELECT COUNT(*) FROM Student WHERE status = 'Đang ở'")->fetchColumn(),
            'moved' => (int) $db->query("SELECT COUNT(*) FROM Student WHERE status = 'Đã chuyển đi'")->fetchColumn(),
            'topScore' => (int) $db->query('SELECT COALESCE(MAX(boarding_score), 0) FROM Student')->fetchColumn(),
        ];
    }

    public static function topStudents(int $limit = 5): array
    {
        $stmt = Database::connection()->prepare('SELECT student_id, full_name, student_code, department, boarding_score FROM Student ORDER BY boarding_score DESC, student_id ASC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function priorityDistribution(): array
    {
        $db = Database::connection();
        $result = [];
        for ($i = 1; $i <= 8; $i++) {
            $stmt = $db->prepare('SELECT COUNT(*) FROM Student WHERE priority_level = :level AND status = \'Đang ở\'');
            $stmt->execute([':level' => $i]);
            $result[$i] = (int) $stmt->fetchColumn();
        }
        return $result;
    }

    public static function lowScoringStudents(int $threshold = 50): array
    {
        $sql = 'SELECT s.student_id, s.full_name, s.student_code, s.boarding_score, r.room_number FROM Student s LEFT JOIN Contract c ON c.student_id = s.student_id AND c.status = \'Đang ở\' LEFT JOIN Room r ON r.room_id = c.room_id WHERE s.boarding_score < :th ORDER BY s.boarding_score ASC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':th' => $threshold]);
        return $stmt->fetchAll();
    }

    private static function normalizeNullableString(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';
        return $value === '' ? null : $value;
    }
}
