/* admin_subjects.js — page-specific scripts */

/* ── Close modals ──────────────────────────────────────── */

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

window.addEventListener('click', function(e) {
    ['viewModal', 'formModal'].forEach(id => {
        const m = document.getElementById(id);
        if (e.target === m) m.style.display = 'none';
    });
});

/* ── Add Subject modal ─────────────────────────────────── */

function openAdd() {
    document.getElementById('formModalTitle').textContent = 'Add Subject';
    document.getElementById('formSubmitBtn').textContent  = 'Add Subject';
    document.getElementById('form_action').value          = 'add';
    document.getElementById('form_subject_id').value      = '';

    ['subject_code','subject_name','description','department','prerequisite'].forEach(f => {
        document.getElementById('form_' + f).value = '';
    });

    document.getElementById('form_units').value         = 3;
    document.getElementById('form_lecture_hours').value = 3.0;
    document.getElementById('form_lab_hours').value     = 0.0;
    document.getElementById('form_year_level').value    = '';
    document.getElementById('form_semester').value      = '';
    document.getElementById('form_status').value        = 'active';
    document.getElementById('formModal').style.display  = 'block';
}

/* ── Edit Subject modal ────────────────────────────────── */

function openEdit(raw) {
    const s = JSON.parse(raw);
    document.getElementById('formModalTitle').textContent = 'Edit Subject';
    document.getElementById('formSubmitBtn').textContent  = 'Save Changes';
    document.getElementById('form_action').value          = 'edit';
    document.getElementById('form_subject_id').value      = s.subject_id;

    ['subject_code','subject_name','description','department','prerequisite',
     'units','lecture_hours','lab_hours','status'].forEach(f => {
        const el = document.getElementById('form_' + f);
        if (el) el.value = s[f] ?? '';
    });

    document.getElementById('form_year_level').value   = s.year_level ?? '';
    document.getElementById('form_semester').value     = s.semester   ?? '';
    document.getElementById('formModal').style.display = 'block';
}

/* ── View Subject modal ────────────────────────────────── */

function openView(raw) {
    const s = JSON.parse(raw);

    document.getElementById('vw_title').textContent     = s.subject_name;
    document.getElementById('vw_code_line').textContent =
        s.subject_code + (s.department ? ' · ' + s.department : '');

    ['subject_name','subject_code','units','lecture_hours','lab_hours',
     'department','prerequisite','status','description'].forEach(f => {
        const el = document.getElementById('vw_' + f);
        if (el) el.textContent = s[f] || '—';
    });

    document.getElementById('vw_total_hours').textContent =
        (parseFloat(s.lecture_hours || 0) + parseFloat(s.lab_hours || 0)).toFixed(1);

    document.getElementById('vw_year_level').textContent =
        s.year_level ? 'Year ' + s.year_level : '—';

    document.getElementById('vw_semester').textContent = s.semester
        ? s.semester.charAt(0).toUpperCase() + s.semester.slice(1) + ' Semester'
        : '—';

    document.getElementById('viewModal').style.display = 'block';
}