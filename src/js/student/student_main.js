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
    event.preventDefault();

    const isOpen = acadDropdownMenu.classList.contains("open");

    // Close
    acadDropdown.classList.remove("open");
    acadDropdownMenu.classList.remove("open");

    // Re-open if it wasn't open before
    if (!isOpen) {
        acadDropdown.classList.add("open");
        acadDropdownMenu.classList.add("open");
        localStorage.setItem('openDropdown', acadDropdown.id);

        if (navMenu.offsetWidth <= 60) {
            navMenu.classList.add("open");
        }
    } else {
        localStorage.removeItem('openDropdown');
    }
});


/* Dark Mode Toggle */

const toggleTrack = document.getElementById('toggleTrack');
const modeIcon    = document.getElementById('modeIcon');
const modeLabel   = document.getElementById('modeLabel');

function applyTheme(isDark) {
    document.documentElement.classList.toggle('dark-mode', isDark);
    toggleTrack.classList.toggle('active', isDark);
    modeIcon.className  = isDark ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
    modeLabel.textContent = isDark ? 'Light Mode' : 'Dark Mode';
    localStorage.setItem('darkMode', isDark);
}

toggleTrack.addEventListener('click', () => {
    applyTheme(!document.documentElement.classList.contains('dark-mode'));
});

// Restore saved preference on page load
applyTheme(localStorage.getItem('darkMode') === 'true');


/* ── Active Sidebar Link + Restore Dropdown ──────────── */

const currentPath = window.location.pathname.split('/').pop();
let activeDropdownFound = false;

document.querySelectorAll('.main-ul li a').forEach(link => {
    const href = link.getAttribute('href')?.split('/').pop();
    if (href && href === currentPath) {
        link.classList.add('active');

        // If this link is inside the acad dropdown, open it
        const menu = link.closest('.acad-dropdown-menu');
        if (menu) {
            menu.classList.add('open');
            const trigger = menu.previousElementSibling;
            if (trigger) {
                trigger.classList.add('open');
                localStorage.setItem('openDropdown', trigger.id);
            }
            activeDropdownFound = true;
        }
    }
});

// Fallback: restore last manually opened dropdown if no active child found
if (!activeDropdownFound) {
    const saved = localStorage.getItem('openDropdown');
    if (saved && saved === acadDropdown.id) {
        acadDropdown.classList.add('open');
        acadDropdownMenu.classList.add('open');
    }
}