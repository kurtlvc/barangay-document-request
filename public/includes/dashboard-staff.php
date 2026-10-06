<?php
$basePath = $basePath ?? (function_exists('bdr_base_path') ? bdr_base_path() : '.');
?>
<main class="flex-grow-1 overflow-auto p-3 p-lg-4">
<div class="dashboard-staff">
    <div class="stats-panel mb-4">
        <div class="row g-0">
            <div class="col-12 col-lg-4">
                <div class="stat-card h-100 px-3 py-3 border-end">
                    <p class="stat-card-header mb-2">Pending Review</p>
                    <div class="d-flex align-items-end gap-2">
                        <p class="stat-card-value stat-card-value-large" data-stat="pending"><?= (int)($stats['pending'] ?? 0) ?></p>
                        <p class="stat-card-meta">requests</p>
                    </div>
                </div>
            </div>
            <div class="col-4 col-lg">
                <div class="stat-card h-100 px-3 py-3 border-end">
                    <p class="stat-card-header text-secondary mb-2">Approved</p>
                    <div class="d-flex align-items-end gap-2">
                        <p class="stat-card-value" data-stat="approved"><?= (int)($stats['approved'] ?? 0) ?></p>
                        <p class="stat-card-meta">requests</p>
                    </div>
                </div>
            </div>
            <div class="col-4 col-lg">
                <div class="stat-card h-100 px-3 py-3 border-end">
                    <p class="stat-card-header text-secondary mb-2">Released / Claimed</p>
                    <div class="d-flex align-items-end gap-2">
                        <p class="stat-card-value" data-stat="released"><?= (int)($stats['released'] ?? 0) ?></p>
                        <p class="stat-card-meta">documents</p>
                    </div>
                </div>
            </div>
            <div class="col-4 col-lg">
                <div class="stat-card h-100 px-3 py-3">
                    <p class="stat-card-header text-secondary mb-2">Total System Requests</p>
                    <div class="d-flex align-items-end gap-2">
                        <p class="stat-card-value" data-stat="total"><?= (int)($stats['total'] ?? 0) ?></p>
                        <p class="stat-card-meta">requests</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Operations Bar -->
    <div class="card border-0 shadow-sm rounded-3 p-3 mb-4 bg-white">
        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
            <h6 class="fw-bold mb-0 text-muted"><i class="bi bi-lightning-charge me-1"></i> Quick Staff Operations:</h6>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= $basePath ?>/staff/process-requests.php" class="btn btn-sm btn-primary">
                    <i class="bi bi-clipboard2-check-fill me-1"></i> Process Queue
                </a>
                <a href="<?= $basePath ?>/staff/release-management.php" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-collection-fill me-1"></i> Release Desk
                </a>
                <a href="<?= $basePath ?>/staff/reports.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-bar-chart-fill me-1"></i> Activity Reports
                </a>
            </div>
        </div>
    </div>

    <!-- Unprocessed Requests Table / Queue -->
    <div class="panel-white p-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h5 class="fw-bold mb-0">Document Request Queue</h5>
                <small class="text-muted" id="dashboard-last-updated">Requests submitted by residents requiring review and approval</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-brand d-inline-flex align-items-center gap-1" id="dashboard-refresh-btn" title="Refresh Queue">
                    <i class="bi bi-arrow-clockwise"></i> <span>Refresh</span>
                </button>
                <a href="<?= $basePath ?>/staff/process-requests.php" class="btn btn-sm btn-outline-brand d-inline-flex align-items-center">Open Full Queue</a>
            </div>
        </div>

        <div id="staff-requests-container">
            <?php if (!empty($recentRequests)): ?>
                <div class="table-responsive">
                    <table class="table table-brand align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Ref #</th>
                                <th>Resident</th>
                                <th>Document Requested</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="staff-requests-tbody">
                            <?php foreach ($recentRequests as $req):
                            ?>
                            <tr>
                                <td class="fw-semibold heading-green">REQ-<?= str_pad($req['request_id'], 4, '0', STR_PAD_LEFT) ?></td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($req['first_name'] . ' ' . $req['last_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                <td><?= htmlspecialchars($req['document_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-muted-soft"><small><?= htmlspecialchars($req['contact_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></small></td>
                                <td class="text-muted-soft"><small><?= date('M d, Y', strtotime($req['request_date'])) ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5" id="staff-requests-empty">
                    <i class="bi bi-check2-all fs-1 text-success d-block mb-3"></i>
                    <h6 class="text-muted">Queue is currently clear!</h6>
                    <p class="small text-muted mb-0">There are no pending requests waiting for review.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</main>
