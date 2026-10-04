<?php
require_once __DIR__ . '/../includes/page-guard.php';
require_once __DIR__ . '/../config/database.php';
bdr_require_page_role(['resident']);
$pageTitle = "Submit New Requests";
$pageDescription = "Need a document? Manage your request form here";
$role = $_SESSION['role'] ?? 'resident';
$name = $_SESSION['name'] ?? 'Resident';
$basePath = '..';

$documentTypes = $pdo->query('SELECT document_id, document_name, fee, processing_days, requirements FROM document_types WHERE is_active = 1 ORDER BY document_name')->fetchAll();
?>

<?php include __DIR__ . '/../page-layout/header.php' ?>
    <main class="resident-content flex-grow-1 overflow-auto p-3 p-lg-4">
        <div class="row g-4 mx-auto">
    <div class="col-lg-7">
        <div class="panel request-form-panel p-4 p-lg-5">
            <span class="eyebrow-badge p-1 rounded">NEW REQUEST</span>
            <h3 class="heading-green fw-bold mt-3 mb-4">Request Details</h3>

            <form id="newRequestForm" data-redirect-url="my-requests.php">

                <div class="mb-4">
                    <label for="documentType" class="form-label">
                        Document Type <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="documentType" name="document_type">
                        <option value="" selected disabled>Select a document type</option>
                        <?php foreach ($documentTypes as $doc): ?>
                            <option value="<?= (int) $doc['document_id'] ?>"><?= htmlspecialchars($doc['document_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="purpose" class="form-label">
                        Purpose of Request <span class="text-danger">*</span>
                    </label>
                    <textarea class="form-control" id="purpose" name="purpose" rows="4" placeholder="e.g. Job Application"></textarea>
                </div>

                <p id="formHelperText" class="helper-text mb-3">Choose a type of document to continue</p>

                <button type="submit" id="submitRequestBtn" class="btn btn-brand" disabled>
                    Submit Request
                </button>
            </form>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="panel document-details-panel p-4 p-lg-5">

            <div id="detailPanelEmpty" class="empty-state">
                <i class="bi bi-file-earmark-text"></i>
                <p class="text-muted-soft mt-3 mb-0">Select a document type to see its fee, processing time, and requirements.</p>
            </div>

            <div id="detailPanelFilled" class="d-none">
                <span class="eyebrow-badge p-1 rounded" id="detailBadge">DOCUMENT DETAILS</span>
                <h3 class="heading-green fw-bold mt-3 mb-4" id="detailTitle">&nbsp;</h3>

                <p class="text-muted-soft mb-1">Fee</p>
                <p class="fw-bold fs-5 mb-4" style="color: var(--gold);" id="detailFee">—</p>

                <p class="text-muted-soft mb-1">Processing</p>
                <p class="fw-bold heading-green mb-4" id="detailProcessing">—</p>

                <p class="text-muted-soft mb-2">Requirements:</p>
                <div id="detailRequirements" class="mb-4"></div>

                <div class="panel p-3 d-flex gap-3 align-items-start" style="background-color: #e3e9e5;">
                    <i class="bi bi-info-circle heading-green fs-5"></i>
                    <p class="mb-0 small text-muted-soft">
                        Please wait for your request to be approved and bring the requirements when claiming the document.
                    </p>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Toast: Request Submitted -->
<div class="toast-container position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 1080;">
    <div id="requestSubmittedToast" class="toast request-toast bg-white" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex align-items-start gap-3 p-3">
            <div class="toast-check">
                <i class="bi bi-check-lg"></i>
            </div>
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-start">
                    <p class="fw-bold heading-green mb-1">Request Submitted</p>
                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <p class="text-muted-soft mb-2 small">
                    <span id="toastDocumentLabel">Document</span> — status <span class="fw-bold heading-green">Pending</span>
                </p>
                <p class="text-muted-soft mb-2 small">Redirecting to My Requests...</p>
            </div>
        </div>
    </div>
</div>

<script>
    window.documentTypesData = <?= json_encode(array_column($documentTypes, null, 'document_id'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<script src="../assets/js/new-request.js"></script>
    </main>
<?php include __DIR__ . '/../page-layout/footer.php' ?>
