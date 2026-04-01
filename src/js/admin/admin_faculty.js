/* admin_faculty.js — page-specific scripts */

/* ── Close modal on outside click ─────────────────────── */

window.addEventListener('click', function(e) {
    const m = document.getElementById('formModal');
    if (e.target === m) m.style.display = 'none';
});

/* ── Add Faculty modal ─────────────────────────────────── */

function openAdd() {
    document.getElementById('formModalTitle').textContent  = 'Add Faculty';
    document.getElementById('formSubmitBtn').textContent   = 'Add Faculty';
    document.getElementById('form_action').value           = 'add';
    document.getElementById('form_faculty_id').value       = '';
    document.getElementById('form_hint').textContent       = 'Default password will be set to the Employee ID.';

    ['employee_id','email','first_name','last_name','middle_name','department','position'].forEach(f => {
        document.getElementById('form_' + f).value = '';
    });
    document.getElementById('form_employment_status').value = 'full-time';
    document.getElementById('form_status').value            = 'active';
    document.getElementById('formModal').style.display      = 'block';
}

/* ── Edit Faculty modal ────────────────────────────────── */

function openEdit(raw) {
    const f = JSON.parse(raw);
    document.getElementById('formModalTitle').textContent = 'Edit Faculty';
    document.getElementById('formSubmitBtn').textContent  = 'Save Changes';
    document.getElementById('form_action').value          = 'edit';
    document.getElementById('form_hint').textContent      = '';

    ['faculty_id','employee_id','email','first_name','last_name',
     'middle_name','department','position','employment_status','status'].forEach(k => {
        const el = document.getElementById('form_' + k);
        if (el) el.value = f[k] ?? '';
    });

    document.getElementById('formModal').style.display = 'block';
}

/* ── Assign / Schedule pane switcher ───────────────────── */

function switchMode(mode) {
    const paneAssign   = document.getElementById('pane-assign');
    const paneSchedule = document.getElementById('pane-schedule');
    if (paneAssign)   paneAssign.style.display   = mode === 'assign'   ? '' : 'none';
    if (paneSchedule) paneSchedule.style.display = mode === 'schedule' ? '' : 'none';

    // Toggle active class on the panel's own view-tabs
    document.querySelectorAll('.panel > .view-tabs .view-tab').forEach((t, i) => {
        t.classList.toggle('active',
            (mode === 'assign'   && i === 0) ||
            (mode === 'schedule' && i === 1)
        );
    });
}

/* ── List / Calendar sub-tab switcher ─────────────────── */

function switchSched(tab) {
    const list = document.getElementById('sched-list');
    const cal  = document.getElementById('sched-cal');
    const tabList = document.getElementById('stab-list');
    const tabCal  = document.getElementById('stab-cal');

    if (list)    list.style.display = tab === 'list' ? '' : 'none';
    if (cal)     cal.style.display  = tab === 'cal'  ? '' : 'none';
    if (tabList) tabList.classList.toggle('active', tab === 'list');
    if (tabCal)  tabCal.classList.toggle('active',  tab === 'cal');
}