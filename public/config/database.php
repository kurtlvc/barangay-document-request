<?php
// Database connection settings.

$DB_HOST = '127.0.0.1:3307';
$DB_NAME = 'barangay_system';
$DB_USER = 'root';
$DB_PASS = '';

$host = $DB_HOST;
$port = 3306;
if (str_contains($DB_HOST, ':')) {
    [$host, $port] = explode(':', $DB_HOST, 2);
}

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['status'=>'error','message' => 'Database connection failed: ' . $e->getMessage()]));
}
