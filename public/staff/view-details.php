<?php
require_once __DIR__ . '/../includes/page-guard.php';
bdr_require_page_role(['staff']);
$pageTitle = 'Request Details';
$pageDescription = 'Review and process a resident document request';
$role = $_SESSION['role'] ?? 'staff';
$name = $_SESSION['name'] ?? 'Staff';
$basePath = '..';

require_once __DIR__ . "/../config/database.php";
$requestId = filter_var($_GET["req"] ?? null, FILTER_VALIDATE_INT);
$stmt = $pdo->prepare("SELECT q.request_id, CONCAT(r.first_name, \" \", r.last_name) AS name, u.email, r.contact_number AS contact, r.address, d.document_name AS document, q.purpose, q.request_date, q.status FROM requests q JOIN residents r ON r.resident_id = q.resident_id LEFT JOIN users u ON u.id = r.user_id JOIN document_types d ON d.document_id = q.document_id WHERE q.request_id = ?");
$stmt->execute([$requestId ?: 0]); $row = $stmt->fetch();
$request = $row ? ["id" => (int) $row["request_id"], "number" => "REQ-" . $row["request_id"], "name" => $row["name"], "email" => $row["email"] ?? "", "contact" => $row["contact"], "address" => $row["address"], "document" => $row["document"], "purpose" => $row["purpose"] ?? "", "date" => date("M j, Y", strtotime($row["request_date"])), "status" => strtolower($row["status"]) === "ready for release" ? "ready" : (strtolower($row["status"]) === "released" ? "claimed" : strtolower($row["status"]))] : null;
if (!$request) http_response_code(404);
$requestNumber = $request["number"] ?? "";

$statusLabels = [
    'pending' => 'Pending',
    'processing' => 'Processing',
    'ready' => 'Ready for Release',
    'claimed' => 'Released',
];
$statusStages = ['pending', 'processing', 'ready', 'claimed'];
$stageIcons = ['bi-hourglass-split', 'bi-gear-fill', 'bi-patch-check-fill', 'bi-box-seam-fill'];
$actionLabels = [
    'pending' => 'Start Processing',
    'processing' => 'Approve Request',
    'ready' => 'Release Document',
];
?>

<?php include __DIR__ . '/../page-layout/header.php' ?>
    <main class="resident-content flex-grow-1 overflow-auto p-3 p-lg-4">
        <div class="mx-auto" style="max-width: 1120px">
            <a href="process-requests.php" class="back-to-details d-inline-flex align-items-center gap-2 text-decoration-none heading-green mb-3">
                <i class="bi bi-arrow-left" aria-hidden="true"></i><span>Back to requests</span>
            </a>

            <?php if (!$request): ?>
                <section class="panel-white p-4">
                    <h1 class="h5 fw-bold heading-green">Request not found</h1>
                    <p class="text-muted-soft mb-3">This request number is not available in the current request list.</p>
                    <a href="process-requests.php" class="btn btn-brand">Return to requests</a>
                </section>
            <?php else:
                $currentStage = array_search($request['status'], $statusStages, true);
                if ($currentStage === false) $currentStage = 0;
            ?>
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                    <div>
                        <p class="small text-muted-soft mb-1">Request details</p>
                        <h1 class="h3 fw-bold heading-green mb-1"><?= htmlspecialchars($request['document']) ?></h1>
                        <span class="text-muted-soft"><?= htmlspecialchars($requestNumber) ?></span>
                    </div>
                    <span id="requestStatus" class="status-badge status-<?= htmlspecialchars($request['status']) ?>">
                        <?= htmlspecialchars($statusLabels[$request['status']] ?? ucfirst($request['status'])) ?>
                    </span>
                </div>

                <div class="row g-3 g-lg-4">
                    <div class="col-lg-5">
                        <section class="panel-white p-4 h-100">
                            <h2 class="h5 fw-bold heading-green mb-4">Resident details</h2>
                            <dl class="mb-0">
                                <dt class="small text-muted-soft fw-normal mb-1">Full name</dt>
                                <dd class="fw-semibold mb-3"><?= htmlspecialchars($request['name']) ?></dd>
                                <dt class="small text-muted-soft fw-normal mb-1">Email address</dt>
                                <dd class="mb-3"><a class="heading-green" href="mailto:<?= htmlspecialchars($request['email']) ?>"><?= htmlspecialchars($request['email']) ?></a></dd>
                                <dt class="small text-muted-soft fw-normal mb-1">Contact number</dt>
                                <dd class="mb-3"><a class="heading-green" href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $request['contact'])) ?>"><?= htmlspecialchars($request['contact']) ?></a></dd>
                                <dt class="small text-muted-soft fw-normal mb-1">Home address</dt>
                                <dd class="mb-0"><?= htmlspecialchars($request['address']) ?></dd>
                            </dl>
                        </section>
                    </div>

                    <div class="col-lg-7">
                        <section class="panel-white p-4 mb-3">
                            <h2 class="h5 fw-bold heading-green mb-4">Request information</h2>
                            <dl class="row mb-0">
                                <dt class="col-sm-4 small text-muted-soft fw-normal mb-1">Document</dt>
                                <dd class="col-sm-8 fw-semibold mb-3"><?= htmlspecialchars($request['document']) ?></dd>
                                <dt class="col-sm-4 small text-muted-soft fw-normal mb-1">Purpose</dt>
                                <dd class="col-sm-8 mb-3"><?= htmlspecialchars($request['purpose']) ?></dd>
                                <dt class="col-sm-4 small text-muted-soft fw-normal mb-1">Date requested</dt>
                                <dd class="col-sm-8 mb-0"><?= htmlspecialchars($request['date']) ?></dd>
                            </dl>
                        </section>

                        <section class="panel-white p-4">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
                                <div>
                                    <h2 class="h5 fw-bold heading-green mb-1">Request progress</h2>
                                    <p class="small text-muted-soft mb-0">Review and move the request through each stage.</p>
                                </div>
                            </div>
                            <div id="requestTimeline" data-request-id="<?= (int) $request["id"] ?>" class="request-timeline mb-4" data-stage="<?= (int) $currentStage ?>">
                                <?php foreach (['Pending', 'Processing', 'Approved', 'Released'] as $index => $step): ?>
                                    <div class="status-step <?= $index < $currentStage ? 'is-complete' : ($index === $currentStage ? 'is-current' : '') ?>" data-step="<?= $index ?>"<?= $index === $currentStage ? ' aria-current="step"' : '' ?>>
                                        <div class="step-circle">
                                            <?php if ($index < $currentStage): ?><i class="bi bi-check-lg" aria-hidden="true"></i><?php endif; ?>
                                            <?php if ($index === $currentStage): ?><i class="bi <?= htmlspecialchars($stageIcons[$index]) ?>" aria-hidden="true"></i><?php endif; ?>
                                        </div>
                                        <p class="step-title mb-1"><?= htmlspecialchars($step) ?></p>
                                        <p class="step-date mb-0" data-step-date><?= $index === 0 ? 'Submitted ' . htmlspecialchars($request['date']) : ($index <= $currentStage ? 'Completed' : 'Awaiting update') ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 border-top pt-3">
                                <span id="workflowMessage" class="small text-muted-soft" aria-live="polite">
                                    <?= $request['status'] === 'claimed' ? 'This request has been released.' : 'Next: ' . htmlspecialchars($actionLabels[$request['status']] ?? 'No further action') ?>
                                </span>
                                <?php if (isset($actionLabels[$request['status']])): ?>
                                    <button type="button" class="btn btn-brand" id="requestAction" data-next-status="<?= htmlspecialchars($request['status'] === 'pending' ? 'processing' : ($request['status'] === 'processing' ? 'ready' : 'claimed')) ?>">
                                        <i class="bi <?= $request['status'] === 'pending' ? 'bi-play-fill' : ($request['status'] === 'processing' ? 'bi-check-lg' : 'bi-box-arrow-up-right') ?> me-1" aria-hidden="true"></i>
                                        <?= htmlspecialchars($actionLabels[$request['status']]) ?>
                                    </button>
                                <?php else: ?>
                                    <span class="status-badge status-claimed"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Complete</span>
                                <?php endif; ?>
                            </div>
                        </section>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($request): ?>
            <div class="modal fade" id="confirmRequestRelease" tabindex="-1" aria-labelledby="confirmRequestReleaseTitle" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 class="modal-title h5 fw-bold heading-green" id="confirmRequestReleaseTitle">Confirm document release</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Mark <strong><?= htmlspecialchars($request['document']) ?></strong> for <strong><?= htmlspecialchars($request['name']) ?></strong> as released?
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-brand" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-brand" id="confirmRequestReleaseButton">Confirm release</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

<script src="../assets/js/staff.js"></script>
<?php include __DIR__ . '/../page-layout/footer.php' ?>