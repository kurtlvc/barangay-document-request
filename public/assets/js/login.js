/* =====================================================================
   login.js — Login + Registration page logic
   Wrapped in an IIFE to avoid global const conflicts with script.js
   ===================================================================== */
(function () {
    'use strict';

    // ── Shared toast helper ──────────────────────────────────────────────
    const messageEl      = document.getElementById('loginMessage');
    const toastEl        = document.getElementById('loginToast');
    const toastMessageEl = document.getElementById('loginToastMessage');

    const showToast = (message, variant) => {
        if (messageEl)      messageEl.textContent = message;
        if (toastMessageEl) toastMessageEl.textContent = message;
        if (toastEl) {
            toastEl.className = `toast align-items-center border-0 text-bg-${variant}`;
            bootstrap.Toast.getOrCreateInstance(toastEl, {
                delay: variant === 'primary' ? 10000 : 5000
            }).show();
        }
    };

    // ── Utility: disable / restore a submit button ────────────────────
    const setButtonLoading = (btn, loading, loadingText = 'Please wait…') => {
        if (!btn) return;
        if (loading) {
            btn.dataset.originalText = btn.textContent;
            btn.disabled  = true;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>${loadingText}`;
        } else {
            btn.disabled     = false;
            btn.textContent  = btn.dataset.originalText || btn.textContent;
        }
    };

    // ── Utility: inline field error ──────────────────────────────────
    const setFieldError = (input, message) => {
        input.classList.toggle('is-invalid', !!message);
        input.classList.toggle('is-valid',   !message && input.value.trim() !== '');
        // Find or create the feedback element that immediately follows the input
        let fb = input.nextElementSibling;
        if (!fb || !fb.classList.contains('invalid-feedback')) {
            fb = document.createElement('div');
            fb.className = 'invalid-feedback';
            input.after(fb);
        }
        fb.textContent = message || '';
    };

    // ── Password visibility toggle ──────────────────────────────────
    // Targets inputs inside .password-field wrappers defined in HTML.
    // CSS handles .password-field positioning and input padding-right.
    const addPasswordToggle = (input) => {
        if (!input) return;
        const wrapper = input.closest('.password-field');
        if (!wrapper) return; // only run for properly wrapped inputs

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'password-toggle-btn';
        btn.setAttribute('aria-label', 'Toggle password visibility');
        btn.innerHTML = '<i class="bi bi-eye"></i>';
        wrapper.appendChild(btn);

        btn.addEventListener('click', () => {
            const show  = input.type === 'password';
            input.type  = show ? 'text' : 'password';
            btn.innerHTML = `<i class="bi bi-eye${show ? '-slash' : ''}"></i>`;
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });
    };

    document.querySelectorAll('input[type="password"]').forEach(addPasswordToggle);

    // ── Password strength indicator ──────────────────────────────────
    const initPasswordStrength = (passwordInput) => {
        if (!passwordInput) return;
        const bar  = document.createElement('div');
        const text = document.createElement('small');
        bar.className  = 'password-strength-bar mt-2';
        text.className = 'password-strength-text form-text';
        bar.innerHTML  = '<div class="password-strength-fill"></div>';
        passwordInput.closest('.col-md-6, .mb-3')?.append(bar, text);
        const fill = bar.querySelector('.password-strength-fill');

        passwordInput.addEventListener('input', () => {
            const val = passwordInput.value;
            let score = 0;
            if (val.length >= 8)           score++;
            if (/[A-Z]/.test(val))         score++;
            if (/[0-9]/.test(val))         score++;
            if (/[^A-Za-z0-9]/.test(val))  score++;

            const levels = [
                { label: '',       color: 'transparent', width: '0%'   },
                { label: 'Weak',   color: '#dc3545',     width: '25%'  },
                { label: 'Fair',   color: '#fd7e14',     width: '50%'  },
                { label: 'Good',   color: '#ffc107',     width: '75%'  },
                { label: 'Strong', color: '#198754',     width: '100%' },
            ];
            const lvl = levels[score] || levels[0];
            fill.style.width           = val.length ? lvl.width : '0%';
            fill.style.backgroundColor = lvl.color;
            text.textContent           = val.length ? lvl.label : '';
            text.style.color           = lvl.color;
        });
    };

    initPasswordStrength(document.getElementById('registerPasswordInput'));

    // ── Tab switching via URL hash ───────────────────────────────────
    const requestedPane = window.location.hash
        ? document.querySelector(window.location.hash)
        : null;
    if (requestedPane && window.bootstrap) {
        const requestedTab = document.querySelector(`[data-bs-target="#${requestedPane.id}"]`);
        if (requestedTab) bootstrap.Tab.getOrCreateInstance(requestedTab).show();
    }

    // ── Login form ───────────────────────────────────────────────────
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = loginForm.querySelector('[type="submit"]');
            setButtonLoading(submitBtn, true, 'Logging in…');
            showToast('Logging in…', 'primary');

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const response  = await fetch('./api/auth.php', {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': csrfToken },
                    body: new FormData(loginForm),
                    credentials: 'same-origin'
                });
                const data = await response.json();

                if (response.ok && data.status === 'ok') {
                    showToast('Logged in! Redirecting to your dashboard…', 'success');
                    setTimeout(() => window.location.href = './dashboard.php', 1000);
                } else {
                    setButtonLoading(submitBtn, false);
                    showToast(data.message || 'Login failed. Please check your credentials.', 'danger');
                }
            } catch {
                setButtonLoading(submitBtn, false);
                showToast('Something went wrong. Please try again.', 'danger');
            }
        });
    }

    // ── Registration multi-step form ─────────────────────────────────
    const registerForm = document.getElementById('registerForm');
    const prevBtn      = document.getElementById('prevBtn');
    const nextBtn      = document.getElementById('nextBtn');

    if (registerForm && prevBtn && nextBtn) {
        let currentStep    = 1;
        const totalSteps   = 3;

        // Set the active step visually and update button states
        const setStep = (step) => {
            currentStep = Math.min(Math.max(step, 1), totalSteps);

            document.querySelectorAll('.form-step').forEach((pane, i) => {
                pane.classList.toggle('active', i + 1 === currentStep);
            });
            document.querySelectorAll('.step-item').forEach((item, i) => {
                item.classList.toggle('active', i + 1 === currentStep);
                item.classList.toggle('done',   i + 1 < currentStep);
            });

            prevBtn.disabled    = currentStep === 1;
            nextBtn.textContent = currentStep === totalSteps ? 'Create Account' : 'Next';
        };

        // Validate all inputs inside the given step number
        const validateStep = (step) => {
            const pane   = document.getElementById('step' + step);
            const fields = [...pane.querySelectorAll('input')];
            let valid    = true;

            fields.forEach(field => {
                if (!field.checkValidity()) {
                    setFieldError(field, field.validationMessage);
                    valid = false;
                } else {
                    setFieldError(field, null);
                }
            });

            // Extra: PH phone number format
            if (step === 1) {
                const phone = document.getElementById('contactnumInput');
                if (phone && phone.value && !/^(09|\+639)\d{9}$/.test(phone.value.trim())) {
                    setFieldError(phone, 'Enter a valid PH mobile number (e.g. 09171234567).');
                    valid = false;
                }
            }

            // Extra: password match on step 3
            if (step === 3) {
                const pass    = document.getElementById('registerPasswordInput');
                const confirm = document.getElementById('confirmpassInput');
                if (pass && confirm && pass.value !== confirm.value) {
                    setFieldError(confirm, 'Passwords do not match.');
                    valid = false;
                }
            }

            return valid;
        };

        // Submit registration to the API
        const submitRegistration = async () => {
            const formData = new FormData(registerForm);
            formData.append('action', 'register');

            nextBtn.disabled  = true;
            nextBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Creating Account…`;
            prevBtn.disabled  = true;
            showToast('Creating resident account…', 'primary');

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const response  = await fetch('./api/auth.php', {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': csrfToken },
                    body: formData,
                    credentials: 'same-origin'
                });
                const data = await response.json();

                if (response.ok && data.status === 'ok') {
                    showToast('Account created! Redirecting to your dashboard…', 'success');
                    setTimeout(() => window.location.href = './dashboard.php', 1500);
                } else {
                    nextBtn.disabled    = false;
                    nextBtn.textContent = 'Create Account';
                    prevBtn.disabled    = false;
                    showToast(data.message || 'Registration failed. Please try again.', 'danger');

                    // Email conflict → jump back to step 1 and highlight the field
                    if (response.status === 409) {
                        setStep(1);
                        const emailField = document.getElementById('registerEmailInput');
                        if (emailField) setFieldError(emailField, data.message);
                    }
                }
            } catch {
                nextBtn.disabled    = false;
                nextBtn.textContent = 'Create Account';
                prevBtn.disabled    = false;
                showToast('Something went wrong during registration. Please try again.', 'danger');
            }
        };

        // Button handlers
        nextBtn.addEventListener('click', () => {
            if (!validateStep(currentStep)) return;
            if (currentStep < totalSteps) {
                setStep(currentStep + 1);
            } else {
                submitRegistration();
            }
        });

        prevBtn.addEventListener('click', () => setStep(currentStep - 1));

        // Clear invalid styles as user corrects a field
        registerForm.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', () => {
                if (input.classList.contains('is-invalid') && input.checkValidity()) {
                    setFieldError(input, null);
                }
            });
        });

        // Live confirm-password mismatch hint
        const confirmInput = document.getElementById('confirmpassInput');
        const passInput    = document.getElementById('registerPasswordInput');
        if (confirmInput && passInput) {
            confirmInput.addEventListener('input', () => {
                if (confirmInput.value && confirmInput.value !== passInput.value) {
                    setFieldError(confirmInput, 'Passwords do not match.');
                } else {
                    setFieldError(confirmInput, null);
                }
            });
        }

        // Prevent native form submit from firing mid-step; only allow on final step
        registerForm.addEventListener('submit', (e) => {
            e.preventDefault();
            if (currentStep === totalSteps && validateStep(totalSteps)) {
                submitRegistration();
            }
        });

        // ── Initialise: set step 1 as active and correct button states ──
        setStep(1);
    }

})();
