/* ── Mobile Nav Button ──────────────────────────────────── */

const navButton = document.getElementById('navButton');
const navMenu   = document.getElementById('navMenu');


navButton.addEventListener('click', () => {
    navMenu.classList.toggle('open');
});


/* Dark Mode Toggle */

const toggleTrack = document.getElementById('toggleTrack');
const modeIcon    = document.getElementById('modeIcon');
const modeLabel   = document.getElementById('modeLabel');

function applyTheme(isDark) {
    document.documentElement.classList.toggle('dark-mode', isDark); // ← updated
    toggleTrack.classList.toggle('active', isDark);
    modeIcon.className  = isDark ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
    modeLabel.textContent = isDark ? 'Light Mode' : 'Dark Mode';
    localStorage.setItem('darkMode', isDark);
}

toggleTrack.addEventListener('click', () => {
    applyTheme(!document.documentElement.classList.contains('dark-mode')); // ← updated
});

// Restore saved preference on page load
applyTheme(localStorage.getItem('darkMode') === 'true');