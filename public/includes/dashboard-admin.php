<?php
$basePath = $basePath ?? (function_exists('bdr_base_path') ? bdr_base_path() : '.');
?>
<main class="flex-grow-1 overflow-auto p-3 p-lg-4">
<div class="dashboard-admin">
    <div class="stats-panel mb-4">
        <div class="row g-0">
            <div class="col-12 col-lg-4">
                <div class="stat-card h-100 px-3 py-3 border-end">
                    <p class="stat-card-header mb-2">User Accounts</p>
                    <div class="d-flex align-items-end gap-2">
                        <p class="stat-card-value stat-card-value-large" data-stat="total_users"><?= (int)($stats['total_users'] ?? 0) ?></p>
                        <p class="stat-card-meta">accounts</p>
                    </div>
                </div>
            </div>
            <div class="col-4 col-lg">
                <div class="stat-card h-100 px-3 py-3 border-end">
                    <p class="stat-card-header text-secondary mb-2">Resident Records</p>
                    <div class="d-flex align-items-end gap-2">
                        <p class="stat-card-value" data-stat="total_residents"><?= (int)($stats['total_residents'] ?? 0) ?></p>
                        <p class="stat-card-meta">residents</p>
                    </div>
                </div>
            </div>
            <div class="col-4 col-lg">
                <div class="stat-card h-100 px-3 py-3 border-end">
                    <p class="stat-card-header text-secondary mb-2">Document Requests</p>
                    <div class="d-flex align-items-end gap-2">
                        <p class="stat-card-value" data-stat="total_requests"><?= (int)($stats['total_requests'] ?? 0) ?></p>
                        <p class="stat-card-meta">requests</p>
                    </div>
                </div>
            </div>
            <div class="col-4 col-lg">
                <div class="stat-card h-100 px-3 py-3">
                    <p class="stat-card-header text-secondary mb-2">Document Types</p>
                    <div class="d-flex align-items-end gap-2">
                        <p class="stat-card-value" data-stat="total_documents"><?= (int)($stats['total_documents'] ?? 0) ?></p>
                        <p class="stat-card-meta">types</p>
                    </div>
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
                <a href="<?= $basePath ?>/admin/user-management.php" class="btn btn-outline-primary btn-sm mt-auto">
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
                <a href="<?= $basePath ?>/admin/resident-records.php" class="btn btn-outline-success btn-sm mt-auto">
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
                <a href="<?= $basePath ?>/admin/document-types.php" class="btn btn-outline-warning btn-sm mt-auto">
                    Configure Types &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Requests Activity Table -->
    <div class="panel-white p-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h5 class="fw-bold mb-0">Recent System Activity</h5>
                <small class="text-muted" id="dashboard-last-updated">Latest document requests submitted across the barangay</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-brand d-inline-flex align-items-center gap-1" id="dashboard-refresh-btn" title="Refresh Activity">
                    <i class="bi bi-arrow-clockwise"></i> <span>Refresh</span>
                </button>
            </div>
        </div>

        <div id="admin-activity-container">
            <?php if (!empty($recentRequests)): ?>
                <div class="table-responsive">
                    <table class="table table-brand align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Ref #</th>
                                <th>Resident Name</th>
                                <th>Document Requested</th>
                                <th>Date Submitted</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="admin-activity-tbody">
                            <?php foreach ($recentRequests as $req):
                                $statusClass = match(strtolower($req['status'])) {
                                    'approved', 'ready for release' => 'ready',
                                    'pending' => 'pending',
                                    'processing' => 'processing',
                                    'claimed', 'released' => 'claimed',
                                    'rejected', 'cancelled' => 'cancelled',
                                    default => 'inactive'
                                };
                            ?>
                            <tr>
                                <td class="fw-semibold heading-green">REQ-<?= str_pad($req['request_id'], 4, '0', STR_PAD_LEFT) ?></td>
                                <td><?= htmlspecialchars($req['first_name'] . ' ' . $req['last_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($req['document_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-muted-soft"><small><?= date('M d, Y', strtotime($req['request_date'])) ?></small></td>
                                <td>
                                    <span class="status-badge status-<?= $statusClass ?>">
                                        <?= htmlspecialchars($req['status'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5" id="admin-activity-empty">
                    <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                    <h6 class="text-muted">No request activity recorded yet</h6>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</main>
