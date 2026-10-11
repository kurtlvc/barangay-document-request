<?php
require_once __DIR__ . '/../includes/page-guard.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Enums/PasswordResetStatus.php';
require_once __DIR__ . '/../includes/Repositories/PasswordResetRepositoryInterface.php';
require_once __DIR__ . '/../includes/Repositories/PdoPasswordResetRepository.php';

use App\Repositories\PdoPasswordResetRepository;

bdr_require_page_role(['admin', 'staff']);
$pageTitle = "Password Reset Requests";
$pageDescription = "Manage resident offline password reset requests and credentials";
$role = $_SESSION['role'] ?? 'admin';
$name = $_SESSION['name'] ?? 'Administrator';
$basePath = '..';

$repository = new PdoPasswordResetRepository($pdo);
$requests = $repository->listRequests();
?>

<?php include __DIR__ . '/../page-layout/header.php' ?>
    <main class="flex-grow-1 overflow-auto p-3 p-lg-4">
        <section class="panel-white p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <div>
                    <h5 class="fw-bold mb-1">Resident Password Reset Queue</h5>
                    <p class="text-muted-soft small mb-0">Assistance for residents who forgot security questions or cannot access their accounts.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge text-bg-light border px-3 py-2">
                        Total Requests: <strong id="resetTotalCount"><?= count($requests) ?></strong>
                    </span>
                </div>
            </div>

            <div class="row g-2 align-items-center mb-3">
                <div class="col-12 col-xl-5">
                    <label for="resetSearch" class="visually-hidden">Search requests</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="search" class="form-control" id="resetSearch" placeholder="Search resident name, email, or contact">
                    </div>
                </div>
                <div class="col-12 col-xl-7 d-flex justify-content-end">
                    <div class="btn-group segmented-control" id="resetStatusFilters" role="group" aria-label="Filter password reset requests">
                        <button type="button" class="btn btn-sm btn-outline-brand active" data-reset-status="all" aria-pressed="true">All</button>
                        <button type="button" class="btn btn-sm btn-outline-brand" data-reset-status="pending" aria-pressed="false">Pending</button>
                        <button type="button" class="btn btn-sm btn-outline-brand" data-reset-status="approved" aria-pressed="false">Approved</button>
                        <button type="button" class="btn btn-sm btn-outline-brand" data-reset-status="rejected" aria-pressed="false">Rejected</button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-brand align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Resident</th>
                            <th>Contact</th>
                            <th>Reason / Note</th>
                            <th>Date Requested</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="resetQueueRows">
                        <?php foreach ($requests as $req):
                            $search = strtolower(implode(' ', array_filter([$req['user_name'], $req['user_email'], $req['contact_number'] ?? '', $req['reason'] ?? ''])));
                            $statusStyle = match ($req['status']) {
                                'approved' => 'status-claimed',
                                'rejected' => 'status-inactive',
                                default => 'status-pending',
                            };
                            $statusLabel = match ($req['status']) {
                                'approved' => 'Approved / Reset',
                                'rejected' => 'Rejected',
                                default => 'Pending Review',
                            };
                        ?>
                            <tr data-request-id="<?= (int) $req['id'] ?>"
                                data-user-id="<?= (int) $req['user_id'] ?>"
                                data-search="<?= htmlspecialchars($search) ?>"
                                data-status="<?= htmlspecialchars($req['status']) ?>"
                                data-name="<?= htmlspecialchars($req['user_name']) ?>"
                                data-email="<?= htmlspecialchars($req['user_email']) ?>"
                                data-contact="<?= htmlspecialchars($req['contact_number'] ?? 'N/A') ?>"
                                data-reason="<?= htmlspecialchars($req['reason'] ?? '') ?>"
                                data-admin-notes="<?= htmlspecialchars($req['admin_notes'] ?? '') ?>">
                                <td class="fw-semibold heading-green">
                                    <?= htmlspecialchars($req['user_name']) ?><br>
                                    <small class="text-muted-soft fw-normal"><?= htmlspecialchars($req['user_email']) ?></small>
                                </td>
                                <td class="text-muted-soft text-nowrap"><?= htmlspecialchars($req['contact_number'] ?? 'N/A') ?></td>
                                <td class="text-muted-soft">
                                    <?= htmlspecialchars($req['reason'] ?: 'No explanation provided.') ?>
                                    <?php if (!empty($req['admin_notes'])): ?>
                                        <div class="small text-secondary mt-1"><em>Note: <?= htmlspecialchars($req['admin_notes']) ?></em></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted-soft text-nowrap"><?= htmlspecialchars(date('M j, Y h:i A', strtotime($req['requested_at']))) ?></td>
                                <td><span class="status-badge <?= $statusStyle ?>"><?= $statusLabel ?></span></td>
                                <td class="text-end text-nowrap">
                                    <?php if ($req['status'] === 'pending' && $role === 'admin'): ?>
                                        <button type="button" class="btn btn-sm btn-brand action-icon me-1" data-approve-reset aria-label="Reset password for <?= htmlspecialchars($req['user_name']) ?>" title="Reset Password" data-bs-toggle="tooltip">
                                            <i class="bi bi-key-fill"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger action-icon" data-reject-reset aria-label="Reject reset for <?= htmlspecialchars($req['user_name']) ?>" title="Reject Request" data-bs-toggle="tooltip">
                                            <i class="bi bi-x-circle-fill"></i>
                                        </button>
                                    <?php elseif ($req['status'] === 'pending'): ?>
                                        <span class="small text-muted-soft fst-italic">Admin review required</span>
                                    <?php else: ?>
                                        <small class="text-muted-soft">Resolved by <?= htmlspecialchars($req['resolved_by_name'] ?? 'Admin') ?></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr id="resetQueueEmptyRow" <?= count($requests) > 0 ? 'hidden' : '' ?>>
                            <td colspan="6" class="empty-state py-4">
                                <i class="bi bi-shield-check fs-2 text-muted-soft mb-2"></i>
                                <p class="mb-0 text-muted-soft">No password reset requests matching criteria.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Admin Reset Modal -->
        <div class="modal fade" id="adminResetModal" tabindex="-1" aria-labelledby="adminResetModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form id="adminResetForm" method="post">
                        <input type="hidden" id="resetUserId" name="user_id">
                        <input type="hidden" id="resetRequestId" name="request_id">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title fw-bold heading-green" id="adminResetModalTitle">Reset Account Password</h5>
                                <p class="text-muted-soft small mb-0" id="adminResetSubTitle"></p>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="adminNewPassInput" class="form-label">New Temporary Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control font-monospace" id="adminNewPassInput" name="new_password" required minlength="8" autocomplete="off">
                                    <button class="btn btn-outline-secondary" type="button" id="adminGeneratePassBtn" title="Generate Secure Password">Generate</button>
                                </div>
                                <small class="text-muted-soft">Minimum 8 characters. Share this temporary password securely with the resident.</small>
                            </div>
                            <div class="mb-3">
                                <label for="adminNotesInput" class="form-label">Resolution Notes (Optional)</label>
                                <textarea class="form-control" id="adminNotesInput" name="admin_notes" rows="2" placeholder="e.g. Resident verified in person with valid Barangay ID"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-brand" id="adminResetSubmitBtn">Set New Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Reject Modal -->
        <div class="modal fade" id="rejectResetModal" tabindex="-1" aria-labelledby="rejectResetModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form id="rejectResetForm" method="post">
                        <input type="hidden" id="rejectRequestId" name="request_id">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold text-danger" id="rejectResetModalTitle">Reject Reset Request</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-2">Are you sure you want to reject the password reset request for <strong id="rejectResidentName"></strong>?</p>
                            <div class="mb-3">
                                <label for="rejectReasonInput" class="form-label">Reason for rejection</label>
                                <textarea class="form-control" id="rejectReasonInput" rows="2" placeholder="e.g. Identity could not be verified"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Yes, Reject Request</button>
                        </div>
                    </form>
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
