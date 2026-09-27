<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
bdr_start_session();

if (empty($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
if (($_SESSION['role'] ?? '') !== 'resident') {
    header('Location: ../dashboard.php');
    exit;
}

$residentId = $_SESSION['resident_id'] ?? null;
$userName = $_SESSION['name'] ?? 'User';
$pageTitle = 'Resident Dashboard';
$pageDescription = "Welcome back, {$userName}! Track and submit document requests.";
$basePath = '..';
$stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'released' => 0];
$recentRequests = [];

try {
    if ($residentId) {
        $stmtStats = $pdo->prepare("
            SELECT COUNT(*) AS total,
                SUM(CASE WHEN LOWER(status) = 'pending' THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN LOWER(status) IN ('approved', 'ready for release') THEN 1 ELSE 0 END) AS approved,
                SUM(CASE WHEN LOWER(status) IN ('claimed', 'released') THEN 1 ELSE 0 END) AS released
            FROM requests WHERE resident_id = :resident_id
        ");
        $stmtStats->execute([':resident_id' => $residentId]);
        $stats = $stmtStats->fetch() ?: $stats;

        $stmtRecent = $pdo->prepare("
            SELECT r.request_id, r.request_date, r.status, r.remarks,
                d.document_name, d.document_number, d.processing_days
            FROM requests r
            JOIN document_types d ON d.document_id = r.document_id
            WHERE r.resident_id = :resident_id
            ORDER BY r.request_date DESC, r.request_id DESC LIMIT 5
        ");
        $stmtRecent->execute([':resident_id' => $residentId]);
        $recentRequests = $stmtRecent->fetchAll();
    }
} catch (PDOException $e) {
    $stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'released' => 0];
    $recentRequests = [];
}

include __DIR__ . '/../page-layout/header.php';
include __DIR__ . '/../includes/dashboard-resident.php';
$pageScripts = ['assets/js/dashboard.js'];
include __DIR__ . '/../page-layout/footer.php';
