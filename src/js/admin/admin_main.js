/* Mobile Nav Button */
const navButton = document.getElementById('navButton');
const navMenu   = document.getElementById('navMenu');

/* Dropdowns */
const dropdowns = [
    {
        trigger: document.getElementById('student-records-dropdown'),
        menu:    document.getElementById('student-records-menu')
    },
    {
        trigger: document.getElementById('acad-records-dropdown'),
        menu:    document.getElementById('acad-records-menu')
    },
    {
        trigger: document.getElementById('personnel-dropdown'),
        menu:    document.getElementById('personnel-menu')
    },
    {
        trigger: document.getElementById('comms-dropdown'),
        menu:    document.getElementById('comms-menu')
    }
];

/* Mobile Nav */
if (navButton && navMenu) {
    navButton.addEventListener('click', () => {
        navMenu.classList.toggle('open');
    });
}

/* Dropdown toggle — closes others when one opens */
dropdowns.forEach(({ trigger, menu }) => {
    if (!trigger || !menu) return;

    trigger.addEventListener('click', event => {
        event.preventDefault();

        const isOpen = menu.classList.contains('open');

        // Close all
        dropdowns.forEach(d => {
            d.trigger?.classList.remove('open');
            d.menu?.classList.remove('open');
        });

        // Re-open if it wasn't open before
        if (!isOpen) {
            trigger.classList.add('open');
            menu.classList.add('open');

            // Open sidebar on mobile if collapsed
            if (navMenu.offsetWidth <= 60) {
                navMenu.classList.add('open');
            }
        }
    });
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


/* ── Active Sidebar Link ───────────────────────────────── */

const currentPath = window.location.pathname.split('/').pop();
document.querySelectorAll('.main-ul li a').forEach(link => {
    const href = link.getAttribute('href')?.split('/').pop();
    if (href && href === currentPath) {
        link.classList.add('active');
    }
});