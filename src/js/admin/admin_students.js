/* admin_students.js — page-specific scripts */

/* ── Modal helpers ─────────────────────────────────────── */

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

window.addEventListener('click', function(e) {
    ['viewModal', 'editModal', 'docModal'].forEach(id => {
        const m = document.getElementById(id);
        if (e.target === m) m.style.display = 'none';
    });
});

/* ── Tab switching ─────────────────────────────────────── */

function switchTab(tabId, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.view-tab').forEach(b => b.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    btn.classList.add('active');
}

/* ── View modal ────────────────────────────────────────── */

function openView(raw) {
    const s = JSON.parse(raw);

    // Reset to first tab
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.view-tab').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-info').classList.add('active');
    document.querySelector('.view-tab').classList.add('active');

    // Header
    const initials = ((s.first_name || '')[0] || '') + ((s.last_name || '')[0] || '');
    document.getElementById('view_avatar').textContent         = initials.toUpperCase();
    document.getElementById('view_fullname').textContent       = [s.first_name, s.middle_name, s.last_name, s.suffix_name].filter(Boolean).join(' ');
    document.getElementById('view_student_number').textContent = 'Student No: ' + (s.student_number || 'N/A');
    document.getElementById('view_status_badge').innerHTML     =
        '<span class="badge ' + (s.status || '').toLowerCase().replace(/ /g, '-') + '">' + (s.status || 'N/A') + '</span>';

    // All text fields
    const fields = [
        'first_name','last_name','middle_name','suffix_name','married_name',
        'gender','birthdate','place_of_birth','civil_status','religion',
        'nationality','disability','email','contact_number',
        'student_number','lrn','college','course','year_level',
        'registration_status','status','account_status','created_at',
        'perm_region','perm_province','perm_municipality','perm_barangay','perm_address','perm_zipcode',
        'mail_region','mail_province','mail_municipality','mail_barangay','mail_address','mail_zipcode'
    ];
    fields.forEach(f => {
        const el = document.getElementById('vw_' + f);
        if (el) el.textContent = s[f] || '—';
    });
    document.getElementById('vw_block').textContent = s.block_name || 'No Block';

    // Documents — fetch full record from server
    document.getElementById('vw_doc_list').innerHTML = '<p style="color:var(--text-label);padding:1rem;">Loading documents…</p>';
    fetch('../../php/get_student_details.php?id=' + s.student_id)
        .then(res => res.json())
        .then(full => {
            const docs = [
                { key: 'doc_form138',    label: 'Form 138' },
                { key: 'doc_birth_cert', label: 'Birth Certificate' },
                { key: 'doc_good_moral', label: 'Good Moral' },
                { key: 'doc_our_au001',  label: 'OUR AU001' },
                { key: 'doc_our_au002',  label: 'OUR AU002' }
            ];
            const basePath = full.applicant_id
                ? `../../uploads/applicants/${full.applicant_id}/`
                : `../../uploads/students/${full.student_id}/`;

            let docHtml = '';
            docs.forEach(d => {
                if (full[d.key]) {
                    const ext      = full[d.key].split('.').pop().toLowerCase();
                    const filePath = basePath + full[d.key];
                    docHtml += `
                        <div class="doc-item">
                            <div class="doc-info">
                                <i class="fa-solid fa-file-${ext === 'pdf' ? 'pdf' : 'image'}"></i>
                                <div>
                                    <strong>${d.label}</strong>
                                    <span>${full[d.key]}</span>
                                </div>
                            </div>
                            <button type="button" class="doc-view-btn" onclick="viewDocument('${filePath}', '${full[d.key]}', '${ext}')">
                                <i class="fa-solid fa-eye"></i> View
                            </button>
                        </div>`;
                } else {
                    docHtml += `
                        <div class="doc-item">
                            <div class="doc-info">
                                <i class="fa-solid fa-file" style="opacity:0.3;"></i>
                                <div>
                                    <strong>${d.label}</strong>
                                    <span class="doc-not-uploaded">Not uploaded</span>
                                </div>
                            </div>
                        </div>`;
                }
            });
            document.getElementById('vw_doc_list').innerHTML = docHtml;
        })
        .catch(() => {
            document.getElementById('vw_doc_list').innerHTML =
                '<p style="color:var(--text-label);padding:1rem;">Failed to load documents.</p>';
        });

    document.getElementById('viewModal').style.display = 'block';
}

/* ── Document viewer ───────────────────────────────────── */

function viewDocument(src, name, ext) {
    document.getElementById('docModalName').textContent = name;
    const body = document.getElementById('docModalBody');
    body.innerHTML = ext === 'pdf'
        ? `<iframe src="${src}" style="width:100%;height:100%;border:none;"></iframe>`
        : `<img src="${src}" alt="${name}" style="max-width:100%;max-height:100%;object-fit:contain;">`;
    document.getElementById('docModal').style.display = 'block';
}

function closeDocModal() {
    document.getElementById('docModal').style.display = 'none';
    document.getElementById('docModalBody').innerHTML = '';
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

/* ── Delete student ────────────────────────────────────── */

function deleteStudent(id, name) {
    if (!confirm(`Delete student "${name}"? This cannot be undone.`)) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `<input name="student_id" value="${id}"><input name="delete_student" value="1">`;
    document.body.appendChild(form);
    form.submit();
}
