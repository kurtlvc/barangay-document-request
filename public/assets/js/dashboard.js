/**
 * Barangay Document Request System - Dashboard AJAX Live Fetching
 */

document.addEventListener('DOMContentLoaded', () => {
    const basePath = document.querySelector('meta[name="base-path"]')?.content || '.';
    const refreshBtn = document.getElementById('dashboard-refresh-btn');
    const lastUpdatedEl = document.getElementById('dashboard-last-updated');

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        const div = document.createElement('div');
        div.textContent = String(str);
        return div.innerHTML;
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr.replace(' ', 'T'));
        if (isNaN(date.getTime())) return escapeHtml(dateStr);
        return date.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
    }

    function formatTime(dateObj) {
        return dateObj.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', second: '2-digit' });
    }

    function padZero(num, size = 4) {
        let s = String(num || 0);
        while (s.length < size) s = '0' + s;
        return s;
    }

    function getStatusClass(status) {
        const s = String(status || '').toLowerCase().trim();
        if (s === 'approved' || s === 'ready for release') {
            return 'ready';
        } else if (s === 'pending') {
            return 'pending';
        } else if (s === 'processing') {
            return 'processing';
        } else if (s === 'claimed' || s === 'released') {
            return 'claimed';
        } else if (s === 'rejected' || s === 'cancelled') {
            return 'cancelled';
        }
        return 'inactive';
    }

    function setRefreshingState(isRefreshing) {
        if (!refreshBtn) return;
        const icon = refreshBtn.querySelector('i');
        if (isRefreshing) {
            refreshBtn.disabled = true;
            if (icon) icon.classList.add('spinner-border', 'spinner-border-sm', 'border-0');
        } else {
            refreshBtn.disabled = false;
            if (icon) icon.classList.remove('spinner-border', 'spinner-border-sm', 'border-0');
        }
    }

    async function fetchDashboardData(isManual = false) {
        setRefreshingState(true);

        try {
            const url = `${basePath}/api/dashboard.php`;
            const response = await fetch(url, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.status === 401) {
                // Session expired
                window.location.href = `${basePath}/login.php`;
                return;
            }

            if (!response.ok) {
                throw new Error(`Server returned HTTP ${response.status}`);
            }

            const data = await response.json();
            if (data.status !== 'ok') {
                throw new Error(data.message || 'Failed to fetch dashboard data');
            }

            // 1. Update metric counters
            if (data.stats && typeof data.stats === 'object') {
                for (const [key, value] of Object.entries(data.stats)) {
                    const el = document.querySelector(`[data-stat="${key}"]`);
                    if (el) {
                        el.textContent = Number(value).toLocaleString();
                    }
                }
            }

            // 2. Update role-specific lists/tables
            if (data.role === 'resident') {
                updateResidentRecentRequests(data.recent_requests || []);
            } else if (data.role === 'staff') {
                updateStaffRequestsQueue(data.recent_requests || []);
            } else if (data.role === 'admin') {
                updateAdminActivity(data.recent_requests || []);
            }

            // 3. Update timestamp indicator
            if (lastUpdatedEl) {
                const now = new Date();
                lastUpdatedEl.textContent = `Live data updated at ${formatTime(now)}`;
            }

        } catch (error) {
            console.error('Error fetching dashboard data via AJAX:', error);
            if (lastUpdatedEl && isManual) {
                lastUpdatedEl.textContent = 'Failed to refresh data. Retrying soon...';
            }
        } finally {
            setRefreshingState(false);
        }
    }

    function updateResidentRecentRequests(requests) {
        const container = document.getElementById('resident-recent-container');
        if (!container) return;

        if (!requests || requests.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5" id="resident-requests-empty">
                    <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                    <h6 class="text-muted">No document requests yet</h6>
                    <p class="small text-muted mb-3">Your submitted document requests and status updates will appear here.</p>
                    <a href="${basePath}/resident/new-request.php" class="btn btn-outline-brand">
                        Submit your first request
                    </a>
                </div>
            `;
            return;
        }

        const rowsHtml = requests.map(req => {
            const statusClass = getStatusClass(req.status);
            return `
                <tr>
                    <td class="fw-semibold heading-green">${escapeHtml(req.document_name)}</td>
                    <td class="text-muted-soft">REQ-${padZero(req.request_id)}</td>
                    <td class="text-muted-soft">${formatDate(req.request_date)}</td>
                    <td>
                        <span class="status-badge status-${statusClass}">
                            ${escapeHtml(req.status)}
                        </span>
                    </td>
                </tr>
            `;
        }).join('');

        const cardsHtml = requests.map(req => {
            const statusClass = getStatusClass(req.status);
            return `
                <div class="request-card">
                    <div class="request-card-top">
                        <span class="request-card-title">${escapeHtml(req.document_name)}</span>
                        <span class="status-badge status-${statusClass}">${escapeHtml(req.status)}</span>
                    </div>
                    <div class="request-card-meta">
                        <span>REQ-${padZero(req.request_id)}</span>
                        <span>${formatDate(req.request_date)}</span>
                    </div>
                </div>
            `;
        }).join('');

        container.innerHTML = `
            <div class="table-responsive d-none d-md-block">
                <table class="table table-brand align-middle mb-0" id="resident-requests-list">
                    <thead>
                        <tr>
                            <th>Document Type</th>
                            <th>Reference #</th>
                            <th>Date Requested</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>${rowsHtml}</tbody>
                </table>
            </div>
            <div class="request-card-list d-md-none">${cardsHtml}</div>
        `;
    }

    function updateStaffRequestsQueue(requests) {
        const container = document.getElementById('staff-requests-container');
        if (!container) return;

        if (!requests || requests.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5" id="staff-requests-empty">
                    <i class="bi bi-check2-all fs-1 text-success d-block mb-3"></i>
                    <h6 class="text-muted">Queue is currently clear!</h6>
                    <p class="small text-muted mb-0">There are no pending requests waiting for review.</p>
                </div>
            `;
            return;
        }

        const rowsHtml = requests.map(req => {
            const fullName = `${req.first_name || ''} ${req.last_name || ''}`.trim() || 'Anonymous';
            const statusClass = getStatusClass(req.status);
            return `
                <tr>
                    <td class="fw-semibold heading-green">REQ-${padZero(req.request_id)}</td>
                    <td>
                        <div class="fw-semibold">${escapeHtml(fullName)}</div>
                    </td>
                    <td>${escapeHtml(req.document_name)}</td>
                    <td class="text-muted-soft"><small>${formatDate(req.request_date)}</small></td>
                    <td>
                        <span class="status-badge status-${statusClass}">
                            ${escapeHtml(req.status)}
                        </span>
                    </td>
                </tr>
            `;
        }).join('');

        const tbody = document.getElementById('staff-requests-tbody');
        if (tbody) {
            tbody.innerHTML = rowsHtml;
        } else {
            container.innerHTML = `
                <div class="table-responsive">
                    <table class="table table-brand align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Ref #</th>
                                <th>Resident</th>
                                <th>Document Requested</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="staff-requests-tbody">
                            ${rowsHtml}
                        </tbody>
                    </table>
                </div>
            `;
        }
    }

    function updateAdminActivity(requests) {
        const container = document.getElementById('admin-activity-container');
        if (!container) return;

        if (!requests || requests.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5" id="admin-activity-empty">
                    <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                    <h6 class="text-muted">No request activity recorded yet</h6>
                </div>
            `;
            return;
        }

        const rowsHtml = requests.map(req => {
            const fullName = `${req.first_name || ''} ${req.last_name || ''}`.trim() || 'Anonymous';
            const statusClass = getStatusClass(req.status);
            return `
                <tr>
                    <td class="fw-semibold heading-green">REQ-${padZero(req.request_id)}</td>
                    <td>${escapeHtml(fullName)}</td>
                    <td>${escapeHtml(req.document_name)}</td>
                    <td class="text-muted-soft"><small>${formatDate(req.request_date)}</small></td>
                    <td>
                        <span class="status-badge status-${statusClass}">
                            ${escapeHtml(req.status)}
                        </span>
                    </td>
                </tr>
            `;
        }).join('');

        const tbody = document.getElementById('admin-activity-tbody');
        if (tbody) {
            tbody.innerHTML = rowsHtml;
        } else {
            container.innerHTML = `
                <div class="table-responsive">
                    <table class="table table-brand align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Ref #</th>
                                <th>Resident Name</th>
                                <th>Document Requested</th>
                                <th>Date Submitted</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="admin-activity-tbody">
                            ${rowsHtml}
                        </tbody>
                    </table>
                </div>
            `;
        }
    }

    // Refresh button event listener
    if (refreshBtn) {
        refreshBtn.addEventListener('click', (e) => {
            e.preventDefault();
            fetchDashboardData(true);
        });
    }

    // Initial AJAX fetch on load
    fetchDashboardData(false);

    // Auto-refresh every 30 seconds if tab is active
    const autoRefreshInterval = setInterval(() => {
        if (!document.hidden) {
            fetchDashboardData(false);
        }
    }, 30000);

    window.addEventListener('beforeunload', () => {
        clearInterval(autoRefreshInterval);
    });
});
