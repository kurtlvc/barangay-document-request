<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

bdr_start_session();
$user = bdr_require_auth();
bdr_require_method(['GET']);

$role = $user['role'];
$residentId = $user['resident_id'] ?? null;
$stats = [];
$recentRequests = [];

try {
    if ($role === 'resident') {
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
                ORDER BY r.request_date DESC, r.request_id DESC
                LIMIT 5
            ");
            $stmtRecent->execute([':resident_id' => $residentId]);
            $recentRequests = $stmtRecent->fetchAll();
        } else {
            $stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'released' => 0];
            $recentRequests = [];
        }
    } elseif ($role === 'staff') {
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

        // Unprocessed queue / recent requests
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
            ORDER BY r.request_date DESC, r.request_id DESC
            LIMIT 6
        ");
        $recentRequests = $stmtRecent->fetchAll();
    } elseif ($role === 'admin') {
        // Admin overview counts
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
            ORDER BY r.request_date DESC, r.request_id DESC
            LIMIT 6
        ");
        $recentRequests = $stmtRecent->fetchAll();
    } else {
        bdr_json_response(['status' => 'error', 'message' => 'Unauthorized role.'], 403);
    }
} catch (PDOException $e) {
    bdr_json_response(['status' => 'error', 'message' => 'Database query failed.'], 500);
}

// Ensure numeric counts are cast to integer
$cleanStats = [];
foreach ($stats as $key => $val) {
    $cleanStats[$key] = (int) $val;
}

bdr_json_response([
    'status' => 'ok',
    'role' => $role,
    'user' => [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'resident_id' => $user['resident_id'],
        'resident_status' => $user['resident_status'],
    ],
    'stats' => $cleanStats,
    'recent_requests' => $recentRequests,
    'timestamp' => date('c')
]);
