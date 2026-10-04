<?php
require_once __DIR__ . '/../includes/page-guard.php';
require_once __DIR__ . '/../config/database.php';
bdr_require_page_role(['staff']);
$pageTitle = "Release Management";
$pageDescription = "Complete requests when documents are claimed";
$role = $_SESSION['role'] ?? 'staff';
$name = $_SESSION['name'] ?? 'Staff';
$basePath = '..';

$notifications = [];
$readyForRelease = $pdo->query("SELECT q.request_id, CONCAT(r.first_name, ' ', r.last_name) AS name, d.document_name AS doc, CONCAT('REQ-', q.request_id) AS ref FROM requests q JOIN residents r ON r.resident_id = q.resident_id JOIN document_types d ON d.document_id = q.document_id WHERE LOWER(q.status) = 'ready for release' ORDER BY q.request_date")->fetchAll();
$recentlyClaimed = $pdo->query("SELECT q.request_id, CONCAT(r.first_name, ' ', r.last_name) AS name, d.document_name AS doc, CONCAT('REQ-', q.request_id) AS ref, DATE_FORMAT(q.request_date, '%b %e, %Y') AS when FROM requests q JOIN residents r ON r.resident_id = q.resident_id JOIN document_types d ON d.document_id = q.document_id WHERE LOWER(q.status) = 'released' ORDER BY q.request_date DESC LIMIT 10")->fetchAll();
?>

<?php include __DIR__ . '/../page-layout/header.php' ?>
    <main class="flex-grow-1 overflow-auto p-3 p-lg-4">
        <div class="col-lg mb-4">
            <section class="panel-white p-4 h-100">
                <h5 class="fw-bold mb-3">Ready for Release <span class="text-muted-soft fw-normal">&middot; <span id="readyForReleaseCount"><?= count($readyForRelease) ?></span></span></h5>

                <div class="table-responsive">
                    <table class="table table-brand align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Resident</th>
                                <th>Document</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="readyForReleaseBody">
                            <?php if (empty($readyForRelease)): ?>
                                <tr>
                                    <td colspan="2" class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <p class="empty-text mb-0">Nothing waiting for release right now.</p>
                                    </td>
                                </tr>
                            <?php else: foreach ($readyForRelease as $r): ?>
                                <tr data-request-id="<?= (int) $r['request_id'] ?>" data-name="<?= htmlspecialchars($r['name']) ?>" data-doc="<?= htmlspecialchars($r['doc']) ?>" data-ref="<?= htmlspecialchars($r['ref']) ?>">
                                    <td class="fw-semibold heading-green">
                                        <?= htmlspecialchars($r['name']) ?><br>
                                        <small class="text-muted-soft fw-normal"> <?= htmlspecialchars($r['ref']) ?></small>
                                    </td>
                                    <td class="fw-semibold heading-green">
                                        <?= htmlspecialchars($r['doc']) ?>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-brand btn-sm mark-claimed-btn">Mark as Claimed</button>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="col-lg">
            <section class="panel-white p-4 h-100">
                <h5 class="fw-bold mb-3">Recently Claimed <span class="text-muted-soft fw-normal">&middot; <?= count($recentlyClaimed) ?></span></h5>

                <div class="table-responsive">
                    <table class="table table-brand align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Resident</th>
                                <th>Document</th>
                                <th>Claimed</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody id="recentlyClaimedBody">
                            <?php foreach ($recentlyClaimed as $r): ?>
                                <tr>
                                    <td class="fw-semibold heading-green">
                                        <?= htmlspecialchars($r['name']) ?><br>
                                        <small class="text-muted-soft fw-normal"> <?= htmlspecialchars($r['ref']) ?></small>
                                    </td>
                                    <td class="fw-semibold heading-green">
                                        <?= htmlspecialchars($r['doc']) ?>
                                    </td>
                                    <td class="text-muted-soft"><?= htmlspecialchars($r['when']) ?></td>
                                    <td class="text-end"><span class="status-badge status-claimed"><i class="bi bi-check-circle-fill"></i> Claimed</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <!-- Confirm-release modal -->
        <div class="modal fade" id="confirmReleaseModal" tabindex="-1" aria-labelledby="confirmReleaseModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold heading-green" id="confirmReleaseModalLabel">Confirm Release</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-1">Mark <strong id="confirmReleaseLabel"></strong> as claimed?</p>
                        <p class="text-muted-soft mb-0" style="font-size:.9rem">This records the date, time, and your name as the releasing staff, and cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-brand" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-brand" id="confirmReleaseBtn">Confirm Release</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- "Marked as claimed" toast — reuses styles.css's existing .request-toast rules -->
        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080">
            <div id="releasedToast" class="toast request-toast p-3" role="status" aria-live="polite" aria-atomic="true">
                <div class="d-flex align-items-start gap-3">
                    <span class="toast-check"><i class="bi bi-check-lg"></i></span>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <strong class="heading-green">Mark as Claimed</strong>
                            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                        <p class="mb-2 text-muted-soft" id="releasedToastBody" style="font-size:.9rem"></p>
                    </div>
                    </div>
                </div>
            </div>
        </div>

    </main>

<script src="../assets/js/staff.js"></script>
<?php include __DIR__ . '/../page-layout/footer.php' ?>
