document.addEventListener('DOMContentLoaded', function () {

    /* ---------------- Process requests: search + status filter ---------------- */
    var searchInput = document.getElementById('reqSearch');
    var statusSelect = document.getElementById('reqStatusFilter');
    var rows = document.querySelectorAll('#requestRows tr[data-status]');

    function applyFilters() {
        if (!rows.length) return;
        var q = (searchInput && searchInput.value ? searchInput.value : '').trim().toLowerCase();
        var status = statusSelect && statusSelect.value ? statusSelect.value : 'all';
        var visible = 0;
        rows.forEach(function (row) {
            var text = row.dataset.search || '';
            var rowStatus = row.dataset.status || '';
            var show = (!q || text.indexOf(q) !== -1) && (status === 'all' || rowStatus === status);
            row.hidden = !show;
            if (show) visible++;
        });
        var emptyRow = document.getElementById('noResultsRow');
        if (emptyRow) emptyRow.hidden = visible > 0;
        var count = document.getElementById('requestCount');
        if (count) count.textContent = visible + ' request' + (visible === 1 ? '' : 's');
    }
    if (searchInput) searchInput.addEventListener('input', applyFilters);
    if (statusSelect) statusSelect.addEventListener('change', applyFilters);
    applyFilters();

    /* ---------------- Release management: confirm + release flow ---------------- */
    var confirmModalEl = document.getElementById('confirmReleaseModal');
    var confirmModal = confirmModalEl && window.bootstrap ? new bootstrap.Modal(confirmModalEl) : null;
    var confirmLabelEl = document.getElementById('confirmReleaseLabel');
    var confirmBtn = document.getElementById('confirmReleaseBtn');
    var pendingRow = null;

    document.querySelectorAll('.mark-claimed-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            pendingRow = btn.closest('tr');
            if (!pendingRow || !confirmModal) return;
            if (confirmLabelEl) {
                confirmLabelEl.textContent = pendingRow.dataset.doc + ' for ' + pendingRow.dataset.name;
            }
            confirmModal.show();
        });
    });

    if (confirmBtn) {
        confirmBtn.addEventListener('click', function () {
            if (!pendingRow) return;
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            fetch('../api/requests.php', {method: 'PATCH', credentials: 'same-origin', headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrf}, body: JSON.stringify({request_id: pendingRow.dataset.requestId, status: 'Released'})})
                .then(response => response.json().then(data => { if (!response.ok || data.status !== 'ok') throw new Error(data.message || 'Could not save the release.'); return data; }))
                .then(() => finishRelease())
                .catch(error => window.alert(error.message));
        });

        function finishRelease() {
            if (!pendingRow) return;

            var name = pendingRow.dataset.name || '';
            var doc = pendingRow.dataset.doc || '';
            var ref = pendingRow.dataset.ref || '';
            var claimedBody = document.getElementById('recentlyClaimedBody');
            var readyBody = document.getElementById('readyForReleaseBody');

            if (claimedBody) {
                var now = new Date();
                var time = now.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
                var tr = document.createElement('tr');
                tr.innerHTML =
                    '<td class="fw-semibold heading-green">' + escapeHtml(name) + '<br><small class="text-muted-soft fw-normal">' + escapeHtml(ref) + '</small></td>' +
                    '<td class="fw-semibold heading-green">' + escapeHtml(doc) +'</td>' +
                    '<td class="text-muted-soft">Today, ' + time + '</td>' +
                    '<td class="text-end"><span class="status-badge status-claimed"><i class="bi bi-check-circle-fill"></i> Claimed</span></td>';
                claimedBody.prepend(tr);
                showReleasedToast(name, doc, time);
            }

            pendingRow.remove();
            if (readyBody && !readyBody.querySelector('tr')) {
                readyBody.innerHTML = '<tr><td colspan="2" class="empty-state"><i class="bi bi-inbox"></i><p class="empty-text mb-0">Nothing waiting for release right now.</p></td></tr>';
            }
            updateSidebarBadge(readyBody);

            if (confirmModal) confirmModal.hide();
            pendingRow = null;
        }
    }

    function updateSidebarBadge(readyBody) {
        var countEl = document.getElementById('readyForReleaseCount');
        if (countEl && readyBody) countEl.textContent = readyBody.querySelectorAll('tr[data-name]').length;
    }

    function showReleasedToast(name, doc, time) {
        var toastEl = document.getElementById('releasedToast');
        if (!toastEl || !window.bootstrap) return;
        var body = document.getElementById('releasedToastBody');
        if (body) body.textContent = doc + ' for ' + name + ' is now marked Claimed, logged under your name at ' + time + '.';
        new bootstrap.Toast(toastEl, { delay: 5000 }).show();
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    /* ---------------- Request details: progress workflow ---------------- */
    var requestAction = document.getElementById('requestAction');
    var requestTimeline = document.getElementById('requestTimeline');
    var requestStatus = document.getElementById('requestStatus');
    var workflowMessage = document.getElementById('workflowMessage');
    var workflowStages = ['pending', 'processing', 'ready', 'claimed'];
    var workflowIcons = ['bi-hourglass-split', 'bi-gear-fill', 'bi-patch-check-fill', 'bi-box-seam-fill'];
    var workflowLabels = {
        pending: 'Pending',
        processing: 'Processing',
        ready: 'Ready for Release',
        claimed: 'Released'
    };
    var nextActions = {
        processing: { label: 'Approve Request', icon: 'bi-check-lg' }
    };

    async function advanceRequestWorkflow(status) {
        if (!requestAction || !requestTimeline || !requestStatus) return;
        const requestId = requestTimeline.dataset.requestId;
        const apiStatus = status === 'ready' ? 'Ready for Release' : (status === 'claimed' ? 'Released' : status.charAt(0).toUpperCase() + status.slice(1));
        try {
            const response = await fetch('../api/requests.php', {method: 'PATCH', credentials: 'same-origin', headers: {'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''}, body: JSON.stringify({request_id: requestId, status: apiStatus})});
            const data = await response.json();
            if (!response.ok || data.status !== 'ok') throw new Error(data.message || 'Could not update the request.');
        } catch (error) { if (workflowMessage) workflowMessage.textContent = error.message; return; }
        var stage = workflowStages.indexOf(status);
        if (stage < 0) return;

        requestStatus.className = 'status-badge status-' + status;
        requestStatus.textContent = workflowLabels[status];
        requestTimeline.dataset.stage = String(stage);

        requestTimeline.querySelectorAll('[data-step]').forEach(function (step) {
            var stepIndex = Number(step.dataset.step);
            var complete = stepIndex < stage;
            step.classList.toggle('is-complete', complete);
            step.classList.toggle('is-current', stepIndex === stage);
            if (stepIndex === stage) {
                step.setAttribute('aria-current', 'step');
            } else {
                step.removeAttribute('aria-current');
            }
            var circle = step.querySelector('.step-circle');
            if (circle) {
                circle.replaceChildren();
                if (complete || stepIndex === stage) {
                    var check = document.createElement('i');
                    check.className = 'bi ' + (complete ? 'bi-check-lg' : workflowIcons[stage]);
                    check.setAttribute('aria-hidden', 'true');
                    circle.append(check);
                }
            }
            var date = step.querySelector('[data-step-date]');
            if (date && stepIndex > 0) {
                date.textContent = stepIndex <= stage ? 'Updated just now' : 'Awaiting update';
            }
        });

        var nextStatus = workflowStages[stage + 1];
        var action = nextActions[status];
        if (nextStatus && action) {
            requestAction.dataset.nextStatus = nextStatus;
            var icon = document.createElement('i');
            icon.className = 'bi ' + action.icon + ' me-1';
            icon.setAttribute('aria-hidden', 'true');
            requestAction.replaceChildren(icon, document.createTextNode(action.label));
            workflowMessage.textContent = 'Next: ' + action.label;
        } else {
            requestAction.remove();
            if (status === 'ready') {
                workflowMessage.innerHTML = 'Ready for release. <a href="release-management.php">Open Release Management</a>.';
            } else {
                workflowMessage.textContent = 'This request has been released.';
            }
        }
    }

    if (requestAction) {
        requestAction.addEventListener('click', function () {
            var nextStatus = requestAction.dataset.nextStatus;
            advanceRequestWorkflow(nextStatus);
        });
    }
});
