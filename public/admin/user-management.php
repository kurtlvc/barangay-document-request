<?php
require_once __DIR__ . '/../includes/page-guard.php';
require_once __DIR__ . '/../config/database.php';
bdr_require_page_role(['admin']);
$pageTitle = "User Management";
$pageDescription = "Manage staff access and administrator accounts";
$role = $_SESSION['role'] ?? 'admin';
$name = $_SESSION['name'] ?? 'Administrator';
$basePath = '..';

$accounts = $pdo->query("SELECT id, name, email, role, contact_number AS contact, IF(is_active, 'Active', 'Deactivated') AS status FROM users WHERE role IN ('staff', 'admin') ORDER BY created_at DESC, id DESC")->fetchAll();
?>

<?php include __DIR__ . '/../page-layout/header.php' ?>
    <main class="flex-grow-1 overflow-auto p-3 p-lg-4">
        <section class="panel-white p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <h5 class="fw-bold mb-1">Staff and Administrator Accounts</h5>
                <button class="btn btn-brand d-flex align-items-center gap-2" type="button" data-bs-toggle="modal" data-bs-target="#accountModal"><i class="bi bi-person-plus-fill"></i>Add Account</button>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-12 col-xl-5">
                    <label class="visually-hidden" for="accountSearch">Search accounts</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="search" class="form-control" id="accountSearch" placeholder="Search name or email">
                    </div>
                </div>
                <div class="col-12 col-xl-7 d-flex justify-content-end">
                    <div class="btn-group segmented-control" id="accountRoleFilters" role="group" aria-label="Filter accounts by role">
                        <button type="button" class="btn btn-sm btn-outline-brand active" data-account-role="all" aria-pressed="true">All Roles</button>
                        <button type="button" class="btn btn-sm btn-outline-brand" data-account-role="staff" aria-pressed="false">Staff</button>
                        <button type="button" class="btn btn-sm btn-outline-brand" data-account-role="admin" aria-pressed="false">Admin</button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-brand align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Name / Email</th>
                            <th>Role</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="accountRows">
                        <?php foreach ($accounts as $account):
                            $search = strtolower(implode(' ', [$account['name'], $account['email']]));
                        ?>
                            <tr data-user-id="<?= (int) $account['id'] ?>" data-search="<?= htmlspecialchars($search) ?>" data-role="<?= strtolower($account['role']) ?>" data-status="<?= strtolower($account['status']) ?>"
                                data-name="<?= htmlspecialchars($account['name']) ?>" data-email="<?= htmlspecialchars($account['email']) ?>" data-contact="<?= htmlspecialchars($account['contact'] ?? '') ?>">
                                <td class="fw-semibold heading-green"><?= htmlspecialchars($account['name']) ?><br><small class="text-muted-soft fw-normal"><?= htmlspecialchars($account['email']) ?></small></td>
                                <td><span class="badge text-bg-light border"><?= htmlspecialchars($account['role']) ?></span></td>
                                <td class="text-muted-soft text-nowrap"><?= htmlspecialchars($account['contact'] ?? 'N/A') ?></td>
                                <td><span class="status-badge <?= $account['status'] === 'Active' ? 'status-claimed' : 'status-inactive' ?>"><?= htmlspecialchars($account['status']) ?></span></td>
                                <td class="text-end text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-secondary action-icon" data-edit-account aria-label="Edit <?= htmlspecialchars($account['name']) ?>" title="Edit" data-bs-toggle="tooltip"><i class="bi bi-pencil"></i></button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary action-icon" data-toggle-account aria-label="<?= $account['status'] === 'Active' ? 'Deactivate' : 'Reactivate' ?> <?= htmlspecialchars($account['name']) ?>" title="<?= $account['status'] === 'Active' ? 'Deactivate' : 'Reactivate' ?>" data-bs-toggle="tooltip"><i class="bi <?= $account['status'] === 'Active' ? 'bi-person-dash' : 'bi-person-check' ?>"></i></button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary action-icon" data-delete-account aria-label="Delete <?= htmlspecialchars($account['name']) ?>" title="Delete" data-bs-toggle="tooltip"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr id="accountEmptyRow" hidden>
                            <td colspan="5" class="empty-state"><i class="bi bi-search"></i>
                                <p class="mb-0 text-muted-soft">No accounts match your search or filters.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="modal fade" id="accountConfirmModal" tabindex="-1" aria-labelledby="accountConfirmTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="accountConfirmTitle">Confirm Account Change</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body"><p class="mb-0" id="accountConfirmMessage"></p></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-brand" id="accountConfirmButton">Confirm</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="accountModal" tabindex="-1" aria-labelledby="accountModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form id="accountForm" data-admin-form="account" data-mode="create" method="post" action="">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title fw-bold" id="accountModalTitle">Add Account</h5>
                                <p class="text-muted-soft small mb-0">Create staff or administrator access.</p>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="accountName" class="form-label">Full Name</label>
                                <input class="form-control" id="accountName" name="full_name" autocomplete="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="accountEmail" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="accountEmail" name="email" autocomplete="email" required>
                            </div>
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label for="accountRole" class="form-label">Role</label>
                                    <select class="form-select" id="accountRole" name="role" required>
                                        <option value="staff">Staff</option>
                                        <option value="admin">Administrator</option>
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <label for="accountContact" class="form-label">Contact Number</label>
                                    <input type="tel" class="form-control" id="accountContact" name="contact_number" autocomplete="tel">
                                </div>
                            </div>
                            <div class="mt-3" id="accountPasswordField">
                                <label for="accountPassword" class="form-label">Temporary Password</label>
                                <input type="password" class="form-control" id="accountPassword" name="temporary_password" minlength="8" autocomplete="new-password" required>
                                <p class="form-text">The account holder can change this after signing in.</p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-brand">Create Account</button>
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
