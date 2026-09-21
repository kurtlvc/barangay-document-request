<?php
$basePath = $basePath ?? (function_exists('bdr_base_path') ? bdr_base_path() : '.');
$role = $_SESSION['role'] ?? 'resident';
$username = $_SESSION['name'] ?? 'User';
$email = $_SESSION['email'] ?? '';
$status = $_SESSION['resident_status'] ?? 'pending';

$residentStatus = ($status === 'verified') ? 'Verified Resident' : 'Pending Verification';
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');

$navItems = [
    'resident' => [
        ['icon' => '<i class="bi bi-house-fill me-2"></i>', 'label' => 'Dashboard', 'href' => $basePath . '/dashboard.php'],
        ['icon' => '<i class="bi bi-file-earmark-plus-fill me-2"></i>', 'label' => 'Request Document', 'href' => $basePath . '/testing-frontend/user-page/residents/new-request.php'],
        ['icon' => '<i class="bi bi-clipboard2-check-fill me-2"></i>', 'label' => 'My Requests', 'href' => $basePath . '/testing-frontend/user-page/residents/my-requests.php'],
    ],
    'staff' => [
        ['icon' => '<i class="bi bi-house-fill me-2"></i>', 'label' => 'Dashboard', 'href' => $basePath . '/dashboard.php'],
        ['icon' => '<i class="bi bi-clipboard2-check-fill me-2"></i>', 'label' => 'Process Requests', 'href' => $basePath . '/testing-frontend/user-page/staffs/process-requests.php'],
        ['icon' => '<i class="bi bi-collection-fill me-2"></i>', 'label' => 'Release Management', 'href' => $basePath . '/testing-frontend/user-page/staffs/release-management.php'],
        ['icon' => '<i class="bi bi-bar-chart-fill me-2"></i>', 'label' => 'Reports', 'href' => $basePath . '/testing-frontend/user-page/staffs/reports.php'],
    ],
    'admin' => [
        ['icon' => '<i class="bi bi-house-fill me-2"></i>', 'label' => 'Dashboard', 'href' => $basePath . '/dashboard.php'],
        ['icon' => '<i class="bi bi-person-fill-gear me-2"></i>', 'label' => 'User Management', 'href' => $basePath . '/testing-frontend/user-page/admins/user-management.php'],
        ['icon' => '<i class="bi bi-person-vcard-fill me-2"></i>', 'label' => 'Resident Records', 'href' => $basePath . '/testing-frontend/user-page/admins/resident-records.php'],
        ['icon' => '<i class="bi bi-file-earmark-text-fill me-2"></i>', 'label' => 'Document Types', 'href' => $basePath . '/testing-frontend/user-page/admins/document-types.php'],
        ['icon' => '<i class="bi bi-file-earmark-bar-graph-fill me-2"></i>', 'label' => 'Reports', 'href' => $basePath . '/testing-frontend/user-page/admins/reports.php'],
    ],
];

$roleBadge = [
    'staff' => '<i class="bi bi-person-badge-fill me-1"></i> Barangay Staff',
    'admin' => '<i class="bi bi-person-vcard-fill me-1"></i> Barangay Administrator',
];
?>
<aside class="sidebar col-lg-3 d-none d-lg-flex vh-100 flex-column justify-content-between p-3 text-white overflow-hidden">  
    <div class="d-flex flex-column h-100">
        <div class="sidebar-header pb-3 mb-2 border-bottom border-light-subtle">
            <a class="navbar-brand fw-bold text-white d-flex align-items-center" href="<?= $basePath ?>/dashboard.php">
                <i class="bi bi-file-earmark-medical-fill fs-3 text-white me-2"></i>
                <span>Barangay Services</span>
            </a>
        </div>
        <div class="sidebar-btn d-flex flex-column py-3 flex-grow-1">
            <?php foreach ($navItems[$role] ?? [] as $item):
                $isActive = (basename($item['href']) === $currentScript);
            ?>
            <a class="nav-link text-start text-white mb-2 p-2 rounded-3 text-decoration-none <?= $isActive ? 'bg-white bg-opacity-25 fw-bold' : 'opacity-75 hover-opacity-100' ?>" href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>">
                <?= $item['icon'] ?>
                <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
            </a>
            <?php endforeach; ?>
        </div>
            
        <div class="profile-card d-flex align-items-center p-3 rounded-3 mt-auto" id="profileCard">
            <div class="rounded-circle bg-white text-dark d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                <i class="bi bi-person-fill fs-4"></i>
            </div>
            <div class="col ps-3 overflow-hidden">
                <p class="text-white text-truncate fw-semibold mb-0" id="username"><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></p>
                <p class="text-white-50 text-truncate small mb-1" id="email"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></p>
                <?php if ($role === 'resident'): ?>
                <span class="<?= $status === 'verified' ? 'verified-badge' : 'unverified-badge' ?> badge rounded-pill p-1 px-2" id="residentStatus">
                    <?= $residentStatus ?>
                </span>
                <?php else: ?>
                <span class="<?= $role ?>-badge badge rounded-pill p-1 px-2">
                    <?= $roleBadge[$role] ?? ucfirst($role) ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</aside>

<!-- Mobile Offcanvas -->
<div class="offcanvas offcanvas-start text-white" tabindex="-1" id="sidebar">
    <div class="offcanvas-header d-flex justify-content-between align-items-center border-bottom border-light-subtle">
        <a class="navbar-brand fw-bold text-white d-flex align-items-center" href="<?= $basePath ?>/dashboard.php">
            <i class="bi bi-file-earmark-medical-fill fs-4 me-2"></i>
            <span>Barangay Services</span>
        </a>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column justify-content-between p-3">
        <div class="offcanvas-nav d-flex flex-column">
            <?php foreach ($navItems[$role] ?? [] as $item):
                $isActive = (basename($item['href']) === $currentScript);
            ?>
            <a class="nav-link text-white mb-2 p-2 rounded-3 text-decoration-none <?= $isActive ? 'bg-white bg-opacity-25 fw-bold' : 'opacity-75' ?>" href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>">
                <?= $item['icon'] ?>
                <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
            </a>
            <?php endforeach; ?>
        </div>
        <div class="profile-card d-flex align-items-center p-3 rounded-3 mt-auto" id="profileCardMobile">
            <div class="rounded-circle bg-white text-dark d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                <i class="bi bi-person-fill fs-4"></i>
            </div>
            <div class="col ps-3 overflow-hidden">
                <p class="text-white text-truncate fw-semibold mb-0"><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></p>
                <p class="text-white-50 text-truncate small mb-1"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></p>
                <?php if ($role === 'resident'): ?>
                <span class="<?= $status === 'verified' ? 'verified-badge' : 'unverified-badge' ?> badge rounded-pill p-1 px-2">
                    <?= $residentStatus ?>
                </span>
                <?php else: ?>
                <span class="<?= $role ?>-badge badge rounded-pill p-1 px-2">
                    <?= $roleBadge[$role] ?? ucfirst($role) ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
