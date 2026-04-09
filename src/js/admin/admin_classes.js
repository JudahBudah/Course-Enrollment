/* admin_classes.js — page-specific scripts */

/* ── Close modal ───────────────────────────────────────── */

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

window.addEventListener('click', function(e) {
    const m = document.getElementById('formModal');
    if (e.target === m) m.style.display = 'none';
    const sm = document.getElementById('studentsModal');
    if (e.target === sm) sm.style.display = 'none';
});

/* ── Filter subjects in the dropdown ──────────────────── */

function filterSubjects() {
    const deptFilter = document.getElementById('filter_dept').value.toLowerCase();
    const yearFilter = document.getElementById('filter_year').value;
    const semFilter  = document.getElementById('filter_sem').value.toLowerCase();
    const searchFilter = document.getElementById('filter_search').value.toLowerCase();
    const select     = document.getElementById('form_subject_id');
    const options    = select.querySelectorAll('option');

    let visibleCount = 0;

    options.forEach((option, index) => {
        if (index === 0) return; // skip placeholder

        const dept = (option.dataset.dept || '').toLowerCase();
        const year = option.dataset.year || '';
        const sem  = (option.dataset.sem  || '').toLowerCase();
        const code = (option.dataset.code || '').toLowerCase();
        const name = (option.dataset.name || '').toLowerCase();

        let show = true;
        if (deptFilter && !dept.includes(deptFilter)) show = false;
        if (yearFilter && year !== yearFilter)         show = false;
        if (semFilter  && sem  !== semFilter)          show = false;
        if (searchFilter && !code.includes(searchFilter) && !name.includes(searchFilter)) show = false;

        option.style.display = show ? '' : 'none';
        if (show) visibleCount++;
    });

    const countEl = document.getElementById('filter_count');
    if (deptFilter || yearFilter || semFilter || searchFilter) {
        countEl.textContent = `Showing ${visibleCount} subject${visibleCount !== 1 ? 's' : ''}`;
        countEl.style.color = visibleCount === 0 ? 'var(--red)' : 'var(--text-label)';
    } else {
        countEl.textContent = 'Showing all subjects';
        countEl.style.color = 'var(--text-label)';
    }
}

/* ── Reset modal filters ───────────────────────────────── */

function resetModalFilters() {
    document.getElementById('filter_dept').value = '';
    document.getElementById('filter_year').value = '';
    document.getElementById('filter_sem').value  = '';
    document.getElementById('filter_search').value = '';
    filterSubjects();
}

/* ── Toggle department select ──────────────────────────── */

function toggleDepartmentSelect() {
    const specificRadio = document.querySelector('input[name="availability_type"][value="specific"]');
    const deptGroup = document.getElementById('specific_dept_group');
    const deptSelect = document.getElementById('form_specific_department');
    
    if (specificRadio && specificRadio.checked) {
        deptGroup.style.display = 'block';
        deptSelect.required = true;
    } else {
        deptGroup.style.display = 'none';
        deptSelect.required = false;
        deptSelect.value = '';
    }
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
    
    // Re-enable subject selection when adding
    const subjectSelect = document.getElementById('form_subject_id');
    const subjectLockedNote = document.getElementById('subject_locked_note');
    const filterBox = document.querySelector('.subject-filter-box');
    
    if (subjectSelect) {
        subjectSelect.disabled = false;
        subjectSelect.style.opacity = '1';
        subjectSelect.style.cursor = 'pointer';
    }
    if (subjectLockedNote) {
        subjectLockedNote.style.display = 'none';
    }
    if (filterBox) {
        filterBox.style.opacity = '1';
        filterBox.style.pointerEvents = 'auto';
    }
    
    // Reset availability type
    document.querySelector('input[name="availability_type"][value="all"]').checked = true;
    toggleDepartmentSelect();

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
    
    // Disable subject selection when editing
    const subjectSelect = document.getElementById('form_subject_id');
    const subjectLockedNote = document.getElementById('subject_locked_note');
    const filterBox = document.querySelector('.subject-filter-box');
    
    if (subjectSelect) {
        subjectSelect.disabled = true;
        subjectSelect.style.opacity = '0.6';
        subjectSelect.style.cursor = 'not-allowed';
    }
    if (subjectLockedNote) {
        subjectLockedNote.style.display = 'block';
    }
    if (filterBox) {
        filterBox.style.opacity = '0.6';
        filterBox.style.pointerEvents = 'none';
    }

    resetModalFilters();
    document.getElementById('formModal').style.display = 'block';
}

/* ── View Students modal ───────────────────────────────── */

function viewStudents(classId, subjectCode) {
    document.getElementById('studentsModalTitle').textContent = `Enrolled Students - ${subjectCode}`;
    document.getElementById('studentsModal').style.display = 'block';
    
    // Show loading
    document.getElementById('studentsContent').innerHTML = `
        <div style="text-align:center;padding:2rem;color:var(--text-label);">
            <i class="fa-solid fa-spinner fa-spin" style="font-size:2rem;"></i>
            <p style="margin-top:1rem;">Loading students...</p>
        </div>
    `;
    
    // Fetch students
    fetch(`../../php/admin_class_students.php?action=get_students&class_id=${classId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayStudents(data.students, classId);
            } else {
                document.getElementById('studentsContent').innerHTML = `
                    <div style="text-align:center;padding:2rem;color:var(--red);">
                        <i class="fa-solid fa-exclamation-circle" style="font-size:2rem;"></i>
                        <p style="margin-top:1rem;">${data.message || 'Failed to load students'}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('studentsContent').innerHTML = `
                <div style="text-align:center;padding:2rem;color:var(--red);">
                    <i class="fa-solid fa-exclamation-circle" style="font-size:2rem;"></i>
                    <p style="margin-top:1rem;">Error loading students</p>
                </div>
            `;
        });
}

function displayStudents(students, classId) {
    if (students.length === 0) {
        document.getElementById('studentsContent').innerHTML = `
            <div style="text-align:center;padding:2rem;color:var(--text-label);">
                <i class="fa-solid fa-users" style="font-size:2rem;"></i>
                <p style="margin-top:1rem;">No students enrolled in this class</p>
            </div>
        `;
        return;
    }
    
    let html = `
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student Number</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Year Level</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    students.forEach(student => {
        const fullName = `${student.last_name}, ${student.first_name} ${student.middle_name || ''}`;
        html += `
            <tr id="student-row-${student.enrollment_id}">
                <td>${student.student_number}</td>
                <td>${fullName}</td>
                <td>${student.email}</td>
                <td>${student.year_level}</td>
                <td><span class="badge ${student.status}">${student.status}</span></td>
                <td>
                    <button class="btn-icon danger" title="Remove Student" 
                            onclick="removeStudent(${student.enrollment_id}, ${classId}, '${fullName}')">
                        <i class="fa-solid fa-user-minus"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
        </div>
    `;
    
    document.getElementById('studentsContent').innerHTML = html;
}

function removeStudent(enrollmentId, classId, studentName) {
    if (!confirm(`Remove ${studentName} from this class?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'remove_student');
    formData.append('enrollment_id', enrollmentId);
    formData.append('class_id', classId);
    
    fetch('../../php/admin_class_students.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove row from table
            const row = document.getElementById(`student-row-${enrollmentId}`);
            if (row) row.remove();
            
            // Check if table is now empty
            const tbody = document.querySelector('#studentsContent tbody');
            if (tbody && tbody.children.length === 0) {
                document.getElementById('studentsContent').innerHTML = `
                    <div style="text-align:center;padding:2rem;color:var(--text-label);">
                        <i class="fa-solid fa-users" style="font-size:2rem;"></i>
                        <p style="margin-top:1rem;">No students enrolled in this class</p>
                    </div>
                `;
            }
            
            // Reload page to update enrollment count
            setTimeout(() => location.reload(), 1000);
        } else {
            alert(data.message || 'Failed to remove student');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error removing student');
    });
}