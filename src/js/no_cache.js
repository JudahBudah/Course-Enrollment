// If the browser restores this page from bfcache (back/forward button),
// force a fresh server request so the session check runs again.
window.addEventListener('pageshow', (e) => {
    if (e.persisted) window.location.reload();
});
