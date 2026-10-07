/* Account Settings — loads and saves the profile through api/profile.php */
document.addEventListener('DOMContentLoaded', () => {
    const personalForm = document.getElementById('personalForm');
    const securityForm = document.getElementById('securityForm');
    if (!personalForm || !securityForm) return;

    const apiUrl = (document.querySelector('meta[name="base-path"]')?.content || '.') + '/api/profile.php';
    const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
    const $ = (id) => document.getElementById(id);
    const isResident = !!$('editFirstName');

    const showAlert = (el, message, type) => {
        el.className = `alert alert-${type}`;
        el.textContent = message;
        el.scrollIntoView({behavior: 'smooth', block: 'nearest'});
    };
    const clearAlert = (el) => { el.className = 'alert d-none'; el.textContent = ''; };

    const setInvalid = (input, invalid) => input.classList.toggle('is-invalid', invalid);

    const setBusy = (btn, busy) => {
        if (busy) {
            btn.dataset.label = btn.textContent;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving…';
        } else {
            btn.disabled = false;
            btn.textContent = btn.dataset.label || 'Submit Changes';
        }
    };

    async function request(method, body) {
        const response = await fetch(apiUrl, {
            method,
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-Token': csrfToken()
            },
            body: body ? JSON.stringify(body) : undefined
        });
        let result = {};
        try { result = await response.json(); } catch { /* non-JSON response */ }
        if (!response.ok || result.status !== 'ok') {
            throw new Error(result.message || 'Something went wrong. Please try again.');
        }
        return result;
    }

    // Keep the sidebar / navbar name in sync after a save
    const refreshDisplayedName = (name) => {
        document.querySelectorAll('.profile-card-name').forEach((el) => { el.textContent = name; });
        document.querySelectorAll('.navbar .dropdown-menu strong').forEach((el) => { el.textContent = name; });
    };

    // ── Load profile ─────────────────────────────────────────────────
    (async () => {
        const submit = $('personalSubmit');
        try {
            if (isResident) submit.disabled = true;
            const {profile} = await request('GET');
            if (!profile) throw new Error('Profile not found.');
            $('editEmail').value = profile.email || '';
            if (isResident) {
                $('editFirstName').value = profile.first_name || '';
                $('editLastName').value = profile.last_name || '';
                $('editContact').value = profile.contact_number || '';
                $('editAddress').value = profile.address || '';
                submit.disabled = false;
            } else {
                $('editName').value = profile.name || '';
            }
        } catch (error) {
            showAlert($('personalAlert'), error.message, 'danger');
        }
    })();

    // ── Personal information ─────────────────────────────────────────
    personalForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const alertEl = $('personalAlert');
        clearAlert(alertEl);

        const fields = isResident
            ? ['editFirstName', 'editLastName', 'editContact', 'editAddress'].map($)
            : [$('editName')];
        let valid = true;
        fields.forEach((input) => {
            const empty = input.value.trim() === '';
            setInvalid(input, empty);
            if (empty) valid = false;
        });
        if (isResident) {
            const phone = $('editContact');
            if (phone.value.trim() !== '' && !/^(09|\+639)\d{9}$/.test(phone.value.trim())) {
                setInvalid(phone, true);
                showAlert(alertEl, 'Enter a valid PH mobile number (e.g. 09171234567).', 'danger');
                return;
            }
        }
        if (!valid) {
            showAlert(alertEl, 'Please complete all required fields.', 'danger');
            return;
        }

        const payload = isResident
            ? {
                name: `${$('editFirstName').value.trim()} ${$('editLastName').value.trim()}`,
                first_name: $('editFirstName').value.trim(),
                last_name: $('editLastName').value.trim(),
                contact_number: $('editContact').value.trim(),
                address: $('editAddress').value.trim()
            }
            : {name: $('editName').value.trim()};

        const btn = $('personalSubmit');
        setBusy(btn, true);
        try {
            const result = await request('PATCH', payload);
            refreshDisplayedName(payload.name);
            showAlert(alertEl, result.message || 'Profile updated.', 'success');
        } catch (error) {
            showAlert(alertEl, error.message, 'danger');
        } finally {
            setBusy(btn, false);
        }
    });

    // ── Sign in and security ─────────────────────────────────────────
    securityForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const alertEl = $('securityAlert');
        clearAlert(alertEl);

        const email = $('editEmail');
        const current = $('currentPass');
        const next = $('editPass');
        const confirm = $('confirmPass');
        [email, current, next, confirm].forEach((input) => setInvalid(input, false));

        if (!email.value.trim() || !email.checkValidity()) {
            setInvalid(email, true);
            showAlert(alertEl, 'Enter a valid email address.', 'danger');
            return;
        }

        const changingPassword = next.value !== '' || confirm.value !== '';
        if (changingPassword) {
            if (current.value === '') {
                setInvalid(current, true);
                showAlert(alertEl, 'Enter your current password to set a new one.', 'danger');
                return;
            }
            if (next.value.length < 8) {
                setInvalid(next, true);
                showAlert(alertEl, 'New password must be at least 8 characters.', 'danger');
                return;
            }
            if (next.value !== confirm.value) {
                setInvalid(confirm, true);
                showAlert(alertEl, 'New passwords do not match.', 'danger');
                return;
            }
        }

        const payload = {email: email.value.trim()};
        if (changingPassword) {
            payload.current_password = current.value;
            payload.new_password = next.value;
        }

        const btn = $('securitySubmit');
        setBusy(btn, true);
        try {
            const result = await request('PATCH', payload);
            current.value = next.value = confirm.value = '';
            showAlert(alertEl, result.message || 'Account updated.', 'success');
        } catch (error) {
            if (/current password/i.test(error.message)) setInvalid(current, true);
            showAlert(alertEl, error.message, 'danger');
        } finally {
            setBusy(btn, false);
        }
    });
});
