document.addEventListener("DOMContentLoaded", function () {
    const navbar = document.getElementById("navbarCollapse");
    const links = navbar ? navbar.querySelectorAll(".nav-link") : [];

    links.forEach(function (link) {
        link.addEventListener("click", function () {
            const bsCollapse = bootstrap.Collapse.getOrCreateInstance(navbar);

            bsCollapse.hide();
        });
    });

    const storedTheme = localStorage.getItem('dokubayan-theme');
    const themeToggle = document.getElementById('themeToggle');

    function applyTheme(isDark) {
        document.body.dataset.theme = isDark ? 'dark' : 'light';
        if (!themeToggle) return;
        const label = isDark ? 'Light mode' : 'Dark mode';
        themeToggle.setAttribute('aria-label', label);
        themeToggle.setAttribute('title', label);
        themeToggle.innerHTML = `<i class="bi ${isDark ? 'bi-sun-fill' : 'bi-moon-fill'}"></i>`;
        if (window.bootstrap) {
            bootstrap.Tooltip.getInstance(themeToggle)?.dispose();
            new bootstrap.Tooltip(themeToggle);
        }
    }

    applyTheme(storedTheme === 'dark');
    themeToggle?.addEventListener('click', () => {
        const isDark = document.body.dataset.theme !== 'dark';
        localStorage.setItem('dokubayan-theme', isDark ? 'dark' : 'light');
        applyTheme(isDark);
    });

    if (window.bootstrap) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(element => {
            if (!bootstrap.Tooltip.getInstance(element)) new bootstrap.Tooltip(element);
        });
    }
});

const registerForm = document.getElementById('registerForm');
const previousButton = document.getElementById('prevBtn');
const nextButton = document.getElementById('nextBtn');
if (registerForm && previousButton && nextButton) {
    let currentStep = 1;
    const totalSteps = 3;

    function setStep(step) {
        currentStep = Math.min(Math.max(step, 1), totalSteps);
        document.querySelectorAll('.form-step').forEach((pane, index) => {
            pane.classList.toggle('active', index + 1 === currentStep);
        });
        document.querySelectorAll('.step-item').forEach((item, index) => {
            item.classList.toggle('active', index + 1 === currentStep);
            item.classList.toggle('done', index + 1 < currentStep);
        });
        previousButton.disabled = currentStep === 1;
        nextButton.textContent = currentStep === totalSteps ? 'Create Account' : 'Next';
    }

    nextButton.addEventListener('click', () => {
        const currentPane = document.getElementById('step' + currentStep);
        const fields = [...currentPane.querySelectorAll('input')];
        if (!fields.every(field => field.reportValidity())) return;
        if (currentStep < totalSteps) setStep(currentStep + 1);
        else registerForm.requestSubmit();
    });

    previousButton.addEventListener('click', () => setStep(currentStep - 1));
}

const requestedPane = window.location.hash ? document.querySelector(window.location.hash) : null;
if (requestedPane && window.bootstrap) {
    const requestedTab = document.querySelector(`[data-bs-target="#${requestedPane.id}"]`);
    if (requestedTab) bootstrap.Tab.getOrCreateInstance(requestedTab).show();
}

