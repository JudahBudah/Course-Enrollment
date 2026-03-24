/* Academic Dropdown */

const acad_dropdown = document.getElementById("acad-dropdown");
const acad_dropdown_menu = document.getElementById("acad-dropdown-menu");

acad_dropdown.addEventListener("click", event => {
    acad_dropdown_menu.classList.toggle("open");
    acad_dropdown.classList.toggle("open");
});


/* Dark Mode Toggle */

const toggleTrack = document.getElementById('toggleTrack');
const modeIcon    = document.getElementById('modeIcon');
const modeLabel   = document.getElementById('modeLabel');

function applyTheme(isDark) {
    document.body.classList.toggle('dark-mode', isDark);
    toggleTrack.classList.toggle('active', isDark);
    modeIcon.className  = isDark ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
    modeLabel.textContent = isDark ? 'Light Mode' : 'Dark Mode';
    localStorage.setItem('darkMode', isDark);
}

toggleTrack.addEventListener('click', () => {
    applyTheme(!document.body.classList.contains('dark-mode'));
});

// Restore saved preference on page load
applyTheme(localStorage.getItem('darkMode') === 'true');