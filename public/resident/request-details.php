<?php
require_once __DIR__ . '/../includes/page-guard.php';
require_once __DIR__ . '/../config/database.php';
bdr_require_page_role(['resident']);
$role = $_SESSION['role'] ?? 'resident';
$name = $_SESSION['name'] ?? 'Resident';
$pageTitle = "My Requests";
$pageDescription = "Track your submitted document requests";
$basePath = '..';

$requestId = filter_var($_GET['req'] ?? null, FILTER_VALIDATE_INT);
$stmt = $pdo->prepare('SELECT q.request_id, q.request_date, q.status, q.remarks, q.purpose, d.document_name FROM requests q JOIN document_types d ON d.document_id = q.document_id WHERE q.request_id = ? AND q.resident_id = ?');
$stmt->execute([$requestId ?: 0, $_SESSION['resident_id'] ?? 0]);
$request = $stmt->fetch();
if ($request) {
    $historyStmt = $pdo->prepare('SELECT status, remarks, updated_at FROM request_history WHERE request_id = ? ORDER BY updated_at, history_id');
    $historyStmt->execute([$request['request_id']]);
    $history = $historyStmt->fetchAll();
}
 
if (!$request) {
    http_response_code(404);
    echo 'Request not found.';
    exit;
}
?>

<?php include __DIR__ . '/../page-layout/header.php' ?>
    <main class="resident-content flex-grow-1 overflow-auto p-3 p-lg-4">
        <a href="my-requests.php" class="back-to-details d-inline-flex align-items-center text-decoration-none heading-green mb-3"><i class="bi bi-arrow-left fs-4"></i></a>

        <div class="row g-4 mx-auto">
            <div class="col-lg-6">
                <div class="panel detail-panel p-4 p-lg-5 h-100">
                    <h4 class="heading-green fw-bold mb-4">Request Info</h4>

                    <p class="text-muted-soft mb-1">Document Type</p>
                    <p class="fw-bold heading-green mb-4"><?= htmlspecialchars($request['document_name']) ?></p>

                    <p class="text-muted-soft mb-1">Purpose</p>
                    <p class="mb-4"><?= htmlspecialchars($request['purpose'] ?? '') ?></p>

                    <p class="text-muted-soft mb-1">Date Requested</p>
                    <p class="fw-bold heading-green mb-4"><?= htmlspecialchars(date('M j, Y', strtotime($request['request_date']))) ?></p>

                    <p class="text-muted-soft mb-1">Current Status</p>
                    <span class="status-badge status-<?= htmlspecialchars(strtolower($request['status']) === 'ready for release' ? 'ready' : (strtolower($request['status']) === 'released' ? 'claimed' : strtolower($request['status']))) ?>">
                        <?= htmlspecialchars($request['status']) ?>
                    </span>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="panel detail-panel p-4 p-lg-5 h-100">
                    <h4 class="heading-green fw-bold mb-4">Status History</h4>

                    <?php foreach ($history as $step): ?>
                        <div class="status-step is-complete">
                            <div class="step-circle">
                                <?php if ($step['done']): ?>
                                    <i class="bi-check-lg"></i>
                                <?php endif; ?>
                            </div>
                            <p class="step-title mb-1"><?= htmlspecialchars($step['status']) ?></p>
                            <p class="step-date mb-0"><?= htmlspecialchars(date('M j, Y g:i A', strtotime($step['updated_at']))) ?><?= $step['remarks'] ? ' · ' . htmlspecialchars($step['remarks']) : '' ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>
<?php include __DIR__ . '/../page-layout/footer.php' ?>
