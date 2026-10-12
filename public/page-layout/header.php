<?php
if (!isset($pageTitle)) { $pageTitle = 'DokuBayan'; }
$basePath = $basePath ?? (function_exists('bdr_base_path') ? bdr_base_path() : '.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0f4530">
    <link rel="icon" href="<?= htmlspecialchars($basePath) ?>/assets/img/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars($basePath) ?>/assets/img/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= htmlspecialchars($basePath) ?>/assets/img/favicon-16x16.png">
    <link rel="apple-touch-icon" href="<?= htmlspecialchars($basePath) ?>/assets/img/apple-touch-icon.png">
    <title><?= htmlspecialchars($pageTitle) ?> — DokuBayan | Barangay Document Requests</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/bootstrap-icons/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/styles.css?v=2">
    <meta name="base-path" content="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>">
    <?php if (!empty($_SESSION['csrf_token'])): ?>
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>
    <script src="<?= htmlspecialchars($basePath) ?>/assets/js/theme-init.js"></script>
</head>
<body>
    <div class="container-fluid p-0">
        <div class="d-flex g-0 app-shell overflow-hidden">
            <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        
            <div class="app-content d-flex col-lg-9 flex-column flex-grow-1">
                <?php include __DIR__ . '/../includes/navbar.php'; ?>
