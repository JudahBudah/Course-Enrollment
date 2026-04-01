/* ── Mobile Nav Button ─────────────────────────────────── */

const navButton = document.getElementById('navButton');
const navMenu   = document.getElementById('navMenu');

if (navButton && navMenu) {
    navButton.addEventListener('click', () => {
        navMenu.classList.toggle('open');
    });
}


/* ── Dark Mode Toggle ──────────────────────────────────── */

const toggleTrack = document.getElementById('toggleTrack');
const modeIcon    = document.getElementById('modeIcon');
const modeLabel   = document.getElementById('modeLabel');

function applyTheme(isDark) {
    document.body.classList.toggle('dark-mode', isDark);
    if (toggleTrack) toggleTrack.classList.toggle('active', isDark);
    if (modeIcon)    modeIcon.className  = isDark ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
    if (modeLabel)   modeLabel.textContent = isDark ? 'Light Mode' : 'Dark Mode';
    localStorage.setItem('adminDarkMode', isDark);
}

if (toggleTrack) {
    toggleTrack.addEventListener('click', () => {
        applyTheme(!document.body.classList.contains('dark-mode'));
    });
}

// Restore saved preference on page load
applyTheme(localStorage.getItem('adminDarkMode') === 'true');


/* ── Active Sidebar Link ───────────────────────────────── */

const currentPath = window.location.pathname.split('/').pop();
document.querySelectorAll('.main-ul li a').forEach(link => {
    const href = link.getAttribute('href')?.split('/').pop();
    if (href && href === currentPath) {
        link.classList.add('active');
    }
});