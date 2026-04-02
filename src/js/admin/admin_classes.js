/* admin_classes.js — page-specific scripts */

/* ── Close modal ───────────────────────────────────────── */

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

window.addEventListener('click', function(e) {
    const m = document.getElementById('formModal');
    if (e.target === m) m.style.display = 'none';
});

/* ── Filter subjects in the dropdown ──────────────────── */

function filterSubjects() {
    const deptFilter = document.getElementById('filter_dept').value.toLowerCase();
    const yearFilter = document.getElementById('filter_year').value;
    const semFilter  = document.getElementById('filter_sem').value.toLowerCase();
    const select     = document.getElementById('form_subject_id');
    const options    = select.querySelectorAll('option');

    let visibleCount = 0;

    options.forEach((option, index) => {
        if (index === 0) return; // skip placeholder

        const dept = (option.dataset.dept || '').toLowerCase();
        const year = option.dataset.year || '';
        const sem  = (option.dataset.sem  || '').toLowerCase();

        let show = true;
        if (deptFilter && !dept.includes(deptFilter)) show = false;
        if (yearFilter && year !== yearFilter)         show = false;
        if (semFilter  && sem  !== semFilter)          show = false;

        option.style.display = show ? '' : 'none';
        if (show) visibleCount++;
    });

    const countEl = document.getElementById('filter_count');
    if (deptFilter || yearFilter || semFilter) {
        countEl.textContent = `Showing ${visibleCount} subject${visibleCount !== 1 ? 's' : ''}`;
    } else {
        countEl.textContent = 'Showing all subjects';
    }
}

/* ── Reset modal filters ───────────────────────────────── */

function resetModalFilters() {
    document.getElementById('filter_dept').value = '';
    document.getElementById('filter_year').value = '';
    document.getElementById('filter_sem').value  = '';
    filterSubjects();
}

/* ── Add Class modal ───────────────────────────────────── */

function openAdd() {
    document.getElementById('formModalTitle').textContent = 'Add Class';
    document.getElementById('formSubmitBtn').textContent  = 'Create Class';
    document.getElementById('form_action').value          = 'add';
    document.getElementById('form_class_id').value        = '';

    ['subject_id','section','faculty_id','schedule_day','schedule_time','room'].forEach(f => {
        const el = document.getElementById('form_' + f);
        if (el) el.value = '';
    });

    document.getElementById('form_school_year').value = '2024-2025';
    document.getElementById('form_semester').value    = '';
    document.getElementById('form_max_slots').value   = 40;
    document.getElementById('form_status').value      = 'open';

    resetModalFilters();
    document.getElementById('formModal').style.display = 'block';
}

/* ── Edit Class modal ──────────────────────────────────── */

function openEdit(raw) {
    const c = JSON.parse(raw);
    document.getElementById('formModalTitle').textContent = 'Edit Class';
    document.getElementById('formSubmitBtn').textContent  = 'Save Changes';
    document.getElementById('form_action').value          = 'edit';
    document.getElementById('form_class_id').value        = c.class_id;

    ['subject_id','section','faculty_id','school_year','semester',
     'schedule_day','schedule_time','room','max_slots','status'].forEach(f => {
        const el = document.getElementById('form_' + f);
        if (el) el.value = c[f] ?? '';
    });

    resetModalFilters();
    document.getElementById('formModal').style.display = 'block';
}