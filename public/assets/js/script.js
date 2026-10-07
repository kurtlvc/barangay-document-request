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
        document.documentElement.dataset.theme = isDark ? 'dark' : 'light';
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


