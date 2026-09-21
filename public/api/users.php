<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

bdr_start_session();
$user = bdr_require_auth(['admin']);
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->query('SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC, id DESC');
    bdr_json_response(['status' => 'ok', 'users' => $stmt->fetchAll()]);
}

bdr_require_write_csrf();
$data = bdr_request_data();
$targetId = (int) ($data['user_id'] ?? $_GET['user_id'] ?? 0);
if ($method === 'PATCH' || $method === 'PUT' || ($method === 'POST' && ($data['action'] ?? '') === 'set-role')) {
    $role = $data['role'] ?? '';
    if ($targetId <= 0 || !in_array($role, ['resident', 'staff', 'admin'], true)) bdr_json_response(['status' => 'error', 'message' => 'A valid user ID and role are required.'], 422);
    if ($targetId === $user['id'] && $role !== 'admin') bdr_json_response(['status' => 'error', 'message' => 'You cannot remove your own admin role.'], 422);
    $stmt = $pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
    $stmt->execute([$role, $targetId]);
    if (!$stmt->rowCount()) bdr_json_response(['status' => 'error', 'message' => 'User not found or unchanged.'], 404);
    bdr_json_response(['status' => 'ok', 'message' => 'User role updated.']);
}

if ($method === 'DELETE') {
    if ($targetId <= 0) bdr_json_response(['status' => 'error', 'message' => 'user_id is required.'], 422);
    if ($targetId === $user['id']) bdr_json_response(['status' => 'error', 'message' => 'You cannot delete your own account.'], 422);
    try {
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?'); $stmt->execute([$targetId]);
        if (!$stmt->rowCount()) bdr_json_response(['status' => 'error', 'message' => 'User not found.'], 404);
        bdr_json_response(['status' => 'ok', 'message' => 'User deleted.']);
    } catch (PDOException $e) { bdr_json_response(['status' => 'error', 'message' => 'This user cannot be deleted while linked records exist.'], 409); }
}

bdr_json_response(['status' => 'error', 'message' => 'Method not allowed.'], 405);
