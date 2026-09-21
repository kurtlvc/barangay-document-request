<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

bdr_start_session();
$user = bdr_require_auth();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->prepare('SELECT u.id, u.name, u.email, u.role, u.created_at, r.resident_id, r.first_name, r.last_name, r.address, r.contact_number, r.status AS resident_status FROM users u LEFT JOIN residents r ON r.user_id = u.id WHERE u.id = ?');
    $stmt->execute([$user['id']]);
    bdr_json_response(['status' => 'ok', 'profile' => $stmt->fetch()]);
}

bdr_require_method(['PATCH', 'PUT', 'POST']);
bdr_require_write_csrf();
$data = bdr_request_data();
$name = trim($data['name'] ?? $user['name']);
$email = trim(strtolower($data['email'] ?? $user['email']));
if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) bdr_json_response(['status' => 'error', 'message' => 'A name and valid email address are required.'], 422);

$currentPassword = $data['current_password'] ?? '';
$newPassword = $data['new_password'] ?? '';
if ($newPassword !== '') {
    if (strlen($newPassword) < 8) bdr_json_response(['status' => 'error', 'message' => 'New password must be at least 8 characters.'], 422);
    $password = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?'); $password->execute([$user['id']]);
    if (!password_verify($currentPassword, $password->fetchColumn() ?: '')) bdr_json_response(['status' => 'error', 'message' => 'Current password is incorrect.'], 422);
}

$residentFields = null;
if ($user['role'] === 'resident' && !empty($user['resident_id'])) {
    $first = trim($data['first_name'] ?? '');
    $last = trim($data['last_name'] ?? '');
    $address = trim($data['address'] ?? '');
    $contact = trim($data['contact_number'] ?? '');
    if ($first !== '' || $last !== '' || $address !== '' || $contact !== '') {
        if ($first === '' || $last === '' || $address === '' || $contact === '') bdr_json_response(['status' => 'error', 'message' => 'Complete all resident profile fields.'], 422);
        $residentFields = [$first, $last, $address, $contact, $user['resident_id']];
    }
}

try {
    $pdo->beginTransaction();
    $sql = 'UPDATE users SET name = ?, email = ?' . ($newPassword !== '' ? ', password_hash = ?' : '') . ' WHERE id = ?';
    $params = [$name, $email]; if ($newPassword !== '') $params[] = password_hash($newPassword, PASSWORD_DEFAULT); $params[] = $user['id'];
    $pdo->prepare($sql)->execute($params);
    if ($residentFields !== null) {
        $pdo->prepare('UPDATE residents SET first_name = ?, last_name = ?, address = ?, contact_number = ? WHERE resident_id = ?')->execute($residentFields);
    }
    $pdo->commit();
    $_SESSION['name'] = $name; $_SESSION['email'] = $email;
    bdr_json_response(['status' => 'ok', 'message' => 'Profile updated.']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    bdr_json_response(['status' => 'error', 'message' => 'Unable to update profile. The email address may already be in use.'], 409);
}
