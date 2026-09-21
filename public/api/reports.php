<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

bdr_start_session();
bdr_require_auth(['staff', 'admin']);
bdr_require_method(['GET']);

$byStatus = $pdo->query('SELECT status, COUNT(*) AS total FROM requests GROUP BY status ORDER BY status')->fetchAll();
$byDocument = $pdo->query('SELECT d.document_id, d.document_name, COUNT(q.request_id) AS total FROM document_types d LEFT JOIN requests q ON q.document_id = d.document_id GROUP BY d.document_id, d.document_name ORDER BY total DESC, d.document_name')->fetchAll();
$residentStatus = $pdo->query('SELECT status, COUNT(*) AS total FROM residents GROUP BY status ORDER BY status')->fetchAll();
$recent = $pdo->query('SELECT q.request_id, q.request_date, q.status, d.document_name, CONCAT(r.first_name, " ", r.last_name) AS resident_name FROM requests q JOIN residents r ON r.resident_id = q.resident_id JOIN document_types d ON d.document_id = q.document_id ORDER BY q.request_date DESC, q.request_id DESC LIMIT 10')->fetchAll();

bdr_json_response(['status' => 'ok', 'requests_by_status' => $byStatus, 'requests_by_document' => $byDocument, 'residents_by_status' => $residentStatus, 'recent_requests' => $recent]);
