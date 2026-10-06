<?php
require_once __DIR__ . '/../includes/page-guard.php';
require_once __DIR__ . '/../config/database.php';
bdr_require_page_role(['staff']);
$pageTitle = "Process Requests";
$pageDescription = "Review, approve, or reject resident requests";
$role = $_SESSION['role'] ?? 'staff';
$name = $_SESSION['name'] ?? 'Staff';
$basePath = '..';

$notifications = [];

$statusLabels = [
    'pending' => 'Pending', 'processing' => 'Processing',
    'ready' => 'Ready for Release', 'claimed' => 'Claimed', 'cancelled' => 'Cancelled',
];

$requests = $pdo->query("SELECT CONCAT(r.first_name, ' ', r.last_name) AS name, CONCAT('REQ-', q.request_id) AS ref, q.request_id, d.document_name AS doc, DATE_FORMAT(q.request_date, '%b %e, %Y') AS date, CASE LOWER(q.status) WHEN 'ready for release' THEN 'ready' WHEN 'released' THEN 'claimed' ELSE LOWER(q.status) END AS status FROM requests q JOIN residents r ON r.resident_id = q.resident_id JOIN document_types d ON d.document_id = q.document_id ORDER BY q.request_date DESC, q.request_id DESC")->fetchAll();

// Sort so the requests most needing attention float to the top.
$order = ['pending' => 1, 'processing' => 2, 'ready' => 3, 'claimed' => 4];
usort($requests, fn($a, $b) => ($order[$a['status']] ?? 9) <=> ($order[$b['status']] ?? 9));
?>

<?php include __DIR__ . '/../page-layout/header.php' ?>
    <main class="flex-grow-1 overflow-auto p-3 p-lg-4">

        <section class="panel-white p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <h5 class="fw-bold mb-1">All Requests</h5>
                <span class="small text-muted-soft" id="requestCount"></span>
            </div>
            <div class="row g-2 align-items-center mb-3">
                <div class="col-md-7 col-lg-5">
                    <label for="reqSearch" class="visually-hidden">Search requests</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="search" id="reqSearch" class="form-control" placeholder="Search name or request number">
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-3">
                    <label for="reqStatusFilter" class="visually-hidden">Filter requests by status</label>
                    <select id="reqStatusFilter" class="form-select">
                        <option value="all">All statuses</option>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="ready">Ready for release</option>
                        <option value="claimed">Claimed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-brand align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Resident</th>
                            <th>Document Type</th>
                            <th class="text-center">Date Filed</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="requestRows">
                        <?php foreach ($requests as $r):
                            $blob = strtolower($r['name'] . ' ' . $r['ref'] . ' ' . $r['doc']);
                        ?>
                        <tr data-status="<?= htmlspecialchars($r['status']) ?>" data-search="<?= htmlspecialchars($blob) ?>">
                            <td class="fw-semibold heading-green">
                                <?= htmlspecialchars($r['name']) ?><br>
                                <small class="text-muted-soft fw-normal"><?= htmlspecialchars($r['ref']) ?></small>
                            </td>
                            <td class="text-muted-soft"><?= htmlspecialchars($r['doc']) ?></td>
                            <td class="text-muted-soft text-center"><?= htmlspecialchars($r['date']) ?></td>
                            <td class="text-center"><span class="status-badge status-<?= htmlspecialchars($r['status']) ?>"><?= htmlspecialchars($statusLabels[$r['status']]) ?></span></td>
                            <td class="text-end"><a href="view-details.php?req=<?= (int) $r['request_id'] ?>" class="view-link" title="View" data-bs-toggle="tooltip"><i class="bi bi-eye"></i> View</a></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr id="noResultsRow" hidden>
                            <td colspan="5" class="empty-state">
                                <i class="bi bi-search"></i>
                                <p class="mb-0 text-muted-soft">No requests match your search or filter.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

    </main>

<script src="../assets/js/staff.js"></script>
<?php include __DIR__ . '/../page-layout/footer.php' ?>
