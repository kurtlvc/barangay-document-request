<?php
$basePath = $basePath ?? (function_exists('bdr_base_path') ? bdr_base_path() : '.');
$resId = $_SESSION['resident_id'] ?? null;
$status = $_SESSION['resident_status'] ?? 'pending';
$isVerified = ($status === 'verified');
?>
<main class="resident-content flex-grow-1 overflow-auto p-3 p-lg-4">
<div class="dashboard-resident">
    <!-- Verification Status Banner -->
    <div class="verify-banner d-flex align-items-center justify-content-between gap-3 mb-4 p-3 flex-wrap">
        <div class="d-flex align-items-center">
            <i class="bi <?= $isVerified ? 'bi-patch-check-fill' : 'bi-exclamation-triangle-fill' ?> fs-3 me-3 flex-shrink-0"></i>
            <div>
                <h6 class="fw-bold mb-1">
                    <?= $isVerified ? 'Verified Resident Profile' : 'Resident Profile Pending Verification' ?>
                    <?= $resId ? "(Resident #{$resId})" : '' ?>
                </h6>
                <small class="mb-0">
                    <?= $isVerified 
                        ? 'Your account is linked with your official barangay record. You can request documents online and track their status.' 
                        : 'Your account is currently pending verification by barangay admin. You can submit document requests after they verify your account.' ?>
                </small>
            </div>
        </div>
    </div>

    <div class="stats-panel mb-4">
        <div class="row g-0">
            <div class="col-12 col-lg-4">
                <div class="stat-card h-100 px-3 py-3 border-end">
                    <p class="stat-card-header mb-2">Total Requests</p>
                    <div class="d-flex align-items-end gap-2">
                        <p class="stat-card-value stat-card-value-large" data-stat="total"><?= (int)($stats['total'] ?? 0) ?></p>
                        <p class="stat-card-meta">requests</p>
                    </div>
                </div>
            </div>
            <div class="col-4 col-lg">
                <div class="stat-card h-100 px-3 py-3 border-end">
                    <p class="stat-card-header text-secondary mb-2">Pending Review</p>
                    <div class="d-flex align-items-end gap-2">
                        <p class="stat-card-value" data-stat="pending"><?= (int)($stats['pending'] ?? 0) ?></p>
                        <p class="stat-card-meta">requests</p>
                    </div>
                </div>
            </div>
            <div class="col-4 col-lg">
                <div class="stat-card h-100 px-3 py-3 border-end">
                    <p class="stat-card-header text-secondary mb-2">Ready for Release</p>
                    <div class="d-flex align-items-end gap-2">
                        <p class="stat-card-value" data-stat="approved"><?= (int)($stats['approved'] ?? 0) ?></p>
                        <p class="stat-card-meta">documents</p>
                    </div>
                </div>
            </div>
            <div class="col-4 col-lg">
                <div class="stat-card h-100 px-3 py-3">
                    <p class="stat-card-header text-secondary mb-2">Claimed</p>
                    <div class="d-flex align-items-end gap-2">
                        <p class="stat-card-value" data-stat="released"><?= (int)($stats['released'] ?? 0) ?></p>
                        <p class="stat-card-meta">documents</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Action / CTA -->
    <div class="quick-action-panel d-flex align-items-center justify-content-between gap-3 p-3 p-md-4 mb-4 flex-wrap">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h5 class="fw-bold mb-1">Need a Barangay Clearance or Certificate?</h5>
                <p class="text-white-50 mb-0">Submit your request online to avoid queuing at the barangay hall.</p>
            </div>
            <div class="d-flex flex-wrap gap-2 quick-action-buttons">
                <a href="<?= $basePath ?>/resident/new-request.php" class="btn btn-brand d-inline-flex align-items-center gap-2">
                    <i class="bi bi-plus-circle me-2"></i> Request a Document
                </a>
                <a href="<?= $basePath ?>/resident/my-requests.php" class="btn btn-outline-brand d-inline-flex align-items-center gap-2">
                    <i class="bi bi-list-ul me-2"></i> My Requests
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Requests Section -->
    <div class="panel-white p-3 p-md-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <div class="me-auto">
                <h5 class="fw-bold mb-0">Recent Document Requests</h5>
                <small class="text-muted" id="dashboard-last-updated"></small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-brand d-inline-flex align-items-center gap-1" id="dashboard-refresh-btn" title="Refresh Requests">
                    <i class="bi bi-arrow-clockwise"></i> <span>Refresh</span>
                </button>
                <a href="<?= $basePath ?>/resident/my-requests.php" class="view-link">View All</a>
            </div>
        </div>

        <div id="resident-recent-container">
            <?php if (!empty($recentRequests)): ?>
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-brand align-middle mb-0" id="resident-requests-list">
                        <thead>
                            <tr>
                                <th>Document Type</th>
                                <th>Reference #</th>
                                <th>Date Requested</th>
                                <th>Status</th>
                                <th class="text-end"><span class="visually-hidden">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                    <?php $requestCards = ''; foreach ($recentRequests as $req): 
                        $statusClass = match(strtolower($req['status'])) {
                            'approved', 'ready for release' => 'ready',
                            'pending' => 'pending',
                            'processing' => 'processing',
                            'claimed', 'released' => 'claimed',
                            'rejected', 'cancelled' => 'cancelled',
                            default => 'inactive'
                        };
                        $refNo = 'REQ-' . str_pad($req['request_id'], 4, '0', STR_PAD_LEFT);
                        $docName = htmlspecialchars($req['document_name'], ENT_QUOTES, 'UTF-8');
                        $reqDate = date('M d, Y', strtotime($req['request_date']));
                        $statusText = htmlspecialchars($req['status'], ENT_QUOTES, 'UTF-8');
                        $detailUrl = $basePath . '/resident/request-details.php?req=' . (int) $req['request_id'];
                        $requestCards .= '<div class="request-card"><div class="request-card-top"><span class="request-card-title">' . $docName . '</span><span class="status-badge status-' . $statusClass . '">' . $statusText . '</span></div><div class="request-card-meta"><span>' . $refNo . '</span><span>' . $reqDate . '</span></div><a href="' . $detailUrl . '" class="view-link request-card-link"><i class="bi bi-eye"></i> View details</a></div>';
                    ?>
                    <tr>
                        <td class="fw-semibold heading-green"><?= htmlspecialchars($req['document_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-muted-soft">REQ-<?= str_pad($req['request_id'], 4, '0', STR_PAD_LEFT) ?></td>
                        <td class="text-muted-soft"><?= date('M d, Y', strtotime($req['request_date'])) ?></td>
                        <td>
                            <span class="status-badge status-<?= $statusClass ?>">
                                <?= htmlspecialchars($req['status'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="<?= $basePath ?>/resident/request-details.php?req=<?= (int) $req['request_id'] ?>" class="view-link">
                                <i class="bi bi-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="request-card-list d-md-none"><?= $requestCards ?></div>
            <?php else: ?>
                <div class="text-center py-5" id="resident-requests-empty">
                    <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                    <h6 class="text-muted">No document requests yet</h6>
                    <p class="small text-muted mb-3">Your submitted document requests and status updates will appear here.</p>
                    <a href="<?= $basePath ?>/resident/new-request.php" class="btn btn-outline-brand">
                        Submit your first request
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</main>
