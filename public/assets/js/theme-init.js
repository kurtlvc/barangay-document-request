(function () {
    try {
        if (localStorage.getItem('dokubayan-theme') !== 'dark') return;
        document.documentElement.dataset.theme = 'dark';
        var sync = function () {
            if (document.body) {
                document.body.dataset.theme = 'dark';
                return true;
            }
            return false;
        };
        if (!sync()) {
            var observer = new MutationObserver(function () {
                if (sync()) observer.disconnect();
            });
            observer.observe(document.documentElement, { childList: true });
            document.addEventListener('DOMContentLoaded', sync, { once: true });
        }
    } catch (e) {}
})();
