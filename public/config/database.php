<?php
// Database connection settings.
// In a real deployment, load these from environment variables instead of
// hardcoding them here (e.g. getenv('DB_HOST')).

$DB_HOST = '127.0.0.1:3306';
$DB_NAME = 'barangay_system';
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
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
    die(json_encode(['status'=>'error','message' => 'Database connection failed']));
}
