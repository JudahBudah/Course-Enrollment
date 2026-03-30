/* Mobile Nav Button */
const navButton = document.getElementById("navButton");
const navIcon = document.getElementById("trans-bars");
const navMenu = document.getElementById("navMenu");

/* Acad Dropdown */
const acadDropdown = document.getElementById("acad-dropdown");
const acadDropdownMenu = document.getElementById("acad-dropdown-menu");


/* Mobile Nav */
navButton.addEventListener("click", event => {
    if (navMenu.offsetWidth === 200 && acadDropdownMenu.offsetHeight > 0) {
        acadDropdownMenu.classList.toggle("open");
        acadDropdown.classList.toggle("open");
        navMenu.classList.toggle("open");
    } else {
        navMenu.classList.toggle("open");
    }
});


/* Academic Dropdown */

acadDropdown.addEventListener("click", event => {
    acadDropdown.classList.toggle("open");
    acadDropdownMenu.classList.toggle("open");

    if (navMenu.offsetWidth === 60) {
        navMenu.classList.toggle("open");
    }
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