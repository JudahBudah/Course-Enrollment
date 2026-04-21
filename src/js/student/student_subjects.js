/* student_subjects.js — classmates & profile modals */

const cmModal  = document.getElementById('classmatesModal');
const pmModal  = document.getElementById('profileModal');
const cmBody   = document.getElementById('cmBody');
const pmBody   = document.getElementById('pmBody');
const cmTitle  = document.getElementById('cmTitle');
const cmSub    = document.getElementById('cmSub');

document.getElementById('cmClose').addEventListener('click', () => cmModal.style.display = 'none');
document.getElementById('pmClose').addEventListener('click', () => pmModal.style.display = 'none');

cmModal.addEventListener('click', e => { if (e.target === cmModal) cmModal.style.display = 'none'; });
pmModal.addEventListener('click', e => { if (e.target === pmModal) pmModal.style.display = 'none'; });
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { cmModal.style.display = 'none'; pmModal.style.display = 'none'; }
});

const YEAR_LABELS = { 1:'1st Year', 2:'2nd Year', 3:'3rd Year', 4:'4th Year' };

document.querySelectorAll('.btn-classmates').forEach(btn => {
    btn.addEventListener('click', function () {
        const classId = this.dataset.classId;
        const subject = this.dataset.subject;

        cmTitle.textContent = 'Classmates';
        cmSub.textContent   = subject;
        cmBody.innerHTML    = '<div class="cm-loading"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</div>';
        cmModal.style.display = 'flex';

        fetch(`../../php/student_classmates.php?action=classmates&class_id=${classId}`)
            .then(r => r.json())
            .then(data => {
                if (!data.success) { cmBody.innerHTML = `<div class="cm-loading">${data.message}</div>`; return; }
                if (!data.classmates.length) {
                    cmBody.innerHTML = '<div class="cm-loading">No other students enrolled in this class yet.</div>';
                    return;
                }
                const grid = document.createElement('div');
                grid.className = 'cm-grid';
                data.classmates.forEach(s => {
                    const card = document.createElement('div');
                    card.className = 'cm-card';
                    card.innerHTML = `
                        <img class="cm-avatar" src="${s.profile_photo}" alt="${s.full_name}" onerror="this.src='../../assets/test/student-profile.webp'">
                        <div class="cm-name">${s.full_name}</div>
                        <div class="cm-num">${s.student_number}</div>
                    `;
                    card.addEventListener('click', () => openProfile(s.student_id, classId));
                    grid.appendChild(card);
                });
                cmBody.innerHTML = `<p style="font-size:.78rem;color:var(--text-label);margin-bottom:.75rem;">${data.classmates.length} classmate${data.classmates.length !== 1 ? 's' : ''} — click a card to view profile</p>`;
                cmBody.appendChild(grid);
            })
            .catch(() => { cmBody.innerHTML = '<div class="cm-loading">Error loading classmates.</div>'; });
    });
});

function openProfile(studentId, classId) {
    pmBody.innerHTML = '<div class="cm-loading"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</div>';
    pmModal.style.display = 'flex';

    fetch(`../../php/student_classmates.php?action=profile&student_id=${studentId}&class_id=${classId}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) { pmBody.innerHTML = `<div class="cm-loading">${data.message}</div>`; return; }
            const p = data.profile;
            const yr = YEAR_LABELS[p.year_level] || p.year_level || 'N/A';
            pmBody.innerHTML = `
                <div class="pm-card">
                    <img class="pm-avatar" src="${p.profile_photo}" alt="${p.full_name}" onerror="this.src='../../assets/test/student-profile.webp'">
                    <div class="pm-name">${p.full_name}</div>
                    <div class="pm-num">${p.student_number}</div>
                    <div class="pm-fields">
                        <div class="pm-field"><label>Course</label><span>${p.course || 'N/A'}</span></div>
                        <div class="pm-field"><label>Year Level</label><span>${yr}</span></div>
                        <div class="pm-field"><label>College</label><span>${p.college || 'N/A'}</span></div>
                    </div>
                </div>
            `;
        })
        .catch(() => { pmBody.innerHTML = '<div class="cm-loading">Error loading profile.</div>'; });
}
