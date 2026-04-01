/* admin_applicants.js — page-specific scripts */

/* ── Status modal ──────────────────────────────────────── */

function updateStatus(id) {
    document.getElementById('applicant_id').value = id;
    document.getElementById('statusModal').style.display = 'block';
}

/* ── Convert modal ─────────────────────────────────────── */

function openConvertModal(id, name, course) {
    document.getElementById('convert_applicant_id').value = id;
    document.getElementById('convertName').textContent = name;
    const courseSelect = document.getElementById('convert_course');
    for (const opt of courseSelect.options) {
        if (opt.value === course) { opt.selected = true; break; }
    }
    document.getElementById('convertModal').style.display = 'block';
}

/* ── Close modals on outside click ────────────────────── */

window.addEventListener('click', function(e) {
    ['statusModal', 'convertModal', 'examModal'].forEach(id => {
        const modal = document.getElementById(id);
        if (e.target === modal) modal.style.display = 'none';
    });
});

/* ── Batch selection ───────────────────────────────────── */

function getSelected() {
    return [...document.querySelectorAll('.row-check:checked')].map(c => c.value);
}

function updateBatch() {
    const sel      = getSelected();
    const bar      = document.getElementById('batchBar');
    const total    = document.querySelectorAll('.row-check').length;
    const selectAll = document.getElementById('selectAll');

    bar.classList.toggle('visible', sel.length > 0);
    document.getElementById('batchCount').textContent = sel.length + ' selected';

    selectAll.indeterminate = sel.length > 0 && sel.length < total;
    selectAll.checked       = sel.length === total && total > 0;
}

function toggleAll(cb) {
    document.querySelectorAll('.row-check').forEach(c => c.checked = cb.checked);
    updateBatch();
}

function deselectAll() {
    document.querySelectorAll('.row-check').forEach(c => c.checked = false);
    document.getElementById('selectAll').checked = false;
    updateBatch();
}

/* ── Exam modal ────────────────────────────────────────── */

function openExamModal() {
    const sel = getSelected();
    if (sel.length === 0) {
        alert('Please select at least one applicant first.');
        return;
    }
    document.getElementById('examModalDesc').textContent =
        'Assigning exam schedule to ' + sel.length + ' applicant(s).';
    document.getElementById('examApplicantInputs').innerHTML =
        sel.map(id => `<input type="hidden" name="applicant_ids[]" value="${id}">`).join('');
    document.getElementById('examModal').style.display = 'block';
}

function clearExam() {
    const sel = getSelected();
    if (sel.length === 0) return;
    if (!confirm('Clear exam schedule for ' + sel.length + ' applicant(s)?')) return;
    document.getElementById('clearExamInputs').innerHTML =
        sel.map(id => `<input type="hidden" name="applicant_ids[]" value="${id}">`).join('');
    document.getElementById('clearExamForm').submit();
}