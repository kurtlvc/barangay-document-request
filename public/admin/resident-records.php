<?php
require_once __DIR__ . '/../includes/page-guard.php';
require_once __DIR__ . '/../config/database.php';
bdr_require_page_role(['admin']);
$pageTitle = "Resident Records";
$pageDescription = "Search and maintain resident profiles";
$role = $_SESSION['role'] ?? 'admin';
$name = $_SESSION['name'] ?? 'Administrator';
$basePath = '..';

$residents = $pdo->query("SELECT r.resident_id, CONCAT(r.first_name, ' ', r.last_name) AS name, COALESCE(u.email, '') AS email, r.contact_number AS contact, r.address, r.created_at AS registered, IF(r.status = 'verified', 'Verified', 'Unverified') AS status FROM residents r LEFT JOIN users u ON u.id = r.user_id ORDER BY r.created_at DESC, r.resident_id DESC")->fetchAll();
?>

<?php include __DIR__ . '/../page-layout/header.php' ?>
    <main class="flex-grow-1 overflow-auto p-3 p-lg-4">
        <section class="panel-white p-4">
            <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                <h5 class="fw-bold mb-1">Resident Accounts</h5>
                <span class="small text-muted-soft text-nowrap">Total: <span id="residentCount"></span></span>
            </div>
            <div class="row g-2 align-items-center mb-2">
                <div class="col-12 col-xl-5">
                    <label for="residentSearch" class="visually-hidden">Search residents</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="search" class="form-control" id="residentSearch" placeholder="Search name, email, or address">
                    </div>
                </div>
                <div class="col-12 col-xl-7 d-flex justify-content-end">
                    <div class="btn-group segmented-control" id="residentStatusFilters" role="group" aria-label="Filter residents by verification status">
                        <button type="button" class="btn btn-sm btn-outline-brand active" data-resident-status="all" aria-pressed="true">All</button>
                        <button type="button" class="btn btn-sm btn-outline-brand" data-resident-status="verified" aria-pressed="false">Verified</button>
                        <button type="button" class="btn btn-sm btn-outline-brand" data-resident-status="unverified" aria-pressed="false">Unverified</button>
                    </div>
                </div>
                
            </div>

            <div class="table-responsive">
                <table class="table table-brand align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Resident</th>
                            <th>Contact</th>
                            <th>Address</th>
                            <th>Registered</th>
                            <th>Verification</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="residentRows">
                        <?php foreach ($residents as $resident):
                            $search = strtolower(implode(' ', [$resident['name'], $resident['email'], $resident['address']]));
                        ?>
                            <tr data-resident-id="<?= (int) $resident['resident_id'] ?>" data-search="<?= htmlspecialchars($search) ?>" data-status="<?= strtolower($resident['status']) ?>" data-name="<?= htmlspecialchars($resident['name']) ?>" data-email="<?= htmlspecialchars($resident['email']) ?>" data-contact="<?= htmlspecialchars($resident['contact']) ?>" data-address="<?= htmlspecialchars($resident['address']) ?>" data-registered="<?= htmlspecialchars($resident['registered']) ?>">
                                <td class="fw-semibold heading-green"><?= htmlspecialchars($resident['name']) ?><br><small class="text-muted-soft fw-normal"><?= htmlspecialchars($resident['email']) ?></small></td>
                                <td class="text-muted-soft text-nowrap"><?= htmlspecialchars($resident['contact']) ?></td>
                                <td class="text-muted-soft"><?= htmlspecialchars($resident['address']) ?></td>
                                <td class="text-muted-soft text-nowrap"><?= htmlspecialchars(date('M j, Y', strtotime($resident['registered']))) ?></td>
                                <td><span class="status-badge <?= $resident['status'] === 'Verified' ? 'status-claimed' : 'status-pending' ?>"><?= htmlspecialchars($resident['status']) ?></span></td>
                                <td class="text-end text-nowrap">
                                    <?php if ($resident['status'] === 'Unverified'): ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary action-icon" data-verify-resident aria-label="Verify <?= htmlspecialchars($resident['name']) ?>" title="Verify" data-bs-toggle="tooltip"><i class="bi bi-person-check-fill"></i></button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary action-icon" data-unverify-resident aria-label="Move <?= htmlspecialchars($resident['name']) ?> back to pending" title="Move back to pending" data-bs-toggle="tooltip"><i class="bi bi-person-dash"></i></button>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary action-icon" data-edit-resident aria-label="Edit <?= htmlspecialchars($resident['name']) ?>" title="Edit" data-bs-toggle="tooltip"><i class="bi bi-pencil"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr id="residentEmptyRow" hidden>
                            <td colspan="6" class="empty-state"><i class="bi bi-search"></i><p class="mb-0 text-muted-soft">No residents match your search or filter.</p></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="d-flex align-items-center justify-content-between gap-2 mt-3">
                <span class="small text-muted-soft" id="residentPageInfo"></span>
                <div class="btn-group" role="group" aria-label="Resident record pages">
                    <button class="btn btn-outline-secondary btn-sm page-arrow" id="residentPrev" type="button" aria-label="Previous" title="Previous" data-bs-toggle="tooltip"><i class="bi bi-chevron-left"></i></button>
                    <button class="btn btn-outline-secondary btn-sm page-arrow" id="residentNext" type="button" aria-label="Next" title="Next" data-bs-toggle="tooltip"><i class="bi bi-chevron-right"></i></button>
                </div>
            </div>
        </section>

        <div class="modal fade" id="residentModal" tabindex="-1" aria-labelledby="residentModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form id="residentForm" method="post" action="">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title fw-bold" id="residentModalTitle">Edit Resident Details</h5>

                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="residentName" class="form-label">Full Name</label>
                                <input class="form-control" id="residentName" name="full_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="residentEmail" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="residentEmail" name="email">
                            </div>
                            <div class="mb-3">
                                <label for="residentContact" class="form-label">Contact Number</label>
                                <input type="tel" class="form-control" id="residentContact" name="contact_number" required>
                            </div>
                            <div class="mb-3">
                                <label for="residentAddress" class="form-label">Address</label>
                                <input class="form-control" id="residentAddress" name="address" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-brand">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="modal fade" id="verifyResidentModal" tabindex="-1" aria-labelledby="verifyResidentModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold heading-green" id="verifyResidentModalTitle">Verify Resident</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0"><span id="verifyResidentPrefix">Are you sure to verify</span> <strong id="verifyResidentName"></strong>? <span id="verifyResidentExtra">Verifying will allow a resident to request a document.</span></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-brand" id="confirmVerifyResident">Yes, Verify Resident</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="toast-container position-fixed bottom-0 end-0 p-3">
            <div id="adminToast" class="toast app-toast border-0 shadow" role="status">
                <div class="d-flex align-items-center gap-3 p-3">
                    <span class="toast-check"><i class="bi bi-check-lg"></i></span>
                    <div class="toast-body"></div>
                    <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>
    </main>

<script src="../assets/js/admin-pages.js"></script>
<?php include __DIR__ . '/../page-layout/footer.php' ?>
