<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

bdr_start_session();
$user = bdr_require_auth();
$method = $_SERVER['REQUEST_METHOD'];
$requestId = (int) ($_GET['request_id'] ?? 0);
$validStatuses = ['Pending', 'Processing', 'Ready for Release', 'Released', 'Rejected'];

function request_row(PDO $pdo, int $requestId, ?int $residentId = null): array|false
{
    $sql = 'SELECT q.request_id, q.request_date, q.status, q.remarks, q.resident_id, q.document_id,
                   d.document_number, d.document_name, d.processing_days,
                   CONCAT(r.first_name, " ", r.last_name) AS resident_name, r.address, r.contact_number,
                   p.name AS processed_by_name
            FROM requests q
            JOIN residents r ON r.resident_id = q.resident_id
            JOIN document_types d ON d.document_id = q.document_id
            LEFT JOIN users p ON p.id = q.processed_by
            WHERE q.request_id = ?';
    if ($residentId !== null) $sql .= ' AND q.resident_id = ' . (int) $residentId;
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$requestId]);
    return $stmt->fetch();
}

if ($method === 'GET') {
    if ($requestId > 0) {
        $request = request_row($pdo, $requestId, $user['role'] === 'resident' ? (int) $user['resident_id'] : null);
        if (!$request) bdr_json_response(['status' => 'error', 'message' => 'Request not found.'], 404);
        $history = $pdo->prepare('SELECT h.history_id, h.status, h.remarks, h.updated_at, u.name AS updated_by_name FROM request_history h JOIN users u ON u.id = h.updated_by WHERE h.request_id = ? ORDER BY h.updated_at ASC, h.history_id ASC');
        $history->execute([$requestId]);
        bdr_json_response(['status' => 'ok', 'request' => $request, 'history' => $history->fetchAll()]);
    }

    $sql = 'SELECT q.request_id, q.request_date, q.status, q.remarks, q.resident_id, d.document_name,
                   CONCAT(r.first_name, " ", r.last_name) AS resident_name
            FROM requests q JOIN residents r ON r.resident_id = q.resident_id
            JOIN document_types d ON d.document_id = q.document_id';
    $params = [];
    if ($user['role'] === 'resident') {
        if (!$user['resident_id']) bdr_json_response(['status' => 'ok', 'requests' => []]);
        $sql .= ' WHERE q.resident_id = ?';
        $params[] = $user['resident_id'];
    }
    $sql .= ' ORDER BY q.request_date DESC, q.request_id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    bdr_json_response(['status' => 'ok', 'requests' => $stmt->fetchAll()]);
}

bdr_require_write_csrf();
$data = bdr_request_data();

if ($method === 'POST' && ($data['action'] ?? '') !== 'update-status') {
    if ($user['role'] !== 'resident') bdr_json_response(['status' => 'error', 'message' => 'Only residents can submit requests.'], 403);
    if (!$user['resident_id'] || $user['resident_status'] !== 'verified') bdr_json_response(['status' => 'error', 'message' => 'Your resident profile must be verified before submitting a request.'], 403);
    $documentId = filter_var($data['document_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $remarks = trim($data['remarks'] ?? '');
    if ($documentId === false) bdr_json_response(['status' => 'error', 'message' => 'Choose a document type.'], 422);
    $document = $pdo->prepare('SELECT document_id FROM document_types WHERE document_id = ?');
    $document->execute([$documentId]);
    if (!$document->fetch()) bdr_json_response(['status' => 'error', 'message' => 'Document type not found.'], 404);
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT INTO requests (resident_id, document_id, status, remarks) VALUES (?, ?, "Pending", ?)');
        $stmt->execute([$user['resident_id'], $documentId, $remarks ?: null]);
        $newRequestId = (int) $pdo->lastInsertId();
        $history = $pdo->prepare('INSERT INTO request_history (request_id, status, remarks, updated_by) VALUES (?, "Pending", ?, ?)');
        $history->execute([$newRequestId, $remarks ?: 'Request submitted.', $user['id']]);
        $pdo->commit();
        bdr_json_response(['status' => 'ok', 'message' => 'Request submitted.', 'request_id' => $newRequestId], 201);
    } catch (Throwable $e) {
        $pdo->rollBack();
        bdr_json_response(['status' => 'error', 'message' => 'Unable to submit the request.'], 500);
    }
}

if (($method === 'PATCH' || $method === 'PUT') || ($method === 'POST' && ($data['action'] ?? '') === 'update-status')) {
    bdr_require_auth(['staff', 'admin']);
    $requestId = $requestId ?: (int) ($data['request_id'] ?? 0);
    $status = trim($data['status'] ?? '');
    $remarks = trim($data['remarks'] ?? '');
    if ($requestId <= 0 || !in_array($status, $validStatuses, true)) bdr_json_response(['status' => 'error', 'message' => 'A valid request ID and status are required.'], 422);
    if (!request_row($pdo, $requestId)) bdr_json_response(['status' => 'error', 'message' => 'Request not found.'], 404);
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('UPDATE requests SET status = ?, remarks = ?, processed_by = ? WHERE request_id = ?');
        $stmt->execute([$status, $remarks ?: null, $user['id'], $requestId]);
        $history = $pdo->prepare('INSERT INTO request_history (request_id, status, remarks, updated_by) VALUES (?, ?, ?, ?)');
        $history->execute([$requestId, $status, $remarks ?: null, $user['id']]);
        $pdo->commit();
        bdr_json_response(['status' => 'ok', 'message' => 'Request status updated.']);
    } catch (Throwable $e) {
        $pdo->rollBack();
        bdr_json_response(['status' => 'error', 'message' => 'Unable to update the request.'], 500);
    }
}

bdr_json_response(['status' => 'error', 'message' => 'Method not allowed.'], 405);
