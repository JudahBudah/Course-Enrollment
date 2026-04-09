/* admin_students.js — page-specific scripts */

/* ── Modal helpers ─────────────────────────────────────── */

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

window.addEventListener('click', function(e) {
    ['viewModal', 'editModal'].forEach(id => {
        const m = document.getElementById(id);
        if (e.target === m) m.style.display = 'none';
    });
});

/* ── View modal ────────────────────────────────────────── */

function openView(raw) {
    const s        = JSON.parse(raw);
    const initials = ((s.first_name || '')[0] || '') + ((s.last_name || '')[0] || '');

    document.getElementById('view_avatar').textContent      = initials.toUpperCase();
    document.getElementById('view_fullname').textContent    = [s.first_name, s.middle_name, s.last_name, s.suffix_name].filter(Boolean).join(' ');
    document.getElementById('view_student_number').textContent = 'Student No: ' + (s.student_number || 'N/A');
    document.getElementById('view_status_badge').innerHTML  =
        '<span class="badge ' + (s.status || '').toLowerCase().replace(/ /g, '-') + '">' + (s.status || 'N/A') + '</span>';

    const fields = ['first_name','last_name','middle_name','suffix_name','gender','birthdate',
                    'email','contact_number','college','course','year_level',
                    'registration_status','status','account_status','created_at'];
    fields.forEach(f => {
        const el = document.getElementById('vw_' + f);
        if (el) el.textContent = s[f] || '—';
    });
    document.getElementById('vw_block').textContent = s.block_name || 'No Block';
    document.getElementById('viewModal').style.display = 'block';
}

/* ── Edit modal ────────────────────────────────────────── */

function populateBlocks(course, selectedBlockId) {
    const select = document.getElementById('edit_block_id');
    select.innerHTML = '<option value="">No Block (Irregular)</option>';
    const filtered = ALL_BLOCKS.filter(b => !course || b.course === course);
    filtered.forEach(b => {
        const opt = document.createElement('option');
        opt.value = b.block_id;
        opt.textContent = b.block_name + ' — ' + b.course + ' Yr' + b.year_level;
        if (String(b.block_id) === String(selectedBlockId)) opt.selected = true;
        select.appendChild(opt);
    });
}

function openEdit(raw) {
    const s = JSON.parse(raw);

    const fields = ['student_id','student_number','first_name','last_name','middle_name',
                    'suffix_name','gender','birthdate','email','contact_number','college',
                    'course','year_level','account_status','status'];
    fields.forEach(f => {
        const el = document.getElementById('edit_' + f);
        if (el) el.value = s[f] || '';
    });

    const regStatusEl = document.getElementById('edit_registration_status');
    if (regStatusEl) regStatusEl.value = s.registration_status || 'Unknown';

    populateBlocks(s.course || '', s.block_id || '');
    document.getElementById('editModal').style.display = 'block';
}

document.getElementById('edit_course').addEventListener('change', function() {
    populateBlocks(this.value, '');
});