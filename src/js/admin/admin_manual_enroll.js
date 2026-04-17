// Two-row curriculum nav: Year tabs + Semester tabs
const yearTabs = document.querySelectorAll('.year-tab');
const semTabs  = document.querySelectorAll('.sem-tab');

yearTabs.forEach(tab => {
    tab.addEventListener('click', function () {
        const yr = this.dataset.year;
        yearTabs.forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        semTabs.forEach(t => {
            t.style.display = t.dataset.year === yr ? '' : 'none';
            t.classList.remove('active');
        });
        const firstSem = document.querySelector(`.sem-tab[data-year="${yr}"]`);
        if (firstSem) {
            firstSem.classList.add('active');
            showPanel(firstSem.dataset.target);
        }
    });
});

semTabs.forEach(tab => {
    tab.addEventListener('click', function () {
        semTabs.forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        showPanel(this.dataset.target);
    });
});

function showPanel(id) {
    document.querySelectorAll('.curric-panel').forEach(p => p.classList.remove('active'));
    const panel = document.getElementById(id);
    if (panel) panel.classList.add('active');
}

// Reserve button — reads the dropdown in the same row
document.querySelectorAll('.btn-reserve-row').forEach(btn => {
    btn.addEventListener('click', function () {
        const subjectId = this.dataset.subjectId;
        const dropdown  = document.querySelector(`.class-dropdown[data-subject-id="${subjectId}"]`);
        if (!dropdown || !dropdown.value) {
            alert('Please select a class from the dropdown first.');
            return;
        }
        if (!confirm('Reserve this class for the student?')) return;
        document.getElementById('reserveClassId').value = dropdown.value;
        document.getElementById('reserveForm').submit();
    });
});

// Irregular subject search filter
const irregularSearch = document.getElementById('irregularSearch');
if (irregularSearch) {
    irregularSearch.addEventListener('input', function () {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#irregularTable tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
}
