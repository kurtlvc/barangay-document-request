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

    function getBadgeClass(status) {
        const s = String(status || '').toLowerCase().trim();
        if (s === 'approved' || s === 'ready for release') {
            return 'bg-success-subtle text-success';
        } else if (s === 'pending') {
            return 'bg-warning-subtle text-warning';
        } else if (s === 'claimed' || s === 'released') {
            return 'bg-info-subtle text-info';
        } else if (s === 'rejected') {
            return 'bg-danger-subtle text-danger';
        }
        return 'bg-secondary-subtle text-secondary';
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
                    <a href="${basePath}/testing-frontend/user-page/residents/new-request.php" class="btn btn-sm btn-outline-primary">
                        Submit your first request
                    </a>
                </div>
            `;
            return;
        }

        const itemsHtml = requests.map(req => {
            const badgeClass = getBadgeClass(req.status);
            return `
                <div class="list-group-item d-flex flex-column flex-sm-row justify-content-between align-items-sm-center px-0 py-3">
                    <div class="d-flex align-items-center mb-2 mb-sm-0">
                        <div class="rounded p-2 bg-light border me-3">
                            <i class="bi bi-file-earmark-text-fill fs-4 text-primary"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-semibold">${escapeHtml(req.document_name)}</h6>
                            <small class="text-muted">
                                Requested on ${formatDate(req.request_date)} &bull; Ref #REQ-${padZero(req.request_id)}
                            </small>
                        </div>
                    </div>
                    <div>
                        <span class="badge rounded-pill px-3 py-2 ${badgeClass}">
                            ${escapeHtml(req.status)}
                        </span>
                    </div>
                </div>
            `;
        }).join('');

        container.innerHTML = `
            <div class="list-group list-group-flush" id="resident-requests-list">
                ${itemsHtml}
            </div>
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
            const badgeClass = getBadgeClass(req.status);
            return `
                <tr>
                    <td class="fw-semibold text-muted">REQ-${padZero(req.request_id)}</td>
                    <td>
                        <div class="fw-semibold">${escapeHtml(fullName)}</div>
                    </td>
                    <td>${escapeHtml(req.document_name)}</td>
                    <td><small>${escapeHtml(req.contact_number || 'N/A')}</small></td>
                    <td><small>${formatDate(req.request_date)}</small></td>
                    <td>
                        <span class="badge rounded-pill px-3 py-2 ${badgeClass}">
                            ${escapeHtml(req.status)}
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="${basePath}/testing-frontend/user-page/staffs/process-requests.php" class="btn btn-sm btn-outline-dark">
                            Process
                        </a>
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
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Ref #</th>
                                <th>Resident</th>
                                <th>Document Requested</th>
                                <th>Contact</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
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
            const badgeClass = getBadgeClass(req.status);
            return `
                <tr>
                    <td class="fw-semibold text-muted">REQ-${padZero(req.request_id)}</td>
                    <td>${escapeHtml(fullName)}</td>
                    <td>${escapeHtml(req.document_name)}</td>
                    <td><small>${formatDate(req.request_date)}</small></td>
                    <td>
                        <span class="badge rounded-pill px-3 py-2 ${badgeClass}">
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
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
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
