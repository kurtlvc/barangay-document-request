document.addEventListener('DOMContentLoaded', () => {
    const toastElement = document.getElementById('adminToast');
    async function apiRequest(path, method = 'GET', payload = null) {
        const options = {method, credentials: 'same-origin', headers: {'Accept': 'application/json'}};
        if (payload !== null) {
            options.headers['Content-Type'] = 'application/json';
            options.headers['X-CSRF-Token'] = document.querySelector('meta[name="csrf-token"]')?.content || '';
            options.body = JSON.stringify(payload);
        }
        const response = await fetch(path, options);
        const result = await response.json();
        if (!response.ok || result.status !== 'ok') throw new Error(result.message || 'The request could not be saved.');
        return result;
    }

    function showAdminToast(message, type = 'success') {
        if (!toastElement || !window.bootstrap) return;
        toastElement.classList.remove('toast-success', 'toast-danger');
        toastElement.classList.add(`toast-${type}`);
        const toastIcon = toastElement.querySelector('.toast-check i');
        if (toastIcon) {
            toastIcon.className = `bi ${type === 'danger' ? 'bi-exclamation-circle-fill' : type === 'info' ? 'bi-info-circle-fill' : 'bi-check-lg'}`;
        }
        toastElement.querySelector('.toast-body').textContent = message;
        bootstrap.Toast.getOrCreateInstance(toastElement, { delay: 4500 }).show();
    }

    function makeCell(content, className = '') {
        const cell = document.createElement('td');
        cell.className = className;
        cell.textContent = content;
        return cell;
    }

    function makeStatusBadge(label, style) {
        const badge = document.createElement('span');
        badge.className = `status-badge ${style}`;
        badge.textContent = label;
        return badge;
    }

    function makeActionButton(icon, label, action) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-sm btn-outline-secondary action-icon';
        button.setAttribute(action, '');
        button.setAttribute('aria-label', label);
        button.setAttribute('title', label.split(' ')[0]);
        button.setAttribute('data-bs-toggle', 'tooltip');
        const iconElement = document.createElement('i');
        iconElement.className = `bi ${icon}`;
        button.append(iconElement);
        if (window.bootstrap) new bootstrap.Tooltip(button);
        return button;
    }

    function makeIdentityCell(name, detail) {
        const cell = document.createElement('td');
        cell.className = 'fw-semibold heading-green';
        const nameElement = document.createElement('span');
        nameElement.textContent = name;
        const detailElement = document.createElement('small');
        detailElement.className = 'text-muted-soft fw-normal d-block';
        detailElement.textContent = detail;
        cell.append(nameElement, detailElement);
        return cell;
    }

    function setupAccountManagement() {
        const rows = document.getElementById('accountRows');
        const modalElement = document.getElementById('accountModal');
        const form = document.getElementById('accountForm');
        const confirmModalElement = document.getElementById('accountConfirmModal');
        if (!rows || !modalElement || !form || !confirmModalElement) return;

        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        const confirmModal = bootstrap.Modal.getOrCreateInstance(confirmModalElement);
        const confirmTitle = document.getElementById('accountConfirmTitle');
        const confirmMessage = document.getElementById('accountConfirmMessage');
        const confirmButton = document.getElementById('accountConfirmButton');
        const nameInput = document.getElementById('accountName');
        const emailInput = document.getElementById('accountEmail');
        const roleInput = document.getElementById('accountRole');
        const contactInput = document.getElementById('accountContact');
        const passwordInput = document.getElementById('accountPassword');
        let pendingAccountAction = null;

        function renderAccountRow(values, existingRow = null) {
            const row = existingRow || document.createElement('tr');
            if (values.id) row.dataset.userId = values.id;
            row.dataset.name = values.name;
            row.dataset.email = values.email;
            row.dataset.contact = values.contact;
            row.dataset.role = values.role.toLowerCase();
            row.dataset.status = values.status.toLowerCase();
            row.dataset.search = `${values.name} ${values.email}`.toLowerCase();
            row.replaceChildren();
            row.append(makeIdentityCell(values.name, values.email));
            const roleCell = makeCell('');
            const roleBadge = document.createElement('span');
            roleBadge.className = 'badge text-bg-light border';
            roleBadge.textContent = values.role;
            roleCell.append(roleBadge);
            row.append(roleCell, makeCell(values.contact, 'text-muted-soft text-nowrap'));
            row.append(makeCell(''));
            row.cells[3].append(makeStatusBadge(values.status, values.status === 'Active' ? 'status-claimed' : 'status-inactive'));
            const actions = makeCell('', 'text-end text-nowrap');
            actions.append(
                makeActionButton('bi-pencil', `Edit ${values.name}`, 'data-edit-account'),
                document.createTextNode(' '),
                makeActionButton(values.status === 'Active' ? 'bi-person-dash' : 'bi-person-check', `${values.status === 'Active' ? 'Deactivate' : 'Reactivate'} ${values.name}`, 'data-toggle-account'),
                document.createTextNode(' '),
                makeActionButton('bi-trash', `Delete ${values.name}`, 'data-delete-account')
            );
            row.append(actions);
            return row;
        }

        function filterAccounts() {
            const query = (document.getElementById('accountSearch')?.value || '').trim().toLowerCase();
            const role = document.querySelector('#accountRoleFilters [data-account-role].active')?.dataset.accountRole || 'all';
            const status = document.getElementById('accountStatusFilter')?.value || 'all';
            let visible = 0;
            rows.querySelectorAll('tr[data-name]').forEach(row => {
                const matches = row.dataset.search.includes(query)
                    && (role === 'all' || row.dataset.role === role)
                    && (status === 'all' || row.dataset.status === status);
                row.hidden = !matches;
                if (matches) visible += 1;
            });
            const emptyRow = document.getElementById('accountEmptyRow');
            if (emptyRow) emptyRow.hidden = visible > 0;
        }

        modalElement.addEventListener('show.bs.modal', event => {
            if (event.relatedTarget?.hasAttribute('data-bs-target')) {
                form.reset();
                form.classList.remove('was-validated');
                form.dataset.mode = 'create';
                form._editingRow = null;
                document.getElementById('accountModalTitle').textContent = 'Add Account';
                form.querySelector('[type="submit"]').textContent = 'Create Account';
                passwordInput.required = true;
            }
        });

        modalElement.addEventListener('hidden.bs.modal', () => {
            form.reset();
            form.classList.remove('was-validated');
            form.dataset.mode = 'create';
            form._editingRow = null;
        });

        form.addEventListener('submit', async event => {
            event.preventDefault();
            form.classList.add('was-validated');
            if (!form.reportValidity()) {
                showAdminToast('Please complete the required account details.', 'danger');
                return;
            }
            const values = {
                id: form._editingRow?.dataset.userId || null,
                name: nameInput.value.trim(),
                email: emailInput.value.trim(),
                role: roleInput.value,
                contact: contactInput.value.trim(),
                status: 'Active',
            };
            const duplicate = [...rows.querySelectorAll('tr[data-name]')].some(row =>
                row !== form._editingRow && row.dataset.email.toLowerCase() === values.email.toLowerCase()
            );
            if (duplicate) {
                showAdminToast('That email address is already assigned to an account.', 'danger');
                return;
            }
            try {
                const payload = {name: values.name, email: values.email, role: values.role, contact_number: values.contact, password: passwordInput.value};
                const saved = form.dataset.mode === 'edit' && form._editingRow
                    ? await apiRequest('../api/users.php', 'PATCH', {...payload, user_id: values.id})
                    : await apiRequest('../api/users.php', 'POST', payload);
                if (saved.user_id) values.id = saved.user_id;
            } catch (error) { showAdminToast(error.message, 'danger'); return; }
            values.role = values.role === 'admin' ? 'Admin' : 'Staff';
            if (form.dataset.mode === 'edit' && form._editingRow) {
                values.status = form._editingRow.dataset.status === 'active' ? 'Active' : 'Deactivated';
                renderAccountRow(values, form._editingRow);
                showAdminToast(`${values.name}'s account details were updated.`);
            } else {
                rows.insertBefore(renderAccountRow(values), document.getElementById('accountEmptyRow'));
                showAdminToast(`${values.role} account for ${values.name} was created successfully.`);
            }
            filterAccounts();
            modal.hide();
        });

        rows.addEventListener('click', event => {
            const button = event.target.closest('button');
            const row = button?.closest('tr[data-name]');
            if (!row) return;
            if (button.hasAttribute('data-edit-account')) {
                form.dataset.mode = 'edit';
                form._editingRow = row;
                nameInput.value = row.dataset.name;
                emailInput.value = row.dataset.email;
                roleInput.value = row.dataset.role;
                contactInput.value = row.dataset.contact;
                passwordInput.required = false;
                document.getElementById('accountModalTitle').textContent = 'Edit Account';
                form.querySelector('[type="submit"]').textContent = 'Save Changes';
                modal.show();
            } else if (button.hasAttribute('data-toggle-account')) {
                const nextStatus = row.dataset.status === 'active' ? 'Deactivated' : 'Active';
                pendingAccountAction = { row, type: 'status', nextStatus };
                confirmTitle.textContent = `${nextStatus === 'Active' ? 'Reactivate' : 'Deactivate'} Account`;
                confirmMessage.textContent = `Are you sure you want to ${nextStatus === 'Active' ? 'reactivate' : 'deactivate'} ${row.dataset.name}'s account?`;
                confirmButton.textContent = `${nextStatus === 'Active' ? 'Reactivate' : 'Deactivate'} Account`; 
                confirmButton.className = 'btn btn-brand';
                confirmModal.show();
            } else if (button.hasAttribute('data-delete-account')) {
                pendingAccountAction = { row, type: 'delete' };
                confirmTitle.textContent = 'Delete Account Permanently';
                confirmMessage.textContent = `Are you sure to permanently delete ${row.dataset.name}'s account? This action cannot be undone.`;
                confirmButton.textContent = 'Yes, Delete Permanently';
                confirmButton.className = 'btn btn-danger';
                confirmModal.show();
            }
        });

        confirmButton.addEventListener('click', async () => {
            if (!pendingAccountAction) return;
            const { row, type, nextStatus } = pendingAccountAction;
            const accountName = row.dataset.name;
            try {
                if (type === 'delete') await apiRequest(`../api/users.php?user_id=${encodeURIComponent(row.dataset.userId)}`, 'DELETE', {});
                else await apiRequest('../api/users.php', 'PATCH', {action: 'set-active', user_id: row.dataset.userId, is_active: nextStatus === 'Active'});
            } catch (error) { showAdminToast(error.message, 'danger'); return; }
            if (type === 'delete') {
                row.remove();
                showAdminToast(`${accountName}'s account was permanently deleted.`);
            } else {
                renderAccountRow({ name: accountName, email: row.dataset.email, role: row.dataset.role === 'admin' ? 'Admin' : 'Staff', contact: row.dataset.contact, status: nextStatus }, row);
                showAdminToast(`${accountName}'s account is now ${nextStatus.toLowerCase()}.`);
            }
            pendingAccountAction = null;
            filterAccounts();
            confirmModal.hide();
        });

        confirmModalElement.addEventListener('hidden.bs.modal', () => { pendingAccountAction = null; });

        document.getElementById('accountRoleFilters')?.addEventListener('click', event => {
            const button = event.target.closest('[data-account-role]');
            if (!button) return;
            document.querySelectorAll('#accountRoleFilters [data-account-role]').forEach(option => {
                const selected = option === button;
                option.classList.toggle('active', selected);
                option.setAttribute('aria-pressed', String(selected));
            });
            filterAccounts();
        });

        ['accountSearch', 'accountStatusFilter'].forEach(id => {
            document.getElementById(id)?.addEventListener('input', filterAccounts);
            document.getElementById(id)?.addEventListener('change', filterAccounts);
        });
    }

    function setupResidentRecords() {
        const rows = document.getElementById('residentRows');
        const modalElement = document.getElementById('residentModal');
        const form = document.getElementById('residentForm');
        const verifyModalElement = document.getElementById('verifyResidentModal');
        if (!rows || !modalElement || !form || !verifyModalElement) return;

        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        const verifyModal = bootstrap.Modal.getOrCreateInstance(verifyModalElement);
        const verifyResidentName = document.getElementById('verifyResidentName');
        const confirmVerifyButton = document.getElementById('confirmVerifyResident');
        const nameInput = document.getElementById('residentName');
        const emailInput = document.getElementById('residentEmail');
        const contactInput = document.getElementById('residentContact');
        const addressInput = document.getElementById('residentAddress');
        let currentPage = 1;
        let pendingVerificationRow = null;

        function filterResidents() {
            const query = (document.getElementById('residentSearch')?.value || '').trim().toLowerCase();
            const status = document.querySelector('#residentStatusFilters [data-resident-status].active')?.dataset.residentStatus || 'all';
            const pageSize = Number(document.getElementById('residentPageSize')?.value || 5);
            const matches = [...rows.querySelectorAll('tr[data-name]')]
                .filter(row => row.dataset.search.includes(query)
                    && (status === 'all' || row.dataset.status === status));
            const totalPages = Math.max(1, Math.ceil(matches.length / pageSize));
            currentPage = Math.min(currentPage, totalPages);
            const start = (currentPage - 1) * pageSize;
            rows.querySelectorAll('tr[data-name]').forEach(row => { row.hidden = true; });
            matches.slice(start, start + pageSize).forEach(row => { row.hidden = false; });
            const emptyRow = document.getElementById('residentEmptyRow');
            if (emptyRow) emptyRow.hidden = matches.length > 0;
            document.getElementById('residentCount').textContent = `${matches.length} resident${matches.length === 1 ? '' : 's'}`;
            document.getElementById('residentPageInfo').textContent = matches.length ? `Showing ${start + 1}–${Math.min(start + pageSize, matches.length)} of ${matches.length}` : 'No records to display';
            document.getElementById('residentPrev').disabled = currentPage <= 1;
            document.getElementById('residentNext').disabled = currentPage >= totalPages;
        }

        rows.addEventListener('click', event => {
            const verifyButton = event.target.closest('[data-verify-resident]');
            const verifyRow = verifyButton?.closest('tr[data-name]');
            if (verifyRow && verifyRow.dataset.status === 'unverified') {
                pendingVerificationRow = verifyRow;
                verifyResidentName.textContent = verifyRow.dataset.name;
                verifyModal.show();
                return;
            }

            const button = event.target.closest('[data-edit-resident]');
            const row = button?.closest('tr[data-name]');
            if (!row) return;
            form._editingRow = row;
            nameInput.value = row.dataset.name;
            emailInput.value = row.dataset.email;
            contactInput.value = row.dataset.contact;
            addressInput.value = row.dataset.address;
            modal.show();
        });

        confirmVerifyButton.addEventListener('click', async () => {
            if (!pendingVerificationRow || pendingVerificationRow.dataset.status !== 'unverified') return;
            const residentName = pendingVerificationRow.dataset.name;
            try { await apiRequest('../api/residents.php', 'PATCH', {action: 'set-status', resident_id: pendingVerificationRow.dataset.residentId, status: 'verified'}); }
            catch (error) { showAdminToast(error.message, 'danger'); return; }
            pendingVerificationRow.dataset.status = 'verified';
            pendingVerificationRow.cells[4].replaceChildren(makeStatusBadge('Verified', 'status-claimed'));
            pendingVerificationRow.querySelector('[data-verify-resident]')?.remove();
            pendingVerificationRow = null;
            filterResidents();
            verifyModal.hide();
            showAdminToast(`${residentName}'s account was verified.`);
        });
        verifyModalElement.addEventListener('hidden.bs.modal', () => { pendingVerificationRow = null; });

        form.addEventListener('submit', async event => {
            event.preventDefault();
            if (!form.reportValidity() || !form._editingRow) return;
            const row = form._editingRow;
            try {
                await apiRequest('../api/residents.php', 'PATCH', {resident_id: row.dataset.residentId, full_name: nameInput.value.trim(), email: emailInput.value.trim(), contact_number: contactInput.value.trim(), address: addressInput.value.trim()});
            } catch (error) { showAdminToast(error.message, 'danger'); return; }
            row.dataset.name = nameInput.value.trim();
            row.dataset.email = emailInput.value.trim();
            row.dataset.contact = contactInput.value.trim();
            row.dataset.address = addressInput.value.trim();
            row.dataset.search = `${row.dataset.name} ${row.dataset.email} ${row.dataset.address}`.toLowerCase();
            row.cells[0].replaceWith(makeIdentityCell(row.dataset.name, row.dataset.email));
            row.cells[1].textContent = row.dataset.contact;
            row.cells[2].textContent = row.dataset.address;
            filterResidents();
            modal.hide();
            showAdminToast(`${row.dataset.name}'s resident record was updated.`);
        });

        document.getElementById('residentSearch')?.addEventListener('input', () => { currentPage = 1; filterResidents(); });
        document.getElementById('residentStatusFilters')?.addEventListener('click', event => {
            const button = event.target.closest('[data-resident-status]');
            if (!button) return;
            document.querySelectorAll('#residentStatusFilters [data-resident-status]').forEach(option => {
                const selected = option === button;
                option.classList.toggle('active', selected);
                option.setAttribute('aria-pressed', String(selected));
            });
            currentPage = 1;
            filterResidents();
        });
        document.getElementById('residentPageSize')?.addEventListener('change', () => { currentPage = 1; filterResidents(); });
        document.getElementById('residentPrev')?.addEventListener('click', () => { currentPage -= 1; filterResidents(); });
        document.getElementById('residentNext')?.addEventListener('click', () => { currentPage += 1; filterResidents(); });
        filterResidents();
    }

    function setupDocumentTypes() {
        const rows = document.getElementById('documentRows');
        const modalElement = document.getElementById('documentModal');
        const form = document.getElementById('documentForm');
        const confirmModalElement = document.getElementById('documentConfirmModal');
        if (!rows || !modalElement || !form || !confirmModalElement) return;

        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        const confirmModal = bootstrap.Modal.getOrCreateInstance(confirmModalElement);
        const confirmMessage = document.getElementById('documentConfirmMessage');
        const confirmButton = document.getElementById('documentConfirmButton');
        const numberInput = document.getElementById('documentNumber');
        const nameInput = document.getElementById('documentName');
        const feeInput = document.getElementById('documentFee');
        const requirementsInput = document.getElementById('documentRequirements');
        const turnaroundInput = document.getElementById('documentTurnaround');
        let pendingDocumentAction = null;

        function renderDocumentRow(values, existingRow = null) {
            const row = existingRow || document.createElement('tr');
            if (values.id) row.dataset.documentId = values.id;
            if (values.number) row.dataset.number = values.number;
            row.dataset.name = values.name;
            row.dataset.fee = values.fee;
            row.dataset.requirements = values.requirements;
            row.dataset.turnaround = values.turnaround;
            row.dataset.status = values.status.toLowerCase();
            row.dataset.search = `${values.name} ${values.requirements}`.toLowerCase();
            row.replaceChildren();
            row.append(makeCell(values.name, 'fw-semibold heading-green'));
            row.append(makeCell(`₱${Number(values.fee).toFixed(2)}`, 'text-nowrap'));
            row.append(makeCell(values.requirements, 'text-muted-soft'));
            row.append(makeCell(`${values.turnaround} business day${Number(values.turnaround) === 1 ? '' : 's'}`, 'text-muted-soft text-nowrap'));
            const statusCell = makeCell('');
            statusCell.append(makeStatusBadge(values.status, values.status === 'Active' ? 'status-claimed' : 'status-inactive'));
            row.append(statusCell);
            const actions = makeCell('', 'text-end text-nowrap');
            actions.append(
                makeActionButton('bi-pencil', `Edit ${values.name}`, 'data-edit-document'),
                document.createTextNode(' '),
                makeActionButton(values.status === 'Active' ? 'bi-archive' : 'bi-arrow-counterclockwise', `${values.status === 'Active' ? 'Deactivate' : 'Reactivate'} ${values.name}`, 'data-toggle-document')
            );
            row.append(actions);
            return row;
        }

        function filterDocuments() {
            const query = (document.getElementById('documentSearch')?.value || '').trim().toLowerCase();
            const status = document.querySelector('#documentStatusFilters [data-document-status].active')?.dataset.documentStatus || 'all';
            let visible = 0;
            const documentRows = [...rows.querySelectorAll('tr[data-name]')];
            documentRows.forEach(row => {
                const matches = row.dataset.search.includes(query)
                    && (status === 'all' || row.dataset.status === status);
                row.hidden = !matches;
                if (matches) visible += 1;
            });
            const emptyRow = document.getElementById('documentEmptyRow');
            if (emptyRow) emptyRow.hidden = visible > 0;
        }

        modalElement.addEventListener('show.bs.modal', event => {
            if (event.relatedTarget?.hasAttribute('data-bs-target')) {
                form.reset();
                form.classList.remove('was-validated');
                form.dataset.mode = 'create';
                form._editingRow = null;
                document.getElementById('documentModalTitle').textContent = 'Add Document Type';
                form.querySelector('[type="submit"]').textContent = 'Save Document Type';
            }
        });
        modalElement.addEventListener('hidden.bs.modal', () => {
            form.reset();
            form.classList.remove('was-validated');
            form.dataset.mode = 'create';
            form._editingRow = null;
        });

        form.addEventListener('submit', async event => {
            event.preventDefault();
            form.classList.add('was-validated');
            if (!form.reportValidity()) {
                showAdminToast('Please complete the required document details.', 'danger');
                return;
            }
            const values = {
                id: form._editingRow?.dataset.documentId || null,
                number: numberInput.value.trim(),
                name: nameInput.value.trim(),
                fee: Number(feeInput.value),
                requirements: requirementsInput.value.trim(),
                turnaround: Number(turnaroundInput.value),
                status: 'Active',
            };
            const duplicate = [...rows.querySelectorAll('tr[data-name]')].some(row =>
                row !== form._editingRow && row.dataset.name.toLowerCase() === values.name.toLowerCase()
            );
            if (duplicate) {
                showAdminToast('A document type with that name already exists.', 'danger');
                return;
            }
            try {
                const payload = {document_number: values.number, document_name: values.name, fee: values.fee, requirements: values.requirements, processing_days: values.turnaround};
                const saved = form.dataset.mode === 'edit' && form._editingRow
                    ? await apiRequest(`../api/documents.php?document_id=${encodeURIComponent(values.id)}`, 'PUT', payload)
                    : await apiRequest('../api/documents.php', 'POST', payload);
                if (saved.document_id) values.id = saved.document_id;
            } catch (error) { showAdminToast(error.message, 'danger'); return; }
            if (form.dataset.mode === 'edit' && form._editingRow) {
                values.status = form._editingRow.dataset.status === 'active' ? 'Active' : 'Deactivated';
                renderDocumentRow(values, form._editingRow);
                showAdminToast(`${values.name} was updated successfully.`);
            } else {
                rows.insertBefore(renderDocumentRow(values), document.getElementById('documentEmptyRow'));
                showAdminToast(`${values.name} was added to the document catalog.`);
            }
            filterDocuments();
            modal.hide();
        });

        rows.addEventListener('click', event => {
            const button = event.target.closest('button');
            const row = button?.closest('tr[data-name]');
            if (!row) return;
            if (button.hasAttribute('data-edit-document')) {
                form.dataset.mode = 'edit';
                form._editingRow = row;
                numberInput.value = row.dataset.number;
                nameInput.value = row.dataset.name;
                feeInput.value = row.dataset.fee;
                requirementsInput.value = row.dataset.requirements;
                turnaroundInput.value = row.dataset.turnaround;
                document.getElementById('documentModalTitle').textContent = 'Edit Document Type';
                form.querySelector('[type="submit"]').textContent = 'Save Changes';
                modal.show();
            } else if (button.hasAttribute('data-toggle-document')) {
                const nextStatus = row.dataset.status === 'active' ? 'Deactivated' : 'Active';
                pendingDocumentAction = { row, nextStatus };
                confirmMessage.textContent = `Are you sure you want to ${nextStatus === 'Active' ? 'reactivate' : 'deactivate'} ${row.dataset.name}?`;
                confirmButton.textContent = `${nextStatus === 'Active' ? 'Reactivate' : 'Deactivate'} Document Type`;
                confirmModal.show();
            }
        });

        confirmButton.addEventListener('click', async () => {
            if (!pendingDocumentAction) return;
            const { row, nextStatus } = pendingDocumentAction;
            const documentName = row.dataset.name;
            try { await apiRequest('../api/documents.php', 'PATCH', {action: 'set-active', document_id: row.dataset.documentId, is_active: nextStatus === 'Active'}); }
            catch (error) { showAdminToast(error.message, 'danger'); return; }
            renderDocumentRow({ id: row.dataset.documentId, number: row.dataset.number, name: documentName, fee: row.dataset.fee, requirements: row.dataset.requirements, turnaround: row.dataset.turnaround, status: nextStatus }, row);
            showAdminToast(`${documentName} is now ${nextStatus.toLowerCase()}.`);
            pendingDocumentAction = null;
            filterDocuments();
            confirmModal.hide();
        });
        confirmModalElement.addEventListener('hidden.bs.modal', () => { pendingDocumentAction = null; });

        document.getElementById('documentSearch')?.addEventListener('input', filterDocuments);
        document.getElementById('documentStatusFilters')?.addEventListener('click', event => {
            const button = event.target.closest('[data-document-status]');
            if (!button) return;
            document.querySelectorAll('#documentStatusFilters [data-document-status]').forEach(option => {
                const selected = option === button;
                option.classList.toggle('active', selected);
                option.setAttribute('aria-pressed', String(selected));
            });
            filterDocuments();
        });
    }

    function setupReports() {
        const reportType = document.getElementById('reportType');
        const sections = [...document.querySelectorAll('[data-report-section]')];
        if (!reportType || !sections.length) return;
        const metrics = document.getElementById('reportMetrics');
        const fromInput = document.getElementById('reportFrom');
        const toInput = document.getElementById('reportTo');
        const numberFormat = new Intl.NumberFormat('en-PH');
        const averageFormat = new Intl.NumberFormat('en-PH', { maximumFractionDigits: 1 });
        const currencyFormat = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });

        function formatDate(value) {
            if (!value) return '';
            const [year, month, day] = value.split('-').map(Number);
            return new Date(year, month - 1, day).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }

        function setReportView() {
            sections.forEach(section => { section.hidden = section.dataset.reportSection !== reportType.value; });
        }

        function makeMetric(label, value, detail = '') {
            const column = document.createElement('div');
            column.className = 'col-6 col-lg-3';
            const card = document.createElement('div');
            card.className = 'stat-card h-100 px-3 py-3';
            const heading = document.createElement('p');
            heading.className = 'stat-card-header text-secondary mb-2';
            heading.textContent = label;
            const content = document.createElement('div');
            content.className = 'd-flex align-items-end gap-2';
            const amount = document.createElement('p');
            amount.className = 'stat-card-value';
            amount.textContent = value;
            content.append(amount);
            if (detail) {
                const note = document.createElement('p');
                note.className = 'stat-card-meta';
                note.textContent = detail;
                content.append(note);
            }
            card.append(heading, content);
            column.append(card);
            return column;
        }

        function renderMetrics(section, grouped, total, from, to) {
            if (!metrics) return;
            const rows = [...grouped.values()];
            const leading = [...rows].sort((left, right) => right.count - left.count)[0];
            const definitions = {
                requests: [
                    ['Total Requests', numberFormat.format(total), 'in selected period'],
                    ['Monthly Average', averageFormat.format(rows.length ? total / rows.length : 0), 'requests per month'],
                    ['Busiest Month', leading?.category || 'None', leading ? `${numberFormat.format(leading.count)} requests` : 'no requests'],
                    ['Months Covered', numberFormat.format(rows.length), 'calendar months'],
                ],
                documents: [
                    ['Service Requests', numberFormat.format(total), 'across document types'],
                    ['Fees Collected', currencyFormat.format(rows.reduce((sum, item) => sum + item.amount, 0)), 'recorded fees'],
                    ['Top Service', leading?.category || 'None', leading ? `${numberFormat.format(leading.count)} requests` : 'no requests'],
                    ['Services Requested', numberFormat.format(rows.length), 'document types'],
                ],
                residents: [
                    ['New Residents', numberFormat.format(total), 'joined in selected period'],
                    ['Monthly Average', averageFormat.format(rows.length ? total / rows.length : 0), 'new residents per month'],
                    ['Most New Residents', leading?.category || 'None', leading ? `${numberFormat.format(leading.count)} residents` : 'no registrations'],
                    ['Months Covered', numberFormat.format(rows.length), 'calendar months'],
                ],
            }[section.dataset.reportKind];
            const row = document.createElement('div');
            row.className = 'row g-0';
            definitions.forEach(([label, value, detail]) => row.append(makeMetric(label, value, detail)));
            metrics.replaceChildren(row);
        }

        function applyDateRange() {
            const from = fromInput.value;
            const to = toInput.value;
            if (!from || !to || from > to) {
                showAdminToast('Choose a valid date range before generating the report.', 'danger');
                return false;
            }
            const rangeText = `${formatDate(from)} – ${formatDate(to)}`;
            sections.forEach(section => {
                section.querySelectorAll('[data-report-range]').forEach(label => { label.textContent = rangeText; });
                const grouped = new Map();
                let total = 0;
                const monthly = ['requests', 'residents'].includes(section.dataset.reportKind);
                if (monthly) {
                    const [startYear, startMonth] = from.split('-').map(Number);
                    const [endYear, endMonth] = to.split('-').map(Number);
                    const cursor = new Date(startYear, startMonth - 1, 1);
                    const lastMonth = new Date(endYear, endMonth - 1, 1);
                    while (cursor <= lastMonth) {
                        const key = `${cursor.getFullYear()}-${String(cursor.getMonth() + 1).padStart(2, '0')}`;
                        const category = cursor.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
                        grouped.set(key, { category, count: 0, amount: 0 });
                        cursor.setMonth(cursor.getMonth() + 1);
                    }
                }
                section.querySelectorAll('[data-report-source] [data-report-date]').forEach(source => {
                    if (source.dataset.reportDate < from || source.dataset.reportDate > to) return;
                    const key = monthly ? source.dataset.reportDate.slice(0, 7) : source.dataset.reportCategory;
                    const category = monthly
                        ? grouped.get(key)?.category
                        : source.dataset.reportCategory;
                    const count = Number(source.dataset.reportCount || 0);
                    const amount = Number(source.dataset.reportAmount || 0);
                    const item = grouped.get(key) || { category, count: 0, amount: 0 };
                    item.count += count;
                    item.amount += amount;
                    total += count;
                    grouped.set(key, item);
                });
                const summaryBody = section.querySelector('[data-report-summary]');
                if (!summaryBody) return;
                summaryBody.replaceChildren();
                const sorted = [...grouped.values()].sort((left, right) => right.count - left.count || left.category.localeCompare(right.category));
                sorted.forEach(item => {
                    const row = document.createElement('tr');
                    row.append(makeCell(item.category, 'fw-semibold heading-green'), makeCell(numberFormat.format(item.count)));
                    if (section.dataset.reportKind === 'documents') row.append(makeCell(currencyFormat.format(item.amount)));
                    summaryBody.append(row);
                });
                if (!sorted.length) {
                    const row = document.createElement('tr');
                    const cell = makeCell('No totals fall within the selected date range.', 'empty-state text-muted-soft');
                    cell.colSpan = section.querySelectorAll('thead th').length;
                    row.append(cell);
                    summaryBody.append(row);
                }
                if (section.dataset.reportSection === reportType.value) renderMetrics(section, grouped, total, from, to);
            });
            return true;
        }

        function exportCsv() {
            const section = sections.find(item => item.dataset.reportSection === reportType.value);
            const table = section?.querySelector('table');
            if (!section || !table) return;
            const values = [
                ['Report', section.dataset.reportTitle],
                ['Period', `${fromInput.value} to ${toInput.value}`],
                [],
                [...table.querySelectorAll('thead th')].map(cell => cell.textContent.trim()),
                ...[...table.querySelectorAll('[data-report-summary] tr')].map(row => [...row.cells].map(cell => cell.textContent.trim())),
            ];
            const csv = values.map(row => row.map(value => `"${String(value).replaceAll('"', '""')}"`).join(',')).join('\r\n');
            const blob = new Blob([`\uFEFF${csv}`], { type: 'text/csv;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `${reportType.value}-report-${fromInput.value}-to-${toInput.value}.csv`;
            link.click();
            window.setTimeout(() => URL.revokeObjectURL(url), 0);
        }

        reportType.addEventListener('change', () => {
            setReportView();
            applyDateRange();
        });
        document.getElementById('generateReport')?.addEventListener('click', () => {
            if (applyDateRange()) showAdminToast('Report updated for the selected date range.');
        });
        document.querySelectorAll('[data-report-export]').forEach(button => {
            button.addEventListener('click', () => {
                if (!applyDateRange()) return;
                if (button.dataset.reportExport === 'CSV') exportCsv();
                else window.print();
            });
        });
        setReportView();
        applyDateRange();
    }

    setupAccountManagement();
    setupResidentRecords();
    setupDocumentTypes();
    setupReports();
});
