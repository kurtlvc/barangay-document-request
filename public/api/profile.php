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
$resetVerification = false;
if ($user['role'] === 'resident' && !empty($user['resident_id'])) {
    $first = trim($data['first_name'] ?? '');
    $last = trim($data['last_name'] ?? '');
    $address = trim($data['address'] ?? '');
    $contact = trim($data['contact_number'] ?? '');
    if ($first !== '' || $last !== '' || $address !== '' || $contact !== '') {
        if ($first === '' || $last === '' || $address === '' || $contact === '') bdr_json_response(['status' => 'error', 'message' => 'Complete all resident profile fields.'], 422);
        // Compare identity fields against the stored record: a change to a
        // verified identity must send the account back to pending.
        $current = $pdo->prepare('SELECT first_name, last_name, address, status FROM residents WHERE resident_id = ?');
        $current->execute([$user['resident_id']]);
        $stored = $current->fetch() ?: null;
        if ($stored && ($stored['status'] ?? '') === 'verified'
            && (trim($stored['first_name'] ?? '') !== $first
                || trim($stored['last_name'] ?? '') !== $last
                || trim($stored['address'] ?? '') !== $address)) {
            $resetVerification = true;
        }
        $residentFields = [$first, $last, $address, $contact, $user['resident_id']];
    }
}

try {
    $pdo->beginTransaction();
    $sql = 'UPDATE users SET name = ?, email = ?' . ($newPassword !== '' ? ', password_hash = ?' : '') . ' WHERE id = ?';
    $params = [$name, $email]; if ($newPassword !== '') $params[] = password_hash($newPassword, PASSWORD_DEFAULT); $params[] = $user['id'];
    $pdo->prepare($sql)->execute($params);
    if ($residentFields !== null) {
        if ($resetVerification) {
            $pdo->prepare("UPDATE residents SET first_name = ?, last_name = ?, address = ?, contact_number = ?, status = 'pending' WHERE resident_id = ?")->execute($residentFields);
        } else {
            $pdo->prepare('UPDATE residents SET first_name = ?, last_name = ?, address = ?, contact_number = ? WHERE resident_id = ?')->execute($residentFields);
        }
    }
    $pdo->commit();
    $_SESSION['name'] = $name; $_SESSION['email'] = $email;
    if ($resetVerification) $_SESSION['resident_status'] = 'pending';
    bdr_json_response([
        'status' => 'ok',
        'message' => $resetVerification
            ? 'Profile updated. Your name or address changed, so your account is pending re-verification by the barangay.'
            : 'Profile updated.',
        'reverification' => $resetVerification,
        'resident_status' => $resetVerification ? 'pending' : ($user['resident_status'] ?? null),
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    bdr_json_response(['status' => 'error', 'message' => 'Unable to update profile. The email address may already be in use.'], 409);
}
