<?php
$basePath = $basePath ?? (function_exists('bdr_base_path') ? bdr_base_path() : '.');
$userName = htmlspecialchars($_SESSION['name'] ?? 'User', ENT_QUOTES, 'UTF-8');
$userRole = htmlspecialchars($_SESSION['role'] ?? 'resident', ENT_QUOTES, 'UTF-8');
?>
<nav class="navbar navbar-expand-lg w-100 me-3 py-3 px-2 border-bottom">
    <button class="navbar-toggler d-lg-none mx-2 border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="nav-heading">
        <h3 class="nav-header fw-bold my-0"><?= htmlspecialchars($pageTitle ?? 'Dashboard', ENT_QUOTES, 'UTF-8') ?></h3>
        <small class="text-secondary text-break">
            <?= htmlspecialchars($pageDescription ?? '', ENT_QUOTES, 'UTF-8') ?>
        </small>
    </div>
    <div class="d-flex align-items-center gap-3 position-relative ms-auto">
        <span class="badge bg-light text-dark border d-none d-md-inline-block px-3 py-2">
            <i class="bi bi-person-circle me-1"></i> <?= $userName ?> (<?= ucfirst($userRole) ?>)
        </span>
        <div class="dropdown">
            <button class="btn nav-link dropdown-toggle me-3 p-1 fs-5" type="button" id="dashboardUserMenu" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="dashboardUserMenu">
                <li class="px-3 py-1 d-md-none">
                    <strong class="d-block text-truncate"><?= $userName ?></strong>
                    <small class="text-muted"><?= ucfirst($userRole) ?></small>
                    <hr class="dropdown-divider my-2">
                </li>
                <li><a class="dropdown-item" href="<?= $basePath ?>/testing-frontend/user-page/account-settings.php"><i class="bi bi-gear me-2"></i>Account Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= $basePath ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>
</nav>
