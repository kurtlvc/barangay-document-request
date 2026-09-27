<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
bdr_start_session();

if (empty($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

$pageTitle = 'Administration Dashboard';
$pageDescription = 'System overview, user accounts, and master resident records.';
$basePath = '..';
$stats = ['total_users' => 0, 'total_residents' => 0, 'total_requests' => 0, 'total_documents' => 0];
$recentRequests = [];

try {
    $stmtStats = $pdo->query("
        SELECT (SELECT COUNT(*) FROM users) AS total_users,
            (SELECT COUNT(*) FROM residents) AS total_residents,
            (SELECT COUNT(*) FROM requests) AS total_requests,
            (SELECT COUNT(*) FROM document_types) AS total_documents
    ");
    $stats = $stmtStats->fetch() ?: $stats;

    $stmtRecent = $pdo->query("
        SELECT r.request_id, r.request_date, r.status, d.document_name,
            res.first_name, res.last_name
        FROM requests r
        JOIN document_types d ON d.document_id = r.document_id
        JOIN residents res ON res.resident_id = r.resident_id
        ORDER BY r.request_date DESC, r.request_id DESC LIMIT 6
    ");
    $recentRequests = $stmtRecent->fetchAll();
} catch (PDOException $e) {
    $stats = ['total_users' => 0, 'total_residents' => 0, 'total_requests' => 0, 'total_documents' => 0];
    $recentRequests = [];
}

include __DIR__ . '/../page-layout/header.php';
include __DIR__ . '/../includes/dashboard-admin.php';
$pageScripts = ['assets/js/dashboard.js'];
include __DIR__ . '/../page-layout/footer.php';
