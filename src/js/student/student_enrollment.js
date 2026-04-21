// Two-row curriculum nav: Year tabs + Semester tabs
const yearTabs = document.querySelectorAll('.year-tab');
const semTabs  = document.querySelectorAll('.sem-tab');

yearTabs.forEach(tab => {
    tab.addEventListener('click', function () {
        const yr = this.dataset.year;

        // Activate year tab
        yearTabs.forEach(t => t.classList.remove('active'));
        this.classList.add('active');

        // Show only sem tabs for this year, hide others
        semTabs.forEach(t => {
            t.style.display = t.dataset.year === yr ? '' : 'none';
            t.classList.remove('active');
        });

        // Activate first sem tab of this year and show its panel
        const firstSem = document.querySelector(`.sem-tab[data-year="${yr}"]`);
        if (firstSem) {
            firstSem.classList.add('active');
            showPanel(firstSem.dataset.target);
        }
    });
});

semTabs.forEach(tab => {
    tab.addEventListener('click', function () {
        semTabs.forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        showPanel(this.dataset.target);
    });
});

function showPanel(id) {
    document.querySelectorAll('.curric-panel').forEach(p => p.classList.remove('active'));
    const panel = document.getElementById(id);
    if (panel) panel.classList.add('active');
}

// Schedule selection modal
const modal      = document.getElementById('schedModal');
const modalClose = document.getElementById('modalClose');
const modalCode  = document.getElementById('modalSubjectCode');
const modalName  = document.getElementById('modalSubjectName');
const modalList  = document.getElementById('modalClassList');

document.querySelectorAll('.btn-select-sched').forEach(btn => {
    btn.addEventListener('click', function () {
        const classes = JSON.parse(this.dataset.classes);
        modalCode.textContent = this.dataset.subjectCode;
        modalName.textContent = this.dataset.subjectName;
        modalList.innerHTML = '';

        classes.forEach(cls => {
            const slotsLeft = cls.max_slots - cls.enrolled_count;
            const isFull    = slotsLeft <= 0;
            const semLabel  = {'1st':'1st Semester','2nd':'2nd Semester','summer':'Summer'}[cls.semester] || cls.semester || '';
            const card      = document.createElement('div');
            card.className  = 'sched-option' + (isFull ? ' sched-full' : '');
            card.innerHTML  = `
                <div class="sched-option-info">
                    <div class="sched-option-section">${cls.section}</div>
                    <div class="sched-option-details">
                        <span><i class="fa-solid fa-clock"></i> ${cls.schedule_day} ${cls.schedule_time}</span>
                        ${cls.room ? `<span><i class="fa-solid fa-door-open"></i> ${cls.room}</span>` : ''}
                        <span><i class="fa-solid fa-chalkboard-user"></i> ${cls.faculty_name || 'TBA'}</span>
                        ${cls.semester ? `<span><i class="fa-solid fa-calendar"></i> ${semLabel}${cls.school_year ? ' ' + cls.school_year : ''}</span>` : ''}
                    </div>
                </div>
                <div class="sched-option-slots ${isFull ? 'slots-full' : 'slots-open'}">
                    ${isFull ? 'Full' : slotsLeft + ' slot' + (slotsLeft !== 1 ? 's' : '') + ' left'}
                </div>
                ${!isFull ? `<button class="btn-enroll-class" data-class-id="${cls.class_id}"><i class="fa-solid fa-plus"></i> Enroll</button>` : ''}
            `;
            modalList.appendChild(card);
        });

        modalList.querySelectorAll('.btn-enroll-class').forEach(enrollBtn => {
            enrollBtn.addEventListener('click', function () {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '../../php/student_enrollment_action.php';
                form.innerHTML = `
                    <input type="hidden" name="action" value="self_enroll">
                    <input type="hidden" name="class_id" value="${this.dataset.classId}">
                `;
                document.body.appendChild(form);
                form.submit();
            });
        });

        modal.style.display = 'flex';
    });
});

modalClose.addEventListener('click', () => modal.style.display = 'none');
modal.addEventListener('click', e => { if (e.target === modal) modal.style.display = 'none'; });
document.addEventListener('keydown', e => { if (e.key === 'Escape') modal.style.display = 'none'; });

// Irregular subject search filter
const irregularSearch = document.getElementById('irregularSearch');
if (irregularSearch) {
    irregularSearch.addEventListener('input', function () {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#irregularTable tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
}

// Curriculum search filter
const curricSearch = document.getElementById('curricSearch');
if (curricSearch) {
    curricSearch.addEventListener('input', function () {
        const q = this.value.toLowerCase();
        document.querySelectorAll('.curric-panel table tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
        // Show all panels when searching
        if (q) {
            document.querySelectorAll('.curric-panel').forEach(p => p.classList.add('active'));
        }
    });
}
