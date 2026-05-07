/* admin_classes.js */

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

window.addEventListener('click', function(e) {
    ['formModal','studentsModal'].forEach(id => {
        const m = document.getElementById(id);
        if (e.target === m) m.style.display = 'none';
    });
});

/* ── Course → Subject checklist ─────────────────────── */

function loadCourseSubjects() {
    const courseId = document.getElementById('modal_course_select').value;
    const wrap     = document.getElementById('subject_checklist_wrap');
    const list     = document.getElementById('subject_checklist');
    const hiddenId = document.getElementById('form_subject_id');

    hiddenId.value = '';
    list.innerHTML = '';

    if (!courseId) { wrap.style.display = 'none'; return; }

    const data = SUBJECTS_BY_COURSE[courseId] || SUBJECTS_BY_COURSE[0] || {};
    if (!Object.keys(data).length) {
        list.innerHTML = '<p style="color:var(--text-label);font-size:.85rem;padding:.5rem 0;">No subjects found for this course.</p>';
        wrap.style.display = 'block';
        return;
    }

    Object.keys(data).sort((a,b) => +a - +b).forEach(yr => {
        const yrLabel = document.createElement('div');
        yrLabel.className = 'checklist-year-label';
        yrLabel.textContent = YEAR_LABELS[yr] || ('Year ' + yr);
        list.appendChild(yrLabel);

        Object.keys(data[yr]).forEach(sem => {
            const semLabel = document.createElement('div');
            semLabel.className = 'checklist-sem-label';
            semLabel.textContent = SEM_LABELS[sem] || sem;
            list.appendChild(semLabel);

            data[yr][sem].forEach(subj => {
                const row = document.createElement('label');
                row.className = 'checklist-row';
                row.innerHTML = `
                    <input type="radio" name="_subject_radio" value="${subj.subject_id}"
                           onchange="document.getElementById('form_subject_id').value=this.value">
                    <span class="checklist-code">${subj.subject_code}</span>
                    <span class="checklist-name">${subj.subject_name}</span>
                    <span class="checklist-units">${subj.units} u</span>
                `;
                list.appendChild(row);
            });
        });
    });

    wrap.style.display = 'block';
}

/* ── Day checkboxes helpers ─────────────────────────── */

function setDayCheckboxes(dayStr) {
    document.querySelectorAll('.day-cb').forEach(cb => {
        cb.checked = dayStr && dayStr.includes(cb.value);
    });
}

function getSelectedDays() {
    return Array.from(document.querySelectorAll('.day-cb:checked')).map(cb => cb.value).join('');
}

/* ── Open Add modal ─────────────────────────────────── */

/* ── Form submit guard ──────────────────────────────── */

document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('#formModal form').addEventListener('submit', function(e) {
        const subjectId = document.getElementById('form_subject_id').value;
        if (!subjectId) {
            e.preventDefault();
            alert('Please select a subject before submitting.');
        }
    });
});

function openAdd() {
    document.getElementById('formModalTitle').textContent = 'Add Class';
    document.getElementById('formSubmitBtn').textContent  = 'Create Class';
    document.getElementById('form_action').value          = 'add';
    document.getElementById('form_class_id').value        = '';
    document.getElementById('form_subject_id').value      = '';

    ['section','faculty_id','schedule_time','room'].forEach(f => {
        const el = document.getElementById('form_' + f);
        if (el) el.value = '';
    });

    const y = new Date().getFullYear();
    document.getElementById('form_school_year').value = CURRENT_SCHOOL_YEAR;
    document.getElementById('form_semester').value    = CURRENT_SEMESTER;
    document.getElementById('form_max_slots').value   = 40;
    document.getElementById('form_status').value      = 'open';
    document.getElementById('form_assign_block_id').value = '';

    setDayCheckboxes('');

    // Reset course selector & checklist
    document.getElementById('modal_course_select').value = '';
    document.getElementById('subject_checklist_wrap').style.display = 'none';
    document.getElementById('subject_checklist').innerHTML = '';
    document.getElementById('subject_locked_note').style.display = 'none';
    document.getElementById('modal_course_select').disabled = false;
    document.getElementById('course_selector_group').style.opacity = '1';

    document.getElementById('formModal').style.display = 'block';
}

/* ── Open Edit modal ────────────────────────────────── */

function openEdit(raw) {
    const c = JSON.parse(raw);
    document.getElementById('formModalTitle').textContent = 'Edit Class';
    document.getElementById('formSubmitBtn').textContent  = 'Save Changes';
    document.getElementById('form_action').value          = 'edit';
    document.getElementById('form_class_id').value        = c.class_id;
    document.getElementById('form_subject_id').value      = c.subject_id;

    ['section','faculty_id','school_year','semester','schedule_time','room','max_slots','status'].forEach(f => {
        const el = document.getElementById('form_' + f);
        if (el) el.value = c[f] ?? '';
    });

    setDayCheckboxes(c.schedule_day || '');

    // Populate block assignment; restriction follows block selection
    const assignedBlocks = BLOCK_ASSIGNMENTS[c.class_id] || [];
    const blockSel = document.getElementById('form_assign_block_id');
    blockSel.value = assignedBlocks.length ? assignedBlocks[0] : '';

    // Show locked note, hide course selector
    document.getElementById('subject_locked_note').style.display = 'block';
    document.getElementById('modal_course_select').disabled = true;
    document.getElementById('course_selector_group').style.opacity = '0.5';
    document.getElementById('subject_checklist_wrap').style.display = 'none';

    document.getElementById('formModal').style.display = 'block';
}


/* ── View Students modal ────────────────────────────── */

function viewStudents(classId, subjectCode) {
    document.getElementById('studentsModalTitle').textContent = `Enrolled Students - ${subjectCode}`;
    document.getElementById('studentsModal').style.display = 'block';
    document.getElementById('studentsContent').innerHTML = `
        <div style="text-align:center;padding:2rem;color:var(--text-label);">
            <i class="fa-solid fa-spinner fa-spin" style="font-size:2rem;"></i>
            <p style="margin-top:1rem;">Loading students...</p>
        </div>`;

    fetch(`../../php/admin_class_students.php?action=get_students&class_id=${classId}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) displayStudents(data.students, classId);
            else document.getElementById('studentsContent').innerHTML =
                `<div style="text-align:center;padding:2rem;color:var(--red);">${data.message || 'Failed to load students'}</div>`;
        })
        .catch(() => {
            document.getElementById('studentsContent').innerHTML =
                `<div style="text-align:center;padding:2rem;color:var(--red);">Error loading students</div>`;
        });
}

function displayStudents(students, classId) {
    if (!students.length) {
        document.getElementById('studentsContent').innerHTML =
            `<div style="text-align:center;padding:2rem;color:var(--text-label);">No students enrolled in this class</div>`;
        return;
    }
    let html = `<div class="table-responsive"><table class="data-table"><thead><tr>
        <th>Student Number</th><th>Name</th><th>Email</th><th>Year Level</th><th>Status</th><th>Action</th>
    </tr></thead><tbody>`;
    students.forEach(s => {
        const name = `${s.last_name}, ${s.first_name} ${s.middle_name || ''}`;
        html += `<tr id="student-row-${s.enrollment_id}">
            <td>${s.student_number}</td><td>${name}</td><td>${s.email}</td>
            <td>${s.year_level}</td>
            <td><span class="badge ${s.status}">${s.status}</span></td>
            <td><button class="btn-icon danger" onclick="removeStudent(${s.enrollment_id},${classId},'${name}')">
                <i class="fa-solid fa-user-minus"></i></button></td>
        </tr>`;
    });
    html += '</tbody></table></div>';
    document.getElementById('studentsContent').innerHTML = html;
}

function removeStudent(enrollmentId, classId, studentName) {
    if (!confirm(`Remove ${studentName} from this class?`)) return;
    const fd = new FormData();
    fd.append('action','remove_student');
    fd.append('enrollment_id', enrollmentId);
    fd.append('class_id', classId);
    fetch('../../php/admin_class_students.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const row = document.getElementById(`student-row-${enrollmentId}`);
                if (row) row.remove();
                const tbody = document.querySelector('#studentsContent tbody');
                if (tbody && !tbody.children.length)
                    document.getElementById('studentsContent').innerHTML =
                        `<div style="text-align:center;padding:2rem;color:var(--text-label);">No students enrolled in this class</div>`;
                setTimeout(() => location.reload(), 800);
            } else alert(data.message || 'Failed to remove student');
        })
        .catch(() => alert('Error removing student'));
}
