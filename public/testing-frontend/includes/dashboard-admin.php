<?php
$basePath = $basePath ?? (function_exists('bdr_base_path') ? bdr_base_path() : '.');
?>
<div class="dashboard-admin">
    <!-- Overview Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 active-card h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-uppercase fw-semibold small mb-1">User Accounts</p>
                        <h2 class="fw-bold mb-0"><?= (int)($stats['total_users'] ?? 0) ?></h2>
                    </div>
                    <i class="bi bi-people-fill fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 pending-card h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-uppercase fw-semibold small mb-1">Resident Records</p>
                        <h2 class="fw-bold mb-0"><?= (int)($stats['total_residents'] ?? 0) ?></h2>
                    </div>
                    <i class="bi bi-person-vcard-fill fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 released-card h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-uppercase fw-semibold small mb-1">Document Requests</p>
                        <h2 class="fw-bold mb-0"><?= (int)($stats['total_requests'] ?? 0) ?></h2>
                    </div>
                    <i class="bi bi-file-earmark-bar-graph-fill fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-light border text-secondary h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-uppercase fw-semibold small mb-1">Document Types</p>
                        <h2 class="fw-bold mb-0"><?= (int)($stats['total_documents'] ?? 0) ?></h2>
                    </div>
                    <i class="bi bi-file-earmark-text fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Administrative Quick Navigation -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 p-4 bg-white h-100">
                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-3 p-3 bg-primary bg-opacity-10 text-primary me-3">
                        <i class="bi bi-person-gear fs-3"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">User Management</h6>
                        <small class="text-muted">Manage staff & resident logins</small>
                    </div>
                </div>
                <a href="<?= $basePath ?>/testing-frontend/user-page/admins/user-management.php" class="btn btn-outline-primary btn-sm mt-auto">
                    Manage Accounts &rarr;
                </a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 p-4 bg-white h-100">
                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-3 p-3 bg-success bg-opacity-10 text-success me-3">
                        <i class="bi bi-person-vcard fs-3"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">Resident Masterlist</h6>
                        <small class="text-muted">View & verify barangay residents</small>
                    </div>
                </div>
                <a href="<?= $basePath ?>/testing-frontend/user-page/admins/resident-records.php" class="btn btn-outline-success btn-sm mt-auto">
                    View Registry &rarr;
                </a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 p-4 bg-white h-100">
                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-3 p-3 bg-warning bg-opacity-10 text-warning me-3">
                        <i class="bi bi-file-earmark-medical fs-3"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">Document Configuration</h6>
                        <small class="text-muted">Edit requirements & turnaround</small>
                    </div>
                </div>
                <a href="<?= $basePath ?>/testing-frontend/user-page/admins/document-types.php" class="btn btn-outline-warning btn-sm mt-auto">
                    Configure Types &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Requests Activity Table -->
    <div class="card border-0 shadow-sm rounded-3 p-4 bg-white">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold mb-0">Recent System Activity</h5>
                <small class="text-muted">Latest document requests submitted across the barangay</small>
            </div>
            <a href="<?= $basePath ?>/testing-frontend/user-page/admins/reports.php" class="btn btn-sm btn-outline-secondary">
                Generate Full Report
            </a>
        </div>

        <?php if (!empty($recentRequests)): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Ref #</th>
                            <th>Resident Name</th>
                            <th>Document Requested</th>
                            <th>Date Submitted</th>
                            <th>Status</th>
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
                            <td><?= htmlspecialchars($req['first_name'] . ' ' . $req['last_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($req['document_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><small><?= date('M d, Y', strtotime($req['request_date'])) ?></small></td>
                            <td>
                                <span class="badge rounded-pill px-3 py-2 <?= $badgeClass ?>">
                                    <?= htmlspecialchars($req['status'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                <h6 class="text-muted">No request activity recorded yet</h6>
            </div>
        <?php endif; ?>
    </div>
</div>
