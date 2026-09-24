<?php
$basePath = $basePath ?? (function_exists('bdr_base_path') ? bdr_base_path() : '.');
$resId = $_SESSION['resident_id'] ?? null;
$status = $_SESSION['resident_status'] ?? 'pending';
$isVerified = ($status === 'verified');
?>
<div class="dashboard-resident">
    <!-- Verification Status Banner -->
    <div class="alert <?= $isVerified ? 'alert-success bg-success-subtle text-success-emphasis border-success-subtle' : 'alert-warning bg-warning-subtle text-warning-emphasis border-warning-subtle' ?> d-flex align-items-center justify-content-between p-3 mb-4 rounded-3 shadow-sm">
        <div class="d-flex align-items-center">
            <i class="bi <?= $isVerified ? 'bi-patch-check-fill' : 'bi-exclamation-triangle-fill' ?> fs-3 me-3"></i>
            <div>
                <h6 class="fw-bold mb-1">
                    <?= $isVerified ? 'Verified Resident Profile' : 'Resident Profile Pending Verification' ?>
                    <?= $resId ? "(Resident #{$resId})" : '' ?>
                </h6>
                <small class="mb-0">
                    <?= $isVerified 
                        ? 'Your account is linked with your official barangay record. You can request documents online and track their status.' 
                        : 'Your account is currently pending verification by barangay staff. You may still submit document requests for review.' ?>
                </small>
            </div>
        </div>
        <a href="<?= $basePath ?>/testing-frontend/user-page/account-settings.php" class="btn btn-sm btn-outline-dark ms-3 text-nowrap">View Profile</a>
    </div>

    <!-- Overview Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 active-card h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-uppercase fw-semibold small mb-1">Total Requests</p>
                        <h2 class="fw-bold mb-0" data-stat="total"><?= (int)($stats['total'] ?? 0) ?></h2>
                    </div>
                    <i class="bi bi-folder2-open fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 pending-card h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-uppercase fw-semibold small mb-1">Pending Review</p>
                        <h2 class="fw-bold mb-0" data-stat="pending"><?= (int)($stats['pending'] ?? 0) ?></h2>
                    </div>
                    <i class="bi bi-clock-history fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 released-card h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-uppercase fw-semibold small mb-1">Ready for Release</p>
                        <h2 class="fw-bold mb-0" data-stat="approved"><?= (int)($stats['approved'] ?? 0) ?></h2>
                    </div>
                    <i class="bi bi-check2-circle fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-light border text-secondary h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-uppercase fw-semibold small mb-1">Claimed</p>
                        <h2 class="fw-bold mb-0" data-stat="released"><?= (int)($stats['released'] ?? 0) ?></h2>
                    </div>
                    <i class="bi bi-archive-fill fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Action / CTA -->
    <div class="card border-0 shadow-sm rounded-3 p-4 mb-4 bg-white">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h5 class="fw-bold mb-1">Need a Barangay Clearance or Certificate?</h5>
                <p class="text-muted mb-0">Submit your request online to avoid queuing at the barangay hall.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= $basePath ?>/testing-frontend/user-page/residents/new-request.php" class="btn btn-primary d-inline-flex align-items-center px-4">
                    <i class="bi bi-plus-circle me-2"></i> Request a Document
                </a>
                <a href="<?= $basePath ?>/testing-frontend/user-page/residents/my-requests.php" class="btn btn-outline-secondary d-inline-flex align-items-center">
                    <i class="bi bi-list-ul me-2"></i> My Requests
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Requests Section -->
    <div class="card border-0 shadow-sm rounded-3 p-4 bg-white">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold mb-0">Recent Document Requests</h5>
                <small class="text-muted" id="dashboard-last-updated"></small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1" id="dashboard-refresh-btn" title="Refresh Requests">
                    <i class="bi bi-arrow-clockwise"></i> <span>Refresh</span>
                </button>
                <a href="<?= $basePath ?>/testing-frontend/user-page/residents/my-requests.php" class="small text-decoration-none">View All</a>
            </div>
        </div>

        <div id="resident-recent-container">
            <?php if (!empty($recentRequests)): ?>
                <div class="list-group list-group-flush" id="resident-requests-list">
                    <?php foreach ($recentRequests as $req): 
                        $badgeClass = match(strtolower($req['status'])) {
                            'approved', 'ready for release' => 'bg-success-subtle text-success',
                            'pending' => 'bg-warning-subtle text-warning',
                            'claimed', 'released' => 'bg-info-subtle text-info',
                            'rejected' => 'bg-danger-subtle text-danger',
                            default => 'bg-secondary-subtle text-secondary'
                        };
                    ?>
                    <div class="list-group-item d-flex flex-column flex-sm-row justify-content-between align-items-sm-center px-0 py-3">
                        <div class="d-flex align-items-center mb-2 mb-sm-0">
                            <div class="rounded p-2 bg-light border me-3">
                                <i class="bi bi-file-earmark-text-fill fs-4 text-primary"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-semibold"><?= htmlspecialchars($req['document_name'], ENT_QUOTES, 'UTF-8') ?></h6>
                                <small class="text-muted">
                                    Requested on <?= date('M d, Y', strtotime($req['request_date'])) ?> &bull; Ref #REQ-<?= str_pad($req['request_id'], 4, '0', STR_PAD_LEFT) ?>
                                </small>
                            </div>
                        </div>
                        <div>
                            <span class="badge rounded-pill px-3 py-2 <?= $badgeClass ?>">
                                <?= htmlspecialchars($req['status'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5" id="resident-requests-empty">
                    <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                    <h6 class="text-muted">No document requests yet</h6>
                    <p class="small text-muted mb-3">Your submitted document requests and status updates will appear here.</p>
                    <a href="<?= $basePath ?>/testing-frontend/user-page/residents/new-request.php" class="btn btn-sm btn-outline-primary">
                        Submit your first request
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
