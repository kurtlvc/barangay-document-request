<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
bdr_start_session();

if (empty($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
if (($_SESSION['role'] ?? '') !== 'staff') {
    header('Location: ../dashboard.php');
    exit;
}

$pageTitle = 'Staff Operations Dashboard';
$pageDescription = 'Manage and process incoming document requests.';
$basePath = '..';
$stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'released' => 0];
$recentRequests = [];

try {
    $stmtStats = $pdo->query("
        SELECT COUNT(*) AS total,
            SUM(CASE WHEN LOWER(status) = 'pending' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN LOWER(status) IN ('approved', 'ready for release') THEN 1 ELSE 0 END) AS approved,
            SUM(CASE WHEN LOWER(status) IN ('claimed', 'released') THEN 1 ELSE 0 END) AS released
        FROM requests
    ");
    $stats = $stmtStats->fetch() ?: $stats;

    $stmtRecent = $pdo->query("
        SELECT r.request_id, r.request_date, r.status, d.document_name,
            res.first_name, res.last_name, res.contact_number
        FROM requests r
        JOIN document_types d ON d.document_id = r.document_id
        JOIN residents res ON res.resident_id = r.resident_id
        ORDER BY r.request_date DESC, r.request_id DESC LIMIT 6
    ");
    $recentRequests = $stmtRecent->fetchAll();
} catch (PDOException $e) {
    $stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'released' => 0];
    $recentRequests = [];
}

include __DIR__ . '/../page-layout/header.php';
include __DIR__ . '/../includes/dashboard-staff.php';
$pageScripts = ['assets/js/dashboard.js'];
include __DIR__ . '/../page-layout/footer.php';
