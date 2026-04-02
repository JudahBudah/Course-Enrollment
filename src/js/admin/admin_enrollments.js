/* admin_enrollments.js — page-specific scripts */

/* ── Live search filter ────────────────────────────────── */

const searchInput = document.getElementById('searchInput');

if (searchInput) {
    searchInput.addEventListener('keyup', function() {
        const val  = this.value.toLowerCase();
        const rows = document.querySelectorAll('#enrollmentTable tr');

        rows.forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
        });
    });
}