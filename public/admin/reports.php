<?php
require_once __DIR__ . '/../includes/page-guard.php';
require_once __DIR__ . '/../config/database.php';
bdr_require_page_role(['admin']);
$pageTitle = "Reports";
$pageDescription = "Review barangay service and account activity";
$role = $_SESSION['role'] ?? 'admin';
$name = $_SESSION['name'] ?? 'Administrator';
$basePath = '..';

$requestReport = $pdo->query("SELECT DATE(request_date) AS date FROM requests")->fetchAll();
$documentReport = $pdo->query("SELECT DATE(q.request_date) AS date, d.document_name AS document, 1 AS requests, IF(LOWER(q.status) = 'released', d.fee, 0) AS fee FROM requests q JOIN document_types d ON d.document_id = q.document_id")->fetchAll();
$residentReport = $pdo->query("SELECT DATE(created_at) AS date FROM residents")->fetchAll();
$reportFrom = date('Y-m-01');
$reportTo = date('Y-m-t');
?>

<?php include __DIR__ . '/../page-layout/header.php' ?>
<main class="flex-grow-1 overflow-auto p-3 p-lg-4">
    <section class="panel-white p-4 mb-4">
        <div class="mb-3"><h5 class="fw-bold mb-1">Generate Barangay Report</h5>
            <p class="text-muted-soft mb-0">Review monthly service volume, new resident registrations, and document fees.</p>
        </div>
        <div class="row g-3 align-items-end">
            <div class="col-lg-5">
                <label for="reportType" class="form-label">Report Type</label>
                <select class="form-select" id="reportType">
                    <option value="requests">Request Volume</option>
                    <option value="documents">Documents Delivered and Fees</option>
                    <option value="residents">New Residents</option>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label for="reportFrom" class="form-label">From</label>
                <input type="date" class="form-control" id="reportFrom" value="<?= htmlspecialchars($reportFrom) ?>">
            </div>
            <div class="col-6 col-lg-2">
                <label for="reportTo" class="form-label">To</label>
                <input type="date" class="form-control" id="reportTo" value="<?= htmlspecialchars($reportTo) ?>">
            </div>
            <div class="col-lg-3 d-flex gap-2">
                <button type="button" class="btn btn-brand flex-grow-1" id="generateReport">Generate</button>
                <div class="dropdown">
                    <button class="btn btn-outline-brand dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Export</button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <button class="dropdown-item" type="button" data-report-export="PDF">Export PDF</button>
                        </li>
                        <li>
                            <button class="dropdown-item" type="button" data-report-export="CSV">Export CSV</button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
    
    <section class="panel-white p-4" data-report-section="requests" data-report-title="Request Volume" data-report-kind="requests">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="fw-bold mb-1">Request Volume</h5>
                <p class="text-muted-soft mb-0">Total requests received in each month of the selected period.</p>
            </div>
            <span class="badge text-bg-light border" data-report-range><?= htmlspecialchars(date('M j, Y', strtotime($reportFrom)) . ' - ' . date('M j, Y', strtotime($reportTo))) ?></span>
        </div>
        <div class="table-responsive">
            <table class="table table-brand align-middle mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Requests</th>
                    </tr>
                </thead>
                <tbody data-report-summary></tbody>
                <tbody data-report-source hidden>
                    <?php foreach ($requestReport as $request): ?>
                    <tr data-report-date="<?= htmlspecialchars($request['date']) ?>" data-report-count="1"></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    
    <section class="panel-white p-4" data-report-section="documents" data-report-title="Documents Delivered and Fees" data-report-kind="documents" hidden>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="fw-bold mb-1">Documents Delivered and Fees</h5>
                <p class="text-muted-soft mb-0">Compare service demand and recorded fees by document type.</p>
            </div>
            <span class="badge text-bg-light border" data-report-range></span>
        </div>
        <div class="table-responsive">
            <table class="table table-brand align-middle mb-0">
                <thead>
                    <tr>
                        <th>Document</th>
                        <th>Requests</th>
                        <th>Fees Collected</th>
                    </tr>
                </thead>
                <tbody data-report-summary></tbody>
                <tbody data-report-source hidden>
                    <?php foreach ($documentReport as $item): ?>
                        <tr data-report-date="<?= htmlspecialchars($item['date']) ?>" data-report-category="<?= htmlspecialchars($item['document']) ?>" data-report-count="<?= (int) $item['requests'] ?>" data-report-amount="<?= (float) $item['fee'] ?>"></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
        
    <section class="panel-white p-4" data-report-section="residents" data-report-title="New Residents" data-report-kind="residents" hidden>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="fw-bold mb-1">New Residents</h5>
                <p class="text-muted-soft mb-0">Count new resident registrations for each month of the selected period.</p>
            </div>
            <span class="badge text-bg-light border" data-report-range></span>
        </div>
        <div class="table-responsive">
            <table class="table table-brand align-middle mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>New Residents</th>
                    </tr>
                </thead>
                <tbody data-report-summary></tbody>
                <tbody data-report-source hidden>
                    <?php foreach ($residentReport as $resident): ?>
                        <tr data-report-date="<?= htmlspecialchars($resident['date']) ?>" data-report-count="1"></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="adminToast" class="toast app-toast border-0 shadow" role="status" aria-live="polite" aria-atomic="true">
            <div class="d-flex">
            <span class="toast-check"><i class="bi bi-check-lg"></i></span>
                <div class="toast-body"></div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
</main>

<script src="../assets/js/admin-pages.js"></script>
<?php include __DIR__ . '/../page-layout/footer.php' ?>
