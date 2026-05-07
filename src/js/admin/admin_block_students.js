/* admin_block_students.js */

/* ── Assigned students search ───────────────────────────── */

const assignedSearch = document.getElementById('assignedSearch');
if (assignedSearch) {
    assignedSearch.addEventListener('input', function () {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#assignedTableBody .block-students-row').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
}

/* ── Student picker filter ──────────────────────────────── */

function filterStudents() {
    const search = document.getElementById('studentSearch').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;
    const cards  = document.querySelectorAll('#studentPicker .picker-card');

    let visible = 0;
    cards.forEach(card => {
        const name   = card.dataset.name   || '';
        const number = card.dataset.number || '';
        const email  = card.dataset.email  || '';
        const st     = card.dataset.status || '';

        const matchSearch = !search || name.includes(search) || number.includes(search) || email.includes(search);
        const matchStatus = !status || st === status;
        const show = matchSearch && matchStatus;

        card.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    const countEl = document.getElementById('studentCount');
    if (countEl) countEl.textContent = `(${visible} student${visible !== 1 ? 's' : ''} available)`;
}

function clearFilters() {
    document.getElementById('studentSearch').value = '';
    document.getElementById('statusFilter').value  = '';
    filterStudents();
}

window.addEventListener('load', filterStudents);

/* ── AJAX individual assign ─────────────────────────────── */

function updateCapacityUI(current, max) {
    const pct   = max > 0 ? Math.round(current / max * 100) : 0;
    const fill  = document.getElementById('capacityFill');
    const label = document.getElementById('capacityLabel');
    if (fill) {
        fill.style.width = pct + '%';
        fill.className = 'capacity-bar-fill' + (current >= max ? ' full' : pct >= 80 ? ' warn' : '');
    }
    if (label) label.textContent = `${current} / ${max} students`;
}

function addAssignedRow(student, studentId) {
    const empty = document.getElementById('assignedEmpty');
    if (empty) empty.remove();

    const body = document.getElementById('assignedTableBody');
    const row  = document.createElement('div');
    row.className = 'block-students-row';
    const name   = ((student.first_name || '') + ' ' + (student.last_name || '')).trim();
    const regSt  = student.registration_status || 'Regular';
    const blockId = document.querySelector('[data-block-id]')?.dataset.blockId
                  || new URLSearchParams(location.search).get('block_id') || '';

    row.innerHTML = `
        <div>${student.student_number || studentId}</div>
        <div class="block-students-col-left">${escHtml(name)}</div>
        <div class="block-students-col-left word-break">${escHtml(student.email || '')}</div>
        <div><span class="badge ${regSt.toLowerCase()}">${escHtml(regSt)}</span></div>
        <div>
            <div class="action-buttons">
                <form method="POST" action="../../php/remove_student_from_block.php" style="display:inline;">
                    <input type="hidden" name="student_id" value="${studentId}">
                    <input type="hidden" name="block_id"   value="${blockId}">
                    <button type="submit" class="btn-icon remove" title="Remove from Block"
                            onclick="return confirm('Remove student from this block?')">
                        <i class="fa-solid fa-user-minus"></i>
                    </button>
                </form>
            </div>
        </div>`;
    body.appendChild(row);
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-assign-card');
    if (!btn) return;

    const studentId = btn.dataset.studentId;
    const blockId   = btn.dataset.blockId;
    const card      = btn.closest('.picker-card');

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

    const fd = new FormData();
    fd.append('action',     'assign_ajax');
    fd.append('student_id', studentId);
    fd.append('block_id',   blockId);

    fetch('../../php/assign_student_to_block.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                // Remove card from picker
                card.style.transition = 'opacity .25s';
                card.style.opacity = '0';
                setTimeout(() => card.remove(), 250);

                // Add row to assigned table
                addAssignedRow(data.student, data.student_id);

                // Update capacity bar
                updateCapacityUI(data.current, data.max);

                // Recount picker
                filterStudents();
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-plus"></i> Assign';
                alert(data.msg || 'Failed to assign student.');
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-plus"></i> Assign';
            alert('Network error. Please try again.');
        });
});
