(() => {
    // Immediately hide page on bfcache restore before any paint
    window.addEventListener('pageshow', (e) => {
        if (e.persisted) {
            document.documentElement.style.visibility = 'hidden';
            window.location.reload();
        }
    });

    function showLoader(msg) {
        const loader = document.getElementById('plm-loader');
        const bar    = document.getElementById('plm-loader-bar');
        const status = document.getElementById('plm-loader-status');
        if (!loader) return;
        if (status && msg) status.textContent = msg;
        bar.classList.remove('complete');
        bar.style.animation = 'none';
        bar.offsetHeight;
        bar.style.animation = '';
        loader.classList.add('visible');
    }

    // Only show loader on login form submit
    const form = document.getElementById('login-form');
    if (form) {
        form.addEventListener('submit', () => showLoader('Authenticating...'));
    }

    function hideLoader() {
        const loader = document.getElementById('plm-loader');
        const bar    = document.getElementById('plm-loader-bar');
        if (!loader) return;
        bar.classList.add('complete');
        loader.classList.remove('visible');
    }

    window.plmLoader = { show: showLoader, hide: hideLoader };
})();
