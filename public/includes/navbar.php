<?php
$basePath = $basePath ?? (function_exists('bdr_base_path') ? bdr_base_path() : '.');
$userName = htmlspecialchars($_SESSION['name'] ?? ($name ?? 'User'), ENT_QUOTES, 'UTF-8');
$userRole = htmlspecialchars($_SESSION['role'] ?? ($role ?? 'resident'), ENT_QUOTES, 'UTF-8');
$notifications = $notifications ?? [];
$unreadCount = count(array_filter($notifications, fn($n) => !empty($n['unread'])));
?>
<nav class="navbar navbar-expand-lg w-100">
    <button class="navbar-toggler d-lg-none mx-2 border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="nav-heading ms-3">
        <h1 class="nav-header fw-bold my-0"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h1>
        <small class="text-secondary text-break d-none d-sm-block">
            <?= htmlspecialchars($pageDescription ?? '') ?>
        </small>
    </div>
    <div class="d-flex align-items-center gap-1 gap-sm-4 position-relative ms-auto">
        <button type="button" class="btn nav-item nav-link" id="themeToggle" aria-label="Toggle theme" title="Toggle theme"><i class="bi bi-moon-fill"></i></button>
        <div class="dropdown">
            <button class="btn nav-item nav-link" data-bs-toggle="dropdown" aria-label="Notifications">
                <i class="bi bi-bell-fill"></i>
                <?php if ($unreadCount > 0): ?><span class="dot"></span><?php endif; ?>
            </button>
            <div class="dropdown-menu dropdown-menu-end panel-menu notif-menu">
                <div class="panel-menu-head">Notifications</div>
                <?php if (empty($notifications)): ?>
                    <p class="panel-menu-empty">You have no notifications.</p>
                <?php else: foreach ($notifications as $n): ?>
                <div class="notif-item <?= !empty($n['unread']) ? 'unread' : '' ?>">
                    <div class="notif-title"><?= htmlspecialchars($n['title']) ?></div>
                    <div class="notif-text"><?= htmlspecialchars($n['text']) ?></div>
                    <div class="notif-time"><?= htmlspecialchars($n['time']) ?></div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
        <div class="dropdown">
            <button class="btn nav-link dropdown-toggle nav-user-toggle p-0" type="button" id="dashboardUserMenu" data-bs-toggle="dropdown" aria-expanded="false" aria-label="User menu">
                <i class="bi bi-person-fill"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dashboardUserMenu">
                <li class="px-3 py-1">
                    <strong class="d-block text-truncate"><?= $userName ?></strong>
                    <small class="text-muted"><?= ucfirst($userRole) ?></small>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= htmlspecialchars($basePath) ?>/account-settings.php"><i class="bi bi-gear me-2"></i>Account Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= htmlspecialchars($basePath) ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>
</nav>
