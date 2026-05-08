<?php
// Run this script from browser or CLI to import database/seeding.sql using project's Database connection

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

try {
    $sql = file_get_contents(__DIR__ . '/../database/seeding.sql');
    if ($sql === false) {
        throw new RuntimeException('Unable to open seeding.sql');
    }

    $db = Database::connection();
    $db->beginTransaction();
    $statements = array_filter(array_map('trim', explode(";", $sql)));
    $count = 0;
    foreach ($statements as $stmt) {
        if ($stmt === '') continue;
        $db->exec($stmt);
        $count++;
    }
    $db->commit();
    echo "Imported OK. Executed statements: {$count}";
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    echo 'Error: ' . $e->getMessage();
}
