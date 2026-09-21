<?php
// Legacy URL kept for compatibility. New clients should use ../residents.php.
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
bdr_start_session();
bdr_require_method(['GET']);
$user = bdr_require_auth(['staff', 'admin']);

$stmt = $pdo->query('SELECT resident_id, user_id, first_name, last_name, address, contact_number, status, created_at FROM residents ORDER BY last_name, first_name');
bdr_json_response(['status' => 'ok', 'residents' => $stmt->fetchAll()]);
