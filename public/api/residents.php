<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

bdr_start_session();
$user = bdr_require_auth();
$method = $_SERVER['REQUEST_METHOD'];
$residentId = (int) ($_GET['resident_id'] ?? 0);

if ($method === 'GET') {
    if ($user['role'] === 'resident') {
        $residentId = (int) $user['resident_id'];
    } elseif ($user['role'] !== 'staff' && $user['role'] !== 'admin') {
        bdr_json_response(['status' => 'error', 'message' => 'You do not have permission for this action.'], 403);
    }
    $sql = 'SELECT r.resident_id, r.user_id, r.first_name, r.last_name, r.address, r.contact_number, r.status, r.created_at, u.email
            FROM residents r LEFT JOIN users u ON u.id = r.user_id';
    $params = [];
    if ($residentId > 0) { $sql .= ' WHERE r.resident_id = ?'; $params[] = $residentId; }
    $sql .= ' ORDER BY r.last_name, r.first_name';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $residents = $stmt->fetchAll();
    if ($residentId > 0) {
        if (!$residents) bdr_json_response(['status' => 'error', 'message' => 'Resident not found.'], 404);
        bdr_json_response(['status' => 'ok', 'resident' => $residents[0]]);
    }
    bdr_json_response(['status' => 'ok', 'residents' => $residents]);
}

bdr_require_auth(['admin']);
bdr_require_write_csrf();
$data = bdr_request_data();

if (($method === 'PATCH' || $method === 'PUT' || $method === 'POST') && ($data['action'] ?? '') === 'set-status') {
    $residentId = $residentId ?: (int) ($data['resident_id'] ?? 0);
    $status = $data['status'] ?? '';
    if ($residentId <= 0 || !in_array($status, ['pending', 'verified', 'rejected'], true)) bdr_json_response(['status' => 'error', 'message' => 'A valid resident ID and status are required.'], 422);
    $stmt = $pdo->prepare('UPDATE residents SET status = ? WHERE resident_id = ?');
    $stmt->execute([$status, $residentId]);
    if (!$stmt->rowCount()) bdr_json_response(['status' => 'error', 'message' => 'Resident not found or unchanged.'], 404);
    bdr_json_response(['status' => 'ok', 'message' => 'Resident status updated.']);
}

if ($method === 'POST') {
    $first = trim($data['first_name'] ?? ''); $last = trim($data['last_name'] ?? '');
    $address = trim($data['address'] ?? ''); $contact = trim($data['contact_number'] ?? '');
    if ($first === '' || $last === '' || $address === '' || $contact === '') bdr_json_response(['status' => 'error', 'message' => 'Name, address, and contact number are required.'], 422);
    $stmt = $pdo->prepare('INSERT INTO residents (first_name, last_name, address, contact_number, status) VALUES (?, ?, ?, ?, "verified")');
    $stmt->execute([$first, $last, $address, $contact]);
    bdr_json_response(['status' => 'ok', 'message' => 'Resident record created.', 'resident_id' => (int) $pdo->lastInsertId()], 201);
}

bdr_json_response(['status' => 'error', 'message' => 'Method not allowed.'], 405);
