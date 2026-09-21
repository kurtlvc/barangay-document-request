<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
global $pdo;

bdr_start_session();

if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userRole = $_SESSION['role'] ?? 'resident';
$userName = $_SESSION['name'] ?? 'User';
$userEmail = $_SESSION['email'] ?? '';
$residentId = $_SESSION['resident_id'] ?? null;
$residentStatus = $_SESSION['resident_status'] ?? 'pending';

$basePath = '.';
$stats = [];
$recentRequests = [];

// Prepare data per role
try {
    if ($userRole === 'resident') {
        $pageTitle = "Resident Dashboard";
        $pageDescription = "Welcome back, {$userName}! Track and submit document requests.";
        $moduleFile = __DIR__ . '/testing-frontend/includes/dashboard-resident.php';

        if ($residentId) {
            // Aggregate request statistics for this resident
            $stmtStats = $pdo->prepare("
                SELECT 
                    COUNT(*) AS total,
                    SUM(CASE WHEN LOWER(status) = 'pending' THEN 1 ELSE 0 END) AS pending,
                    SUM(CASE WHEN LOWER(status) IN ('approved', 'ready for release') THEN 1 ELSE 0 END) AS approved,
                    SUM(CASE WHEN LOWER(status) IN ('claimed', 'released') THEN 1 ELSE 0 END) AS released
                FROM requests
                WHERE resident_id = :resident_id
            ");
            $stmtStats->execute([':resident_id' => $residentId]);
            $stats = $stmtStats->fetch() ?: [];

            // Recent requests for this resident
            $stmtRecent = $pdo->prepare("
                SELECT 
                    r.request_id,
                    r.request_date,
                    r.status,
                    r.remarks,
                    d.document_name,
                    d.document_number,
                    d.processing_days
                FROM requests r
                JOIN document_types d ON d.document_id = r.document_id
                WHERE r.resident_id = :resident_id
                ORDER BY r.request_date DESC
                LIMIT 5
            ");
            $stmtRecent->execute([':resident_id' => $residentId]);
            $recentRequests = $stmtRecent->fetchAll();
        }
    } elseif ($userRole === 'staff') {
        $pageTitle = "Staff Operations Dashboard";
        $pageDescription = "Manage and process incoming document requests.";
        $moduleFile = __DIR__ . '/testing-frontend/includes/dashboard-staff.php';

        // Overall request stats
        $stmtStats = $pdo->query("
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN LOWER(status) = 'pending' THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN LOWER(status) IN ('approved', 'ready for release') THEN 1 ELSE 0 END) AS approved,
                SUM(CASE WHEN LOWER(status) IN ('claimed', 'released') THEN 1 ELSE 0 END) AS released
            FROM requests
        ");
        $stats = $stmtStats->fetch() ?: [];

        // Unprocessed queue
        $stmtRecent = $pdo->query("
            SELECT 
                r.request_id,
                r.request_date,
                r.status,
                d.document_name,
                res.first_name,
                res.last_name,
                res.contact_number
            FROM requests r
            JOIN document_types d ON d.document_id = r.document_id
            JOIN residents res ON res.resident_id = r.resident_id
            ORDER BY r.request_date DESC
            LIMIT 6
        ");
        $recentRequests = $stmtRecent->fetchAll();
    } else {
        // Admin
        $pageTitle = "Administration Dashboard";
        $pageDescription = "System overview, user accounts, and master resident records.";
        $moduleFile = __DIR__ . '/testing-frontend/includes/dashboard-admin.php';

        // Admin counts
        $stmtStats = $pdo->query("
            SELECT 
                (SELECT COUNT(*) FROM users) AS total_users,
                (SELECT COUNT(*) FROM residents) AS total_residents,
                (SELECT COUNT(*) FROM requests) AS total_requests,
                (SELECT COUNT(*) FROM document_types) AS total_documents
        ");
        $stats = $stmtStats->fetch() ?: [];

        // Recent activity
        $stmtRecent = $pdo->query("
            SELECT 
                r.request_id,
                r.request_date,
                r.status,
                d.document_name,
                res.first_name,
                res.last_name
            FROM requests r
            JOIN document_types d ON d.document_id = r.document_id
            JOIN residents res ON res.resident_id = r.resident_id
            ORDER BY r.request_date DESC
            LIMIT 6
        ");
        $recentRequests = $stmtRecent->fetchAll();
    }
} catch (PDOException $e) {
    // Graceful fallback if query fails
    $stats = [];
    $recentRequests = [];
}

// Render Modular Layout
include __DIR__ . '/testing-frontend/page-layout/header.php';

if (isset($moduleFile) && file_exists($moduleFile)) {
    include $moduleFile;
} else {
    echo "<div class='alert alert-danger'>Dashboard module not found for role: " . htmlspecialchars($userRole, ENT_QUOTES, 'UTF-8') . "</div>";
}

include __DIR__ . '/testing-frontend/page-layout/footer.php';
