document.addEventListener('DOMContentLoaded', function () {
    const form           = document.getElementById('newRequestForm');
    if (!form) return;

    const documentSelect = document.getElementById('documentType');
    const purposeInput    = document.getElementById('purpose');
    const submitBtn       = document.getElementById('submitRequestBtn');
    const helperText      = document.getElementById('formHelperText');

    // Document types are embedded by residents/new-request.php as JSON.
    const documentTypes = window.documentTypesData || {};

    const detailBadge        = document.getElementById('detailBadge');
    const detailTitle        = document.getElementById('detailTitle');
    const detailFee          = document.getElementById('detailFee');
    const detailProcessing   = document.getElementById('detailProcessing');
    const detailRequirements = document.getElementById('detailRequirements');
    const detailPanelEmpty   = document.getElementById('detailPanelEmpty');
    const detailPanelFilled  = document.getElementById('detailPanelFilled');

    function renderDocumentDetails(key) {
        const doc = documentTypes[key];

        if (!doc) {
            detailPanelFilled.classList.add('d-none');
            detailPanelEmpty.classList.remove('d-none');
            return;
        }

        detailPanelEmpty.classList.add('d-none');
        detailPanelFilled.classList.remove('d-none');

        detailBadge.textContent = 'DOCUMENT DETAILS';
        detailTitle.textContent = doc.document_name;
        detailFee.textContent = Number(doc.fee) > 0 ? ('₱' + Number(doc.fee).toFixed(2)) : 'Free';
        detailProcessing.textContent = doc.processing_days + (Number(doc.processing_days) === 1 ? ' business day' : ' business days');

        detailRequirements.innerHTML = '';
        (doc.requirements || '').split(',').map(item => item.trim()).filter(Boolean).forEach(function (req) {
            const li = document.createElement('div');
            li.className = 'fw-bold heading-green';
            li.textContent = req;
            detailRequirements.appendChild(li);
        });
    }

    function currentValidationMessage() {
        if (!documentSelect.value) {
            return 'Choose a type of document to continue';
        }
        if (!purposeInput.value.trim()) {
            return 'Indicate the purpose to continue';
        }
        return null;
    }

    function updateFormState() {
        const message = currentValidationMessage();

        if (message) {
            submitBtn.disabled = true;
            helperText.textContent = message;
            helperText.classList.remove('d-none');
        } else {
            submitBtn.disabled = false;
            helperText.classList.add('d-none');
        }
    }

    documentSelect.addEventListener('change', function () {
        renderDocumentDetails(this.value);
        updateFormState();
    });

    purposeInput.addEventListener('input', updateFormState);

    // Initial state on page load
    renderDocumentDetails(documentSelect.value);
    updateFormState();

    // ---- Submission ----
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        if (submitBtn.disabled) return;

        submitBtn.disabled = true;
        try {
            const response = await fetch('../api/requests.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''},
                body: new URLSearchParams({document_id: documentSelect.value, purpose: purposeInput.value.trim()})
            });
            const result = await response.json();
            if (!response.ok || result.status !== 'ok') throw new Error(result.message || 'Could not submit the request.');
            showSubmittedToast(documentTypes[documentSelect.value]?.document_name || 'Document');
            setTimeout(() => { window.location.href = form.dataset.redirectUrl || 'my-requests.php'; }, 1500);
        } catch (error) {
            helperText.textContent = error.message;
            helperText.classList.remove('d-none');
            submitBtn.disabled = false;
        }
    });

    function showSubmittedToast(documentLabel) {
        const toastEl = document.getElementById('requestSubmittedToast');
        if (!toastEl) return;

        document.getElementById('toastDocumentLabel').textContent = documentLabel;

        const toast = new bootstrap.Toast(toastEl, { autohide: false });
        toast.show();
    }
});
