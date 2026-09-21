<?php
$basePath = $basePath ?? (function_exists('bdr_base_path') ? bdr_base_path() : '.');
?>
<div class="dashboard-staff">
    <!-- Overview Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 pending-card h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-uppercase fw-semibold small mb-1">Pending Review</p>
                        <h2 class="fw-bold mb-0"><?= (int)($stats['pending'] ?? 0) ?></h2>
                    </div>
                    <i class="bi bi-clock-history fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 active-card h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-uppercase fw-semibold small mb-1">Approved</p>
                        <h2 class="fw-bold mb-0"><?= (int)($stats['approved'] ?? 0) ?></h2>
                    </div>
                    <i class="bi bi-check-circle-fill fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 released-card h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-uppercase fw-semibold small mb-1">Released / Claimed</p>
                        <h2 class="fw-bold mb-0"><?= (int)($stats['released'] ?? 0) ?></h2>
                    </div>
                    <i class="bi bi-box-seam-fill fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-light border text-secondary h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-uppercase fw-semibold small mb-1">Total System Requests</p>
                        <h2 class="fw-bold mb-0"><?= (int)($stats['total'] ?? 0) ?></h2>
                    </div>
                    <i class="bi bi-files fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Operations Bar -->
    <div class="card border-0 shadow-sm rounded-3 p-3 mb-4 bg-white">
        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
            <h6 class="fw-bold mb-0 text-muted"><i class="bi bi-lightning-charge me-1"></i> Quick Staff Operations:</h6>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= $basePath ?>/testing-frontend/user-page/staffs/process-requests.php" class="btn btn-sm btn-primary">
                    <i class="bi bi-clipboard2-check-fill me-1"></i> Process Queue
                </a>
                <a href="<?= $basePath ?>/testing-frontend/user-page/staffs/release-management.php" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-collection-fill me-1"></i> Release Desk
                </a>
                <a href="<?= $basePath ?>/testing-frontend/user-page/staffs/reports.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-bar-chart-fill me-1"></i> Activity Reports
                </a>
            </div>
        </div>
    </div>

    <!-- Unprocessed Requests Table / Queue -->
    <div class="card border-0 shadow-sm rounded-3 p-4 bg-white">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold mb-0">Incoming Document Request Queue</h5>
                <small class="text-muted">Requests submitted by residents requiring review and approval</small>
            </div>
            <a href="<?= $basePath ?>/testing-frontend/user-page/staffs/process-requests.php" class="btn btn-sm btn-outline-primary">
                Open Full Queue
            </a>
        </div>

        <?php if (!empty($recentRequests)): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Ref #</th>
                            <th>Resident</th>
                            <th>Document Requested</th>
                            <th>Contact</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentRequests as $req): 
                            $badgeClass = match(strtolower($req['status'])) {
                                'approved', 'ready for release' => 'bg-success-subtle text-success',
                                'pending' => 'bg-warning-subtle text-warning',
                                'claimed', 'released' => 'bg-info-subtle text-info',
                                'rejected' => 'bg-danger-subtle text-danger',
                                default => 'bg-secondary-subtle text-secondary'
                            };
                        ?>
                        <tr>
                            <td class="fw-semibold text-muted">REQ-<?= str_pad($req['request_id'], 4, '0', STR_PAD_LEFT) ?></td>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($req['first_name'] . ' ' . $req['last_name'], ENT_QUOTES, 'UTF-8') ?></div>
                            </td>
                            <td><?= htmlspecialchars($req['document_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><small><?= htmlspecialchars($req['contact_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></small></td>
                            <td><small><?= date('M d, Y', strtotime($req['request_date'])) ?></small></td>
                            <td>
                                <span class="badge rounded-pill px-3 py-2 <?= $badgeClass ?>">
                                    <?= htmlspecialchars($req['status'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="<?= $basePath ?>/testing-frontend/user-page/staffs/process-requests.php" class="btn btn-sm btn-outline-dark">
                                    Process
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-check2-all fs-1 text-success d-block mb-3"></i>
                <h6 class="text-muted">Queue is currently clear!</h6>
                <p class="small text-muted mb-0">There are no pending requests waiting for review.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
