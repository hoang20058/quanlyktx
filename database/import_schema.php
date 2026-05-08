<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Env.php';

// Load env
Env::load(__DIR__ . '/../.env');

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

$schemaFile = __DIR__ . '/schema.sql';
if (!file_exists($schemaFile)) {
    echo "Schema file not found: $schemaFile\n";
    exit(1);
}

$sql = file_get_contents($schemaFile);
if ($sql === false || trim($sql) === '') {
    echo "Schema file is empty or unreadable.\n";
    exit(1);
}

// Connect via mysqli and run multi_query
$mysqli = new mysqli($host, $user, $pass, '', (int)$port);
if ($mysqli->connect_errno) {
    echo "Connect failed: ({$mysqli->connect_errno}) {$mysqli->connect_error}\n";
    exit(1);
}

// Allow multiple statements
if (!$mysqli->multi_query($sql)) {
    echo "Import failed: ({$mysqli->errno}) {$mysqli->error}\n";
    $mysqli->close();
    exit(1);
}

// Process result sets to execute all statements
do {
    if ($res = $mysqli->store_result()) {
        $res->free();
    }
} while ($mysqli->more_results() && $mysqli->next_result());

echo "Schema import finished.\n";

// Show tables in the selected database
$db = getenv('DB_NAME') ?: 'quanlyktx';
$mysqli->select_db($db);
$result = $mysqli->query('SHOW TABLES');
if ($result) {
    echo "Tables in database '{$db}':\n";
    while ($row = $result->fetch_row()) {
        echo " - {$row[0]}\n";
    }
    $result->free();
} else {
    echo "Could not list tables: ({$mysqli->errno}) {$mysqli->error}\n";
}

$mysqli->close();

exit(0);
