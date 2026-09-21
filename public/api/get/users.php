<?php
// Legacy URL kept for compatibility. New clients should use ../users.php.
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
bdr_start_session();
bdr_require_method(['GET']);
bdr_require_auth(['admin']);

$stmt = $pdo->query('SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC, id DESC');
bdr_json_response(['status' => 'ok', 'users' => $stmt->fetchAll()]);
