/* admin_faculty.js — page-specific scripts */

/* ── Modal helpers ─────────────────────────────────────── */

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

window.addEventListener('click', function(e) {
    ['viewModal', 'formModal'].forEach(id => {
        const m = document.getElementById(id);
        if (e.target === m) m.style.display = 'none';
    });
});

/* ── View modal ────────────────────────────────────────── */

function openView(raw) {
    const f = JSON.parse(raw);
    const initials = ((f.first_name || '')[0] || '') + ((f.last_name || '')[0] || '');

    document.getElementById('view_avatar').textContent   = initials.toUpperCase();
    document.getElementById('view_fullname').textContent =
        [f.first_name, f.middle_name, f.last_name, (f.suffix_name && f.suffix_name !== 'none' ? f.suffix_name : '')]
        .filter(Boolean).join(' ');
    document.getElementById('view_employee_id').textContent  = 'Employee ID: ' + (f.employee_id || 'N/A');
    document.getElementById('view_status_badge').innerHTML   =
        '<span class="badge ' + (f.status || '') + '">' + (f.status ? f.status.charAt(0).toUpperCase() + f.status.slice(1) : 'N/A') + '</span>';

    const simple = ['position','college','department','email',
                    'first_name','last_name','middle_name','suffix_name',
                    'date_of_birth','place_of_birth','sex','civil_status',
                    'religion','nationality','disability',
                    'phone','personal_email'];
    simple.forEach(k => {
        const el = document.getElementById('vw_' + k);
        if (el) el.textContent = f[k] || '—';
    });

    // Employment status — prettify
    const empEl = document.getElementById('vw_employment_status');
    if (empEl) empEl.textContent = f.employment_status
        ? f.employment_status.replace(/-/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
        : '—';

    // Permanent address
    const permEl = document.getElementById('vw_permanent_address_full');
    if (permEl) {
        const parts = [f.permanent_address, f.permanent_barangay, f.permanent_municipality,
                       f.permanent_province, f.permanent_region, f.permanent_zip_code].filter(Boolean);
        permEl.textContent = parts.length ? parts.join(', ') : '—';
    }

    // Mailing address
    const mailEl = document.getElementById('vw_mailing_address_full');
    if (mailEl) {
        if (parseInt(f.mailing_same_as_permanent) === 1) {
            mailEl.textContent = 'Same as permanent address';
        } else {
            const parts = [f.mailing_address, f.mailing_barangay, f.mailing_municipality,
                           f.mailing_province, f.mailing_region, f.mailing_zip_code].filter(Boolean);
            mailEl.textContent = parts.length ? parts.join(', ') : '—';
        }
    }

    document.getElementById('viewModal').style.display = 'block';
}

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
    ['assign','schedule'].forEach(p => {
        const el = document.getElementById('pane-' + p);
        if (el) el.style.display = mode === p ? '' : 'none';
    });

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
