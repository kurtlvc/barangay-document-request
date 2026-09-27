<?php
require_once __DIR__ . '/includes/functions.php';
bdr_start_session();

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$dashboardByRole = [
    'admin' => 'admin/dashboard.php',
    'staff' => 'staff/dashboard.php',
    'resident' => 'resident/dashboard.php',
];
$destination = $dashboardByRole[$_SESSION['role'] ?? ''] ?? null;

if ($destination === null) {
    http_response_code(403);
    exit('Your account does not have a dashboard role.');
}

header('Location: ' . $destination);
exit;
