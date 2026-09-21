const messageEl = document.getElementById('loginMessage');
const toastEl = document.getElementById('loginToast');
const toastMessageEl = document.getElementById('loginToastMessage');

const showToast = (message, variant) => {
    if (messageEl) messageEl.textContent = message;
    if (toastMessageEl) toastMessageEl.textContent = message;
    if (toastEl) {
        toastEl.className = `toast align-items-center border-0 text-bg-${variant}`;
        bootstrap.Toast.getOrCreateInstance(toastEl, { delay: variant === 'primary' ? 10000 : 5000 }).show();
    }
};

const loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const form = e.target;
        const formData = new FormData(form);
        showToast('Logging in…', 'primary');

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const response = await fetch('./api/auth.php', {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': csrfToken
                },
                body: formData,
                credentials: 'same-origin'
            });

            const data = await response.json();

            if (response.ok && data.status === 'ok') {
                showToast('Logged in! Redirecting to your dashboard…', 'success');
                setTimeout(() => window.location.href = './dashboard.php', 1000);
            } else {
                showToast(data.message || 'Login failed', 'danger');
            }
        } catch (err) {
            showToast('Something went wrong. Please try again.', 'danger');
        }
    });
}

const registerForm = document.getElementById('registerForm');
if (registerForm) {
    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const form = e.target;
        const pass = form.querySelector('#registerPasswordInput')?.value || '';
        const confirmPass = form.querySelector('#confirmpassInput')?.value || '';

        if (pass !== confirmPass) {
            showToast('Passwords do not match.', 'danger');
            return;
        }

        const formData = new FormData(form);
        formData.append('action', 'register');
        showToast('Creating resident account…', 'primary');

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const response = await fetch('./api/auth.php', {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': csrfToken
                },
                body: formData,
                credentials: 'same-origin'
            });

            const data = await response.json();

            if (response.ok && data.status === 'ok') {
                showToast('Account created! Redirecting to your dashboard…', 'success');
                setTimeout(() => window.location.href = './dashboard.php', 1000);
            } else {
                showToast(data.message || 'Registration failed.', 'danger');
            }
        } catch (err) {
            showToast('Something went wrong during registration. Please try again.', 'danger');
        }
    });
}
