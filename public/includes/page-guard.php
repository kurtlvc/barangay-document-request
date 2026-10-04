<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../config/database.php';

function bdr_require_page_role(array $allowedRoles): void
{
    global $pdo;
    bdr_start_session();
    $basePath = bdr_base_path();

    if (empty($_SESSION['user_id'])) {
        header('Location: ' . $basePath . '/login.php');
        exit;
    }

    $activeCheck = $pdo->prepare('SELECT role, is_active FROM users WHERE id = ?');
    $activeCheck->execute([$_SESSION['user_id']]);
    $accountState = $activeCheck->fetch();
    if (!$accountState || !(int) $accountState['is_active']) {
        $_SESSION = [];
        session_destroy();
        header('Location: ' . $basePath . '/login.php');
        exit;
    }
    $_SESSION['role'] = $accountState['role'];

    if (!in_array($_SESSION['role'] ?? '', $allowedRoles, true)) {
        header('Location: ' . $basePath . '/dashboard.php');
        exit;
    }
}
