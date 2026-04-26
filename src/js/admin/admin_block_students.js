/* admin_block_students.js — page-specific scripts */

/* ── Student filter in assign dropdown ─────────────────── */

function filterStudents() {
    const search = document.getElementById('studentSearch').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;
    const select  = document.getElementById('studentSelect');
    const options = select.querySelectorAll('option');

    let visibleCount = 0;

    options.forEach((option, index) => {
        if (index === 0) return; // skip placeholder

        const name    = option.dataset.name    || '';
        const id      = option.dataset.id      || '';
        const email   = option.dataset.email   || '';
        const optStat = option.dataset.status  || '';

        let show = true;
        if (search && !name.includes(search) && !id.includes(search) && !email.includes(search)) show = false;
        if (status && optStat !== status) show = false;

        option.style.display = show ? '' : 'none';
        if (show) visibleCount++;
    });

    const countEl = document.getElementById('studentCount');
    if (countEl) {
        countEl.textContent = `(${visibleCount} student${visibleCount !== 1 ? 's' : ''} available)`;
    }
}

function clearFilters() {
    document.getElementById('studentSearch').value = '';
    document.getElementById('statusFilter').value  = '';
    filterStudents();
}

// Initialise count on load
window.addEventListener('load', filterStudents);

/* ── Assigned students search filter ───────────────────── */

const assignedSearch = document.getElementById('assignedSearch');
if (assignedSearch) {
    assignedSearch.addEventListener('input', function () {
        const q = this.value.toLowerCase();
        document.querySelectorAll('.block-students-row').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
}