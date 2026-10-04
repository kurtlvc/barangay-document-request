<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
bdr_start_session();
$user = bdr_require_auth(['admin']);
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->query("SELECT id, name, email, role, contact_number, is_active, created_at FROM users WHERE role IN ('staff', 'admin') ORDER BY created_at DESC, id DESC");
    bdr_json_response(['status' => 'ok', 'users' => $stmt->fetchAll()]);
}

bdr_require_write_csrf();
$data = bdr_request_data();
$targetId = (int) ($data['user_id'] ?? $_GET['user_id'] ?? 0);

if ($method === 'POST') {
    $name = trim($data['name'] ?? '');
    $email = strtolower(trim($data['email'] ?? ''));
    $role = $data['role'] ?? '';
    $contact = trim($data['contact_number'] ?? '');
    $password = $data['password'] ?? '';
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($role, ['staff', 'admin'], true) || strlen($password) < 8) {
        bdr_json_response(['status' => 'error', 'message' => 'Provide a valid name, email, staff/admin role, and password of at least 8 characters.'], 422);
    }
    try {
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, contact_number) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role, $contact ?: null]);
        bdr_json_response(['status' => 'ok', 'message' => 'Account created.', 'user_id' => (int) $pdo->lastInsertId()], 201);
    } catch (PDOException $e) {
        bdr_json_response(['status' => 'error', 'message' => 'That email address is already in use.'], 409);
    }
}

if ($method === 'PATCH' || $method === 'PUT') {
    $targetId = $targetId ?: (int) ($data['id'] ?? 0);
    if ($targetId <= 0) bdr_json_response(['status' => 'error', 'message' => 'user_id is required.'], 422);
    if (($data['action'] ?? '') === 'set-active') {
        $active = filter_var($data['is_active'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($active === null) bdr_json_response(['status' => 'error', 'message' => 'is_active must be true or false.'], 422);
        if ($targetId === $user['id'] && !$active) bdr_json_response(['status' => 'error', 'message' => 'You cannot deactivate your own account.'], 422);
        $stmt = $pdo->prepare('UPDATE users SET is_active = ? WHERE id = ? AND role IN (\'staff\', \'admin\')');
        $stmt->execute([$active ? 1 : 0, $targetId]);
    } else {
        $name = trim($data['name'] ?? '');
        $email = strtolower(trim($data['email'] ?? ''));
        $role = $data['role'] ?? '';
        $contact = trim($data['contact_number'] ?? '');
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($role, ['staff', 'admin'], true)) bdr_json_response(['status' => 'error', 'message' => 'Name, valid email, and staff/admin role are required.'], 422);
        if ($targetId === $user['id'] && $role !== 'admin') bdr_json_response(['status' => 'error', 'message' => 'You cannot remove your own admin role.'], 422);
        try {
            $sql = 'UPDATE users SET name = ?, email = ?, role = ?, contact_number = ?';
            $params = [$name, $email, $role, $contact ?: null];
            if (($data['password'] ?? '') !== '') {
                if (strlen($data['password']) < 8) bdr_json_response(['status' => 'error', 'message' => 'Password must be at least 8 characters.'], 422);
                $sql .= ', password_hash = ?'; $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            $sql .= ' WHERE id = ? AND role IN (\'staff\', \'admin\')'; $params[] = $targetId;
            $stmt = $pdo->prepare($sql); $stmt->execute($params);
        } catch (PDOException $e) { bdr_json_response(['status' => 'error', 'message' => 'That email address is already in use.'], 409); }
    }
    if (!$stmt->rowCount()) bdr_json_response(['status' => 'error', 'message' => 'Account not found or unchanged.'], 404);
    bdr_json_response(['status' => 'ok', 'message' => 'Account updated.']);
}

if ($method === 'DELETE') {
    if ($targetId <= 0) bdr_json_response(['status' => 'error', 'message' => 'user_id is required.'], 422);
    if ($targetId === $user['id']) bdr_json_response(['status' => 'error', 'message' => 'You cannot delete your own account.'], 422);
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role IN ('staff', 'admin')"); $stmt->execute([$targetId]);
        if (!$stmt->rowCount()) bdr_json_response(['status' => 'error', 'message' => 'Account not found.'], 404);
        bdr_json_response(['status' => 'ok', 'message' => 'Account deleted.']);
    } catch (PDOException $e) { bdr_json_response(['status' => 'error', 'message' => 'This account is linked to request history and cannot be deleted.'], 409); }
}
bdr_json_response(['status' => 'error', 'message' => 'Method not allowed.'], 405);
