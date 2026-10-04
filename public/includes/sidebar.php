<?php
$basePath = $basePath ?? (function_exists('bdr_base_path') ? bdr_base_path() : '.');
$role = $_SESSION['role'] ?? ($role ?? 'resident');
$name = $_SESSION['name'] ?? ($name ?? 'User');
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');

$navItems = [
    'resident' => [
        ['icon' => '<i class="bi bi-house-fill"></i>', 'label' => 'Dashboard', 'href' => $basePath . '/resident/dashboard.php'],
        ['icon' => '<i class="bi bi-file-earmark-plus-fill"></i>', 'label' => 'Request Document', 'href' => $basePath . '/resident/new-request.php'],
        ['icon' => '<i class="bi bi-clipboard2-check-fill"></i>',  'label' => 'My Requests', 'href' => $basePath . '/resident/my-requests.php'],
    ],
    'staff' => [
        ['icon' => '<i class="bi bi-house-fill"></i>','label' => 'Dashboard', 'href' => $basePath . '/staff/dashboard.php'],
        ['icon' => '<i class="bi bi-clipboard2-check-fill"></i>','label' => 'Process Requests', 'href' => $basePath . '/staff/process-requests.php'],
        ['icon' => '<i class="bi bi-collection-fill"></i>','label' => 'Release Management', 'href' => $basePath . '/staff/release-management.php'],
        ['icon' => '<i class="bi bi-bar-chart-fill"></i>','label' => 'Reports', 'href' => $basePath . '/staff/reports.php'],
    ],
    'admin' => [
        ['icon' => '<i class="bi bi-house-fill"></i>','label' => 'Dashboard', 'href' => $basePath . '/admin/dashboard.php'],
        ['icon' => '<i class="bi bi-person-fill-gear"></i>','label' => 'User Management', 'href' => $basePath . '/admin/user-management.php'],
        ['icon' => '<i class="bi bi-person-vcard-fill"></i>','label' => 'Resident Records', 'href' => $basePath . '/admin/resident-records.php'],
        ['icon' => '<i class="bi bi-file-earmark-text-fill"></i>','label' => 'Document Types', 'href' => $basePath . '/admin/document-types.php'],
        ['icon' => '<i class="bi bi-file-earmark-bar-graph-fill"></i>','label' => 'Reports', 'href' => $basePath . '/admin/reports.php'],
    ],
];

$roleBadge = [
    'resident' => 'Resident',
    'staff' => 'Barangay Staff',
    'admin' => 'Barangay Administrator',
];
?>
<aside class="sidebar col-lg-3 d-none d-lg-flex vh-100 overflow-hidden">  
    <div class="sidebar-content p-3 d-flex flex-column h-100">
        <div class="sidebar-header">
            <a class="navbar-brand fw-semibold m-3" href="<?= htmlspecialchars($basePath) ?>/"><img src="<?= htmlspecialchars($basePath) ?>/logo.png" alt="DokuBayan"> DokuBayan </a>
        </div>
        <nav class="sidebar-btn d-flex flex-column gap-1 px-3 pt-4" aria-label="Main navigation">
            <?php foreach ($navItems[$role] ?? [] as $item):
                $isActive = (basename($item['href']) === $currentScript);
            ?>
            <a class="nav-link <?= $isActive ? 'active' : '' ?> text-light" href="<?= htmlspecialchars($item['href']) ?>">
                <?= $item['icon'] ?>
                <span><?= htmlspecialchars($item['label']) ?></span>
            </a>
            <?php endforeach; ?>
        </nav>
            
        <div class="profile-card d-flex align-items-center p-3 rounded mt-auto flex-shrink-0">
            <?php if (!empty($profileImg)): ?>
                <img src="<?= htmlspecialchars($profileImg) ?>" alt="<?= htmlspecialchars($name) ?>" class="profile-avatar">
            <?php else: ?>
                <span class="profile-avatar" aria-hidden="true"><i class="bi bi-person-fill"></i></span>
            <?php endif; ?>
            <div class="profile-card-info">
                <p class="profile-card-name"><?= htmlspecialchars($name) ?></p>
                <p class="profile-card-role"><?= htmlspecialchars($roleBadge[$role] ?? ucfirst($role)) ?></p>
            </div>
        </div>
    </div>
</aside>

<!-- Mobile -->
<div class="offcanvas offcanvas-start d-lg-none d-flex flex-column vh-100" tabindex="-1" id="sidebar">
    <div class="offcanvas-header d-flex justify-content-between">
        <a class="navbar-brand fw-semibold m-3" href="<?= htmlspecialchars($basePath) ?>/"><img src="<?= htmlspecialchars($basePath) ?>/logo.png" alt="DokuBayan"> DokuBayan </a>
        <button type="button" class="btn text-light" data-bs-dismiss="offcanvas" aria-label="Close"><i class="bi-x-lg"></i></button>
    </div>
    <nav class="offcanvas-nav d-flex flex-column gap-1 mx-4 pt-3" aria-label="Main navigation">
        <?php foreach ($navItems[$role] ?? [] as $item):
            $isActive = (basename($item['href']) === $currentScript);
        ?>
        <a class="nav-link <?= $isActive ? 'active' : '' ?> text-light" href="<?= htmlspecialchars($item['href']) ?>">
            <?= $item['icon'] ?>
            <span><?= htmlspecialchars($item['label']) ?></span>
        </a>
        <?php endforeach; ?>
    </nav>
    <div class="profile-card d-flex align-items-center p-3 rounded mx-3 mt-auto flex-shrink-0">
        <?php if (!empty($profileImg)): ?>
            <img src="<?= htmlspecialchars($profileImg) ?>" alt="<?= htmlspecialchars($name) ?>" class="profile-avatar">
        <?php else: ?>
            <span class="profile-avatar" aria-hidden="true"><i class="bi bi-person-fill"></i></span>
        <?php endif; ?>
        <div class="profile-card-info">
            <p class="profile-card-name"><?= htmlspecialchars($name) ?></p>
            <p class="profile-card-role"><?= htmlspecialchars($roleBadge[$role] ?? ucfirst($role)) ?></p>
        </div>
    </div>
</div>
