<?php
require_once __DIR__ . '/../includes/page-guard.php';
require_once __DIR__ . '/../config/database.php';
bdr_require_page_role(['resident']);
$pageTitle = "My Requests";
$pageDescription = "Track the status of your submitted document requests here";
$role = $_SESSION['role'] ?? 'resident';
$name = $_SESSION['name'] ?? 'Resident';
$basePath = '..';

$stmt = $pdo->prepare('SELECT q.request_id, q.request_date, q.status, d.document_name FROM requests q JOIN document_types d ON d.document_id = q.document_id WHERE q.resident_id = ? ORDER BY q.request_date DESC, q.request_id DESC');
$stmt->execute([$_SESSION['resident_id'] ?? 0]);
$requests = $stmt->fetchAll();
?>

<?php include __DIR__ . '/../page-layout/header.php' ?>
    <main class="resident-content flex-grow-1 overflow-auto p-3 p-lg-4">
        <div class="panel request-list-panel p-3 p-lg-4 mx-auto">

    <?php if (count($requests) === 0): ?>
        <div class="empty-state">
            <i class="bi bi-file-earmark-text"></i>
            <p class="fw-semibold heading-green mt-3 mb-1">No requests yet</p>
            <p class="text-muted-soft mb-3">Requests you submit will show up here so you can track their status.</p>
            <a href="new-request.php" class="btn btn-outline-brand">Request a Document</a>
        </div>
    <?php else: ?>
        <div class="table-responsive d-none d-md-block">
            <table class="table table-brand mb-0">
                <thead>
                    <tr>
                        <th>Document Type</th>
                        <th>Request Number</th>
                        <th>
                            Date Requested
                            <button type="button" class="sort-caret" aria-label="Sort by date requested"><i class="bi bi-caret-down-fill"></i></button>
                        </th>
                        <th>
                            Status
                        </th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                        <?php foreach ($requests as $req): $status = strtolower($req['status']); ?>
                        <tr>
                            <td class="fw-semibold heading-green"><?= htmlspecialchars($req['document_name']) ?></td>
                            <td class="text-muted-soft">REQ-<?= (int) $req['request_id'] ?></td>
                            <td class="text-muted-soft"><?= htmlspecialchars(date('M j, Y', strtotime($req['request_date']))) ?></td>
                            <td>
                                <span class="status-badge status-<?= htmlspecialchars($status === 'ready for release' ? 'ready' : ($status === 'released' ? 'claimed' : $status)) ?>">
                                    <?= htmlspecialchars(ucfirst($req['status'])) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="request-details.php?req=<?= (int) $req['request_id'] ?>" class="view-link">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="request-card-list d-md-none">
            <?php foreach ($requests as $req): $status = strtolower($req['status']);
                $badgeClass = $status === 'ready for release' ? 'ready' : ($status === 'released' ? 'claimed' : $status); ?>
            <div class="request-card">
                <div class="request-card-top">
                    <span class="request-card-title"><?= htmlspecialchars($req['document_name']) ?></span>
                    <span class="status-badge status-<?= htmlspecialchars($badgeClass) ?>"><?= htmlspecialchars(ucfirst($req['status'])) ?></span>
                </div>
                <div class="request-card-meta">
                    <span>REQ-<?= (int) $req['request_id'] ?></span>
                    <span><?= htmlspecialchars(date('M j, Y', strtotime($req['request_date']))) ?></span>
                </div>
                <a href="request-details.php?req=<?= (int) $req['request_id'] ?>" class="view-link request-card-link">
                    <i class="bi bi-eye"></i> View details
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
    </main>
<?php include __DIR__ . '/../page-layout/footer.php' ?>
