<?php
require_once __DIR__ . '/functions.php';

function bdr_require_page_role(array $allowedRoles): void
{
    bdr_start_session();
    $basePath = bdr_base_path();

    if (empty($_SESSION['user_id'])) {
        header('Location: ' . $basePath . '/login.php');
        exit;
    }

    if (!in_array($_SESSION['role'] ?? '', $allowedRoles, true)) {
        header('Location: ' . $basePath . '/dashboard.php');
        exit;
    }
}
