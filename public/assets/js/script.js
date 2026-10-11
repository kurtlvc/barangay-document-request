document.addEventListener("DOMContentLoaded", function () {
    const navbar = document.getElementById("navbarCollapse");
    const links = navbar ? navbar.querySelectorAll(".nav-link") : [];
    const toggler = document.querySelector('[data-bs-target="#navbarCollapse"]');

    links.forEach(function (link) {
        link.addEventListener("click", function () {
            const bsCollapse = bootstrap.Collapse.getOrCreateInstance(navbar);

            bsCollapse.hide();
        });
    });

    document.addEventListener("click", function (event) {
        if (!navbar || !navbar.classList.contains("show")) return;
        if (navbar.contains(event.target)) return;
        if (toggler && toggler.contains(event.target)) return;
        bootstrap.Collapse.getOrCreateInstance(navbar).hide();
    });

    document.addEventListener("keydown", function (event) {
        if (event.key !== "Escape" || !navbar || !navbar.classList.contains("show")) return;
        bootstrap.Collapse.getOrCreateInstance(navbar).hide();
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


