<?php
require_once __DIR__ . '/../includes/page-guard.php';
require_once __DIR__ . '/../config/database.php';
bdr_require_page_role(['admin']);
$pageTitle = "Document Types";
$pageDescription = "Manage available barangay documents and requirements";
$role = $_SESSION['role'] ?? 'admin';
$name = $_SESSION['name'] ?? 'Administrator';
$basePath = '..';

$documents = $pdo->query("SELECT document_id, document_number, document_name AS name, fee, requirements, processing_days AS turnaround_days, is_active FROM document_types ORDER BY document_name")->fetchAll();
?>

<?php include __DIR__ . '/../page-layout/header.php' ?>
    <main class="flex-grow-1 overflow-auto p-3 p-lg-4">
        <section class="panel-white p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
               <h5 class="fw-bold mb-1">Document Catalog</h5>
                <button class="btn btn-brand d-flex align-items-center gap-2" type="button" data-bs-toggle="modal" data-bs-target="#documentModal"><i class="bi bi-file-earmark-plus-fill"></i> Add Document Type</button>
            </div>
            <div class="row g-2 mb-2">
                <div class="col-12 col-xl-5">
                    <label class="visually-hidden" for="documentSearch">Search document types</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="search" class="form-control" id="documentSearch" placeholder="Search document or requirement">
                    </div>
                </div>
                <div class="col-12 col-xl-7 d-flex justify-content-end">
                    <div class="btn-group segmented-control" id="documentStatusFilters" role="group" aria-label="Filter document types by status">
                        <button type="button" class="btn btn-sm btn-outline-brand active" data-document-status="all" aria-pressed="true">All</button>
                        <button type="button" class="btn btn-sm btn-outline-brand" data-document-status="active" aria-pressed="false">Active</button>
                        <button type="button" class="btn btn-sm btn-outline-brand" data-document-status="deactivated" aria-pressed="false">Deactivated</button>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-brand align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Document Type</th>
                            <th>Fee</th>
                            <th>Requirements</th>
                            <th>Turnaround</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="documentRows">
                        <?php foreach ($documents as $document): $search = strtolower(implode(' ', [$document['name'], $document['requirements'] ?? ''])); $document['status'] = (int) $document['is_active'] ? 'Active' : 'Deactivated'; ?>
                            <tr data-document-id="<?= (int) $document['document_id'] ?>" data-number="<?= htmlspecialchars($document['document_number']) ?>" data-search="<?= htmlspecialchars($search) ?>" data-status="<?= strtolower($document['status']) ?>" data-name="<?= htmlspecialchars($document['name']) ?>" data-fee="<?= htmlspecialchars($document['fee']) ?>" data-requirements="<?= htmlspecialchars($document['requirements'] ?? '') ?>" data-turnaround="<?= (int) $document['turnaround_days'] ?>">
                                <td class="fw-semibold heading-green"><?= htmlspecialchars($document['name']) ?></td>
                                <td class="text-nowrap">₱<?= number_format((float) $document['fee'], 2) ?></td>
                                <td class="text-muted-soft"><?= htmlspecialchars($document['requirements'] ?? 'N/A') ?></td>
                                <td class="text-muted-soft text-nowrap"><?= (int) $document['turnaround_days'] ?> business day<?= (int) $document['turnaround_days'] === 1 ? '' : 's' ?></td>
                                <td><span class="status-badge <?= $document['status'] === 'Active' ? 'status-claimed' : 'status-inactive' ?>"><?= htmlspecialchars($document['status']) ?></span></td>
                                <td class="text-end text-nowrap"><button type="button" class="btn btn-sm btn-outline-secondary action-icon" data-edit-document aria-label="Edit <?= htmlspecialchars($document['name']) ?>" title="Edit" data-bs-toggle="tooltip"><i class="bi bi-pencil"></i></button> 
                                <button type="button" class="btn btn-sm btn-outline-secondary action-icon" data-toggle-document aria-label="<?= $document['status'] === 'Active' ? 'Deactivate' : 'Reactivate' ?> <?= htmlspecialchars($document['name']) ?>" title="<?= $document['status'] === 'Active' ? 'Deactivate' : 'Reactivate' ?>" data-bs-toggle="tooltip"><i class="bi <?= $document['status'] === 'Active' ? 'bi-archive' : 'bi-arrow-counterclockwise' ?>"></i></button></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr id="documentEmptyRow" hidden>
                            <td colspan="6" class="empty-state"><i class="bi bi-search"></i>
                                <p class="mb-0 text-muted-soft">No document types match your search or filter.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="modal fade" id="documentModal" tabindex="-1" aria-labelledby="documentModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form id="documentForm" data-admin-form="document" data-mode="create" method="post" action="">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title fw-bold" id="documentModalTitle">Add Document Type</h5>
                                <p class="text-muted-soft small mb-0">Define the service residents can request.</p>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="documentNumber" class="form-label">Document Code</label>
                                <input class="form-control" id="documentNumber" name="document_number" required>
                            </div>
                            <div class="mb-3">
                                <label for="documentName" class="form-label">Document Name</label>
                                <input class="form-control" id="documentName" name="name" required>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-sm-6">
                                    <label for="documentFee" class="form-label">Fee (PHP)</label>
                                    <input type="number" class="form-control" id="documentFee" name="fee" min="0" step="0.01" required>
                                </div>
                                <div class="col-sm-6">
                                    <label for="documentTurnaround" class="form-label">Processing Time</label>
                                    <input type="number" min="0" class="form-control" id="documentTurnaround" name="turnaround" required>
                                </div>
                            </div>
                            <div>
                                <label for="documentRequirements" class="form-label">Requirements</label>
                                <textarea class="form-control" id="documentRequirements" name="requirements" rows="3" placeholder="eg. Valid ID, Proof of Residency" required></textarea>
                                <p class="form-text">List each requirement separated by a comma.</p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-brand">Save Document Type</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="toast-container position-fixed bottom-0 end-0 p-3">
            <div id="adminToast" class="toast app-toast border-0 shadow" role="status">
                <div class="d-flex"><span class="toast-check"><i class="bi bi-check-lg"></i></span><div class="toast-body"></div>
                    <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>
        <div class="modal fade" id="documentConfirmModal" tabindex="-1" aria-labelledby="documentConfirmTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="documentConfirmTitle">Confirm Document Change</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body"><p class="mb-0" id="documentConfirmMessage"></p></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-brand" id="documentConfirmButton">Confirm</button>
                    </div>
                </div>
            </div>
        </div>
    </main>

<script src="../assets/js/admin-pages.js"></script>
<?php include __DIR__ . '/../page-layout/footer.php' ?>
