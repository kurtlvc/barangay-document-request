<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

bdr_start_session();
$method = $_SERVER['REQUEST_METHOD'];
$user = bdr_require_auth();
$documentId = (int) ($_GET['document_id'] ?? 0);

if ($method === 'GET') {
    if ($documentId > 0) {
        $stmt = $pdo->prepare('SELECT document_id, document_number, document_name, file_path, processing_days, fee, requirements, is_active FROM document_types WHERE document_id = ?');
        $stmt->execute([$documentId]);
        $document = $stmt->fetch();
        if (!$document) bdr_json_response(['status' => 'error', 'message' => 'Document type not found.'], 404);
        bdr_json_response(['status' => 'ok', 'document' => $document]);
    }
    $sql = 'SELECT document_id, document_number, document_name, file_path, processing_days, fee, requirements, is_active FROM document_types';
    if ($user['role'] !== 'admin') $sql .= ' WHERE is_active = 1';
    $stmt = $pdo->query($sql . ' ORDER BY document_name');
    bdr_json_response(['status' => 'ok', 'documents' => $stmt->fetchAll()]);
}

bdr_require_auth(['admin']);
bdr_require_write_csrf();
$data = bdr_request_data();

if (($method === 'PATCH' || $method === 'PUT' || $method === 'POST') && ($data['action'] ?? '') === 'set-active') {
    $documentId = $documentId ?: (int) ($data['document_id'] ?? 0);
    $active = filter_var($data['is_active'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($documentId <= 0 || $active === null) bdr_json_response(['status' => 'error', 'message' => 'A document ID and active state are required.'], 422);
    $stmt = $pdo->prepare('UPDATE document_types SET is_active = ? WHERE document_id = ?');
    $stmt->execute([$active ? 1 : 0, $documentId]);
    if (!$stmt->rowCount()) bdr_json_response(['status' => 'error', 'message' => 'Document not found or unchanged.'], 404);
    bdr_json_response(['status' => 'ok', 'message' => 'Document status updated.']);
}

if ($method === 'POST') {
    $number = trim($data['document_number'] ?? '');
    $name = trim($data['document_name'] ?? '');
    $path = trim($data['file_path'] ?? '');
    $days = filter_var($data['processing_days'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    $fee = filter_var($data['fee'] ?? 0, FILTER_VALIDATE_FLOAT);
    if ($number === '' || $name === '' || $days === false || $fee === false || $fee < 0) bdr_json_response(['status' => 'error', 'message' => 'Document number, name, a nonnegative fee, and processing days are required.'], 422);
    try {
        $stmt = $pdo->prepare('INSERT INTO document_types (document_number, document_name, file_path, processing_days, fee, requirements) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$number, $name, $path ?: null, $days, $fee, trim($data['requirements'] ?? '') ?: null]);
        bdr_json_response(['status' => 'ok', 'message' => 'Document type created.', 'document_id' => (int) $pdo->lastInsertId()], 201);
    } catch (PDOException $e) {
        bdr_json_response(['status' => 'error', 'message' => 'The document number is already in use.'], 409);
    }
}

if ($method === 'PUT' || $method === 'PATCH') {
    if ($documentId <= 0) bdr_json_response(['status' => 'error', 'message' => 'document_id is required.'], 422);
    $number = trim($data['document_number'] ?? '');
    $name = trim($data['document_name'] ?? '');
    $path = trim($data['file_path'] ?? '');
    $days = filter_var($data['processing_days'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    $fee = filter_var($data['fee'] ?? 0, FILTER_VALIDATE_FLOAT);
    if ($number === '' || $name === '' || $days === false || $fee === false || $fee < 0) bdr_json_response(['status' => 'error', 'message' => 'Document number, name, a nonnegative fee, and processing days are required.'], 422);
    try {
        $stmt = $pdo->prepare('UPDATE document_types SET document_number = ?, document_name = ?, file_path = ?, processing_days = ?, fee = ?, requirements = ? WHERE document_id = ?');
        $stmt->execute([$number, $name, $path ?: null, $days, $fee, trim($data['requirements'] ?? '') ?: null, $documentId]);
    } catch (PDOException $e) { bdr_json_response(['status' => 'error', 'message' => 'That document number is already in use.'], 409); }
    if (!$stmt->rowCount()) bdr_json_response(['status' => 'error', 'message' => 'Document type not found or unchanged.'], 404);
    bdr_json_response(['status' => 'ok', 'message' => 'Document type updated.']);
}

if ($method === 'DELETE') {
    if ($documentId <= 0) bdr_json_response(['status' => 'error', 'message' => 'document_id is required.'], 422);
    try {
        $stmt = $pdo->prepare('DELETE FROM document_types WHERE document_id = ?');
        $stmt->execute([$documentId]);
        if (!$stmt->rowCount()) bdr_json_response(['status' => 'error', 'message' => 'Document type not found.'], 404);
        bdr_json_response(['status' => 'ok', 'message' => 'Document type deleted.']);
    } catch (PDOException $e) {
        bdr_json_response(['status' => 'error', 'message' => 'A document type with requests cannot be deleted.'], 409);
    }
}

bdr_json_response(['status' => 'error', 'message' => 'Method not allowed.'], 405);
