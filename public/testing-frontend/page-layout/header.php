<?php
if (!isset($pageTitle)) { $pageTitle = 'Barangay Document Requests'; }
$basePath = $basePath ?? (function_exists('bdr_base_path') ? bdr_base_path() : '.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — Barangay Document Requests</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/styles.css">
    <meta name="base-path" content="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>">
    <?php if (!empty($_SESSION['csrf_token'])): ?>
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>
</head>
<body>
    <div class="container-fluid p-0">
        <div class="d-flex g-0 vh-100 overflow-hidden">
            <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        
            <div class="d-flex col-lg-9 flex-column flex-grow-1">
                <?php include __DIR__ . '/../includes/navbar.php'; ?>
                
                <main class="flex-grow-1 overflow-auto p-3">