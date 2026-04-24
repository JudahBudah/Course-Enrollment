<?php
session_start();
include("../../php/connection.php");
include("../../php/functions.php");

include("../../php/admin_functions.php");

$user_data = check_login($con);

$profile_src = !empty($user_data['profile_photo'])
    ? '../../' . $user_data['profile_photo']
    : '../../uploads/default.jpg';

$curriculum_url = '';
$course_info    = get_course_info($con, $user_data['course'] ?? '');
$curriculum_url = $course_info['curriculum_url'] ?? '';

// System current semester & school year
$cur_semester   = get_setting($con, 'current_semester', '');
$cur_school_year = get_setting($con, 'current_school_year', '');

$view_mode = $_GET['view'] ?? 'current'; // 'current' or 'past'

// Remove unused variables
unset($status_filter, $sem_filter);

// Fetch all enrolled subjects based on view mode
if ($view_mode === 'past') {
    // Past = completed enrollments OR confirmed enrollments from a previous semester/year
    if ($cur_semester && $cur_school_year) {
        $stmt = mysqli_prepare($con, "
            SELECT s.subject_id, s.subject_code, s.subject_name, s.units,
                   s.lecture_hours, s.lab_hours, s.prerequisite,
                   c.class_id, c.section, c.schedule_day, c.schedule_time, c.room,
                   c.semester, c.school_year, c.enrolled_count, c.max_slots,
                   CONCAT(f.first_name, ' ', f.last_name) AS faculty_name,
                   f.email AS faculty_email, f.department AS faculty_dept
            FROM enrollments e
            JOIN classes c ON e.class_id = c.class_id
            JOIN subjects s ON c.subject_id = s.subject_id
            LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
            WHERE e.student_id = ?
              AND (
                e.status = 'completed'
                OR (e.status = 'confirmed' AND (c.semester != ? OR c.school_year != ?))
              )
            ORDER BY c.school_year DESC, FIELD(c.semester,'1st','2nd','summer'), s.subject_code
        ");
        mysqli_stmt_bind_param($stmt, "iss", $user_data['student_id'], $cur_semester, $cur_school_year);
    } else {
        $stmt = mysqli_prepare($con, "
            SELECT s.subject_id, s.subject_code, s.subject_name, s.units,
                   s.lecture_hours, s.lab_hours, s.prerequisite,
                   c.class_id, c.section, c.schedule_day, c.schedule_time, c.room,
                   c.semester, c.school_year, c.enrolled_count, c.max_slots,
                   CONCAT(f.first_name, ' ', f.last_name) AS faculty_name,
                   f.email AS faculty_email, f.department AS faculty_dept
            FROM enrollments e
            JOIN classes c ON e.class_id = c.class_id
            JOIN subjects s ON c.subject_id = s.subject_id
            LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
            WHERE e.student_id = ? AND e.status IN ('completed', 'confirmed')
            ORDER BY c.school_year DESC, FIELD(c.semester,'1st','2nd','summer'), s.subject_code
        ");
        mysqli_stmt_bind_param($stmt, "i", $user_data['student_id']);
    }
} elseif ($cur_semester && $cur_school_year) {
    $stmt = mysqli_prepare($con, "
        SELECT s.subject_id, s.subject_code, s.subject_name, s.units,
               s.lecture_hours, s.lab_hours, s.prerequisite,
               c.class_id, c.section, c.schedule_day, c.schedule_time, c.room,
               c.semester, c.school_year, c.enrolled_count, c.max_slots,
               CONCAT(f.first_name, ' ', f.last_name) AS faculty_name,
               f.email AS faculty_email, f.department AS faculty_dept
        FROM enrollments e
        JOIN classes c ON e.class_id = c.class_id
        JOIN subjects s ON c.subject_id = s.subject_id
        LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
        WHERE e.student_id = ? AND e.status = 'confirmed'
          AND c.semester = ? AND c.school_year = ?
        ORDER BY FIELD(c.semester,'1st','2nd','summer'), s.subject_code
    ");
    mysqli_stmt_bind_param($stmt, "iss", $user_data['student_id'], $cur_semester, $cur_school_year);
} else {
    $stmt = mysqli_prepare($con, "
        SELECT s.subject_id, s.subject_code, s.subject_name, s.units,
               s.lecture_hours, s.lab_hours, s.prerequisite,
               c.class_id, c.section, c.schedule_day, c.schedule_time, c.room,
               c.semester, c.school_year, c.enrolled_count, c.max_slots,
               CONCAT(f.first_name, ' ', f.last_name) AS faculty_name,
               f.email AS faculty_email, f.department AS faculty_dept
        FROM enrollments e
        JOIN classes c ON e.class_id = c.class_id
        JOIN subjects s ON c.subject_id = s.subject_id
        LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
        WHERE e.student_id = ? AND e.status = 'confirmed'
        ORDER BY FIELD(c.semester,'1st','2nd','summer'), s.subject_code
    ");
    mysqli_stmt_bind_param($stmt, "i", $user_data['student_id']);
}
mysqli_stmt_execute($stmt);
$result   = mysqli_stmt_get_result($stmt);
$subjects = [];
while ($row = mysqli_fetch_assoc($result)) $subjects[] = $row;
mysqli_stmt_close($stmt);

$total_units = array_sum(array_column($subjects, 'units'));

$sem_labels = ['1st' => '1st Semester', '2nd' => '2nd Semester', 'summer' => 'Summer'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Subjects</title>
    <link rel="icon" href="../../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <script>
        (function() {
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark-mode');
        }
        })();
    </script>
    <link rel="stylesheet" href="../../css/student/student_main.css">
    <link rel="stylesheet" href="../../css/student/student_my_subjects.css">
</head>
<body>
<header>
    <div class="nav-section">
        <button class="nav-button" id="navButton">
            <i class="fa-solid fa-bars trans-bars" id="trans-bars"></i>
        </button>
        <div class="logo-container">
            <img src="../../assets/plm-logo.png" alt="PLM Logo" loading="lazy">
            <div class="title-container">
                <div class="logo-title">PAMANTASAN NG LUNGSOD NG MAYNILA</div>
                <div class="logo-sub">University of the City of Manila</div>
            </div>
        </div>
        <div class="acc-display-container">
            <div class="acc-name"><?php echo htmlspecialchars(trim(($user_data['first_name'] ?? '') . ' ' . ($user_data['last_name'] ?? ''))); ?></div>
            <div class="acc-img">
                <img src="<?php echo htmlspecialchars($profile_src); ?>" alt="Profile">
            </div>
        </div>
    </div>

    <nav class="main-nav" id="navMenu">
        <div class="nav-wrapper">
            <ul class="main-ul">
                <li><a href="student_home.php"><i class="fa-solid fa-house"></i><div class="li-name">Dashboard</div></a></li>
                <li><a href="student_subjects.php"><i class="fa-solid fa-calendar"></i><div class="li-name">Schedule</div></a></li>
                <li><a href="student_enrollment.php"><i class="fa-solid fa-id-card"></i><div class="li-name">Enrollment</div></a></li>
                <li><a href="student_grades.php"><i class="fa-solid fa-book"></i><div class="li-name">Grades</div></a></li>
                <li><a href="student_my_subjects.php" class="active"><i class="fa-solid fa-layer-group"></i><div class="li-name">My Subjects</div></a></li>
                <li class="course-dropdown">
                    <a href="#" id="acad-dropdown">
                        <i class="fa-solid fa-school"></i>
                        <div class="li-name chev-space">Academics <i class="fa-solid fa-chevron-down"></i></div>
                    </a>
                    <div class="acad-dropdown-menu" id="acad-dropdown-menu">
                        <ul>
                            <li><a href="student_info-program.php">Program</a></li>
                            <li><a href="student_info-college.php">College</a></li>
                            <?php if ($curriculum_url): ?><li><a href="<?php echo htmlspecialchars($curriculum_url); ?>" target="_blank">Curriculum</a></li><?php endif; ?>
                        </ul>
                    </div>
                </li>
                <li><a href="student_account.php"><i class="fa-solid fa-user"></i><div class="li-name">Profile</div></a></li>
                <li><a href="../../php/student_logout.php" class="logout-bg"><i class="fa-solid fa-arrow-right-from-bracket"></i><div class="li-name">Logout</div></a></li>
            </ul>
        </div>
        <div class="drk-mode-container">
            <div class="drk-label">
                <i class="fa-solid fa-moon" id="modeIcon"></i>
                <span class="li-name" id="modeLabel">Dark Mode</span>
            </div>
            <div class="toggle-track li-name" id="toggleTrack"><div class="toggle-thumb"></div></div>
        </div>
    </nav>
</header>

<div class="main-flex">
    <div class="spacer"></div>
    <main>

        <div class="ms-header">
            <div>
                <h1>My Subjects</h1>
                <p><?php echo htmlspecialchars($user_data['course'] ?? ''); ?> &bull; <?php echo count($subjects); ?> subject<?php echo count($subjects) !== 1 ? 's' : ''; ?> &bull; <?php echo $total_units; ?> units</p>
            </div>
            <div class="ms-options">
                <a href="?view=current" class="ms-opt <?php echo $view_mode === 'current' ? 'ms-opt--current active' : ''; ?>">
                    <i class="fa-solid fa-chalkboard"></i> Current
                </a>
                <a href="?view=past" class="ms-opt <?php echo $view_mode === 'past' ? 'ms-opt--past active' : ''; ?>">
                    <i class="fa-solid fa-clock-rotate-left"></i> Past
                </a>
            </div>
        </div>

        <?php if (empty($subjects)): ?>
        <div class="ms-empty">
            <i class="fa-solid fa-layer-group"></i>
            <p>No <?php echo $view_mode === 'past' ? 'past' : 'current'; ?> subjects found.</p>
            <?php if ($view_mode === 'current'): ?><a href="student_enrollment.php">Go to Enrollment</a><?php endif; ?>
        </div>
        <?php else:
    // Group by school year + semester
    $grouped = [];
    foreach ($subjects as $subj) {
        $key = $subj['school_year'] . '||' . $subj['semester'];
        $grouped[$key][] = $subj;
    }
?>

<div class="ms-sections">
<?php foreach ($grouped as $key => $group):
    [$sy, $sem] = explode('||', $key);
    $sem_label  = $sem_labels[$sem] ?? $sem;
    $grp_units  = array_sum(array_column($group, 'units'));
?>
    <div class="ms-section">
        <div class="ms-section-label">
            <div class="ms-section-label-left">
                <span class="ms-section-sem"><?php echo htmlspecialchars($sem_label); ?></span>
                <span class="ms-section-sy">S.Y. <?php echo htmlspecialchars($sy); ?></span>
            </div>
            <span class="ms-section-meta">
                <?php echo count($group); ?> subject<?php echo count($group) !== 1 ? 's' : ''; ?>
                &bull;
                <?php echo $grp_units; ?> units
            </span>
        </div>

        <div class="ms-grid">
            <?php foreach ($group as $subj):
                $hours = $subj['lecture_hours'] + $subj['lab_hours'];
                $data  = htmlspecialchars(json_encode($subj), ENT_QUOTES);
            ?>
            <div class="ms-card" onclick="openSubject('<?php echo $data; ?>')">
                <div class="ms-card-top">
                    <span class="ms-code"><?php echo htmlspecialchars($subj['subject_code']); ?></span>
                    <?php if ($subj['lab_hours'] > 0): ?><span class="ms-tag-lab">Lab</span><?php endif; ?>
                </div>
                <div class="ms-name"><?php echo htmlspecialchars($subj['subject_name']); ?></div>
                <div class="ms-meta">
                    <span><i class="fa-solid fa-graduation-cap"></i> <?php echo $subj['units']; ?> units</span>
                    <span><i class="fa-solid fa-clock"></i> <?php echo $hours; ?> hrs</span>
                </div>
                <div class="ms-card-hint"><i class="fa-solid fa-circle-info"></i> Click for details</div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>
</div>

<?php endif; ?>

    </main>
</div>

<!-- Subject Detail Modal -->
<div id="msModal" class="ms-overlay" style="display:none;">
    <div class="ms-modal">
        <div class="ms-modal-header">
            <div>
                <div class="ms-modal-code" id="mCode"></div>
                <div class="ms-modal-name" id="mName"></div>
            </div>
            <button class="ms-modal-close" onclick="closeModal('msModal')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="ms-modal-body">
            <div class="ms-info-grid" id="mGrid"></div>
        </div>
    </div>
</div>

<!-- Classmates Modal -->
<div id="cmModal" class="cm-overlay" style="display:none;">
    <div class="cm-box">
        <div class="cm-header">
            <div><div class="cm-title" id="cmTitle"></div><div class="cm-sub" id="cmSub"></div></div>
            <button class="cm-close" onclick="closeModal('cmModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="cm-body" id="cmBody"></div>
    </div>
</div>

<!-- Profile Modal -->
<div id="pmModal" class="cm-overlay" style="display:none;">
    <div class="cm-box cm-profile-box">
        <div class="cm-header">
            <div class="cm-title">Student Profile</div>
            <button class="cm-close" onclick="closeModal('pmModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="cm-body" id="pmBody"></div>
    </div>
</div>

<script src="../../js/student/student_main.js"></script>
<script>
function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

['msModal','cmModal','pmModal'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
});

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') ['msModal','cmModal','pmModal'].forEach(closeModal);
});

function openSubject(raw) {
    const s = JSON.parse(raw);
    document.getElementById('mCode').textContent = s.subject_code;
    document.getElementById('mName').textContent = s.subject_name;

    const semLabels = {'1st':'1st Semester','2nd':'2nd Semester','summer':'Summer'};
    const hours = parseFloat(s.lecture_hours||0) + parseFloat(s.lab_hours||0);
    const slotsPct = s.max_slots > 0 ? Math.round((s.enrolled_count / s.max_slots) * 100) : 0;

    const rows = [
        ['fa-graduation-cap',  'Units',         s.units + ' units'],
        ['fa-clock',           'Hours',         hours + ' hrs (Lec: ' + s.lecture_hours + ' / Lab: ' + s.lab_hours + ')'],
        ['fa-calendar-alt',    'Semester',      semLabels[s.semester] || s.semester],
        ['fa-school',          'School Year',   s.school_year],
        ['fa-chalkboard-user', 'Faculty',       s.faculty_name || 'TBA'],
        ['fa-envelope',        'Faculty Email', s.faculty_email || 'N/A'],
        ['fa-users-rectangle', 'Section',       s.section || 'TBA'],
        ['fa-calendar-days',   'Schedule',      ((s.schedule_day || '') + (s.schedule_time ? ' · ' + s.schedule_time : '')) || 'TBA'],
        ['fa-door-open',       'Room',          s.room || 'TBA'],
        ['fa-users',           'Enrollment',    s.enrolled_count + ' / ' + s.max_slots + ' students'],
        ['fa-link',            'Prerequisite',  s.prerequisite || 'None'],
    ];

    let html = '';
    rows.forEach(([icon, label, val]) => {
        html += `<div class="ms-info-row"><div class="ms-info-label"><i class="fa-solid ${icon}"></i> ${label}</div><div class="ms-info-val">${val}</div></div>`;
    });

    html += `<div class="ms-bar-wrap"><div class="ms-bar-track"><div class="ms-bar-fill" style="width:${slotsPct}%"></div></div><span>${slotsPct}% full</span></div>`;
    html += `<button class="ms-classmates-btn" onclick="openClassmates(${s.class_id}, '${s.subject_code} — ${s.subject_name.replace(/'/g, "\\'")}')"><i class="fa-solid fa-users"></i> View Classmates</button>`;

    document.getElementById('mGrid').innerHTML = html;
    document.getElementById('msModal').style.display = 'flex';
}

function openClassmates(classId, subject) {
    document.getElementById('cmTitle').textContent = 'Classmates';
    document.getElementById('cmSub').textContent   = subject;
    document.getElementById('cmBody').innerHTML    = '<div class="cm-loading"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</div>';
    document.getElementById('cmModal').style.display = 'flex';

    fetch(`../../php/student_classmates.php?action=classmates&class_id=${classId}`)
        .then(r => r.json())
        .then(data => {
            const body = document.getElementById('cmBody');
            if (!data.success) { body.innerHTML = `<div class="cm-loading">${data.message}</div>`; return; }
            if (!data.classmates.length) { body.innerHTML = '<div class="cm-loading">No other students enrolled yet.</div>'; return; }
            const grid = document.createElement('div');
            grid.className = 'cm-grid';
            data.classmates.forEach(s => {
                const card = document.createElement('div');
                card.className = 'cm-card';
                card.innerHTML = `<img class="cm-avatar" src="${s.profile_photo}" onerror="this.src='../../uploads/default.jpg'"><div class="cm-name">${s.full_name}</div><div class="cm-num">${s.student_number}</div>`;
                card.addEventListener('click', () => openProfile(s.student_id, classId));
                grid.appendChild(card);
            });
            body.innerHTML = `<p style="font-size:.78rem;color:var(--text-label);margin-bottom:.75rem;">${data.classmates.length} classmate${data.classmates.length!==1?'s':''} — click a card to view profile</p>`;
            body.appendChild(grid);
        })
        .catch(() => { document.getElementById('cmBody').innerHTML = '<div class="cm-loading">Error loading classmates.</div>'; });
}

function openProfile(studentId, classId) {
    document.getElementById('pmBody').innerHTML = '<div class="cm-loading"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</div>';
    document.getElementById('pmModal').style.display = 'flex';
    fetch(`../../php/student_classmates.php?action=profile&student_id=${studentId}&class_id=${classId}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) { document.getElementById('pmBody').innerHTML = `<div class="cm-loading">${data.message}</div>`; return; }
            const p = data.profile;
            const yr = {1:'1st Year',2:'2nd Year',3:'3rd Year',4:'4th Year'}[p.year_level] || p.year_level || 'N/A';
            document.getElementById('pmBody').innerHTML = `<div class="pm-card"><img class="pm-avatar" src="${p.profile_photo}" onerror="this.src='../../uploads/default.jpg'"><div class="pm-name">${p.full_name}</div><div class="pm-num">${p.student_number}</div><div class="pm-fields"><div class="pm-field"><label>Course</label><span>${p.course||'N/A'}</span></div><div class="pm-field"><label>Year Level</label><span>${yr}</span></div><div class="pm-field"><label>College</label><span>${p.college||'N/A'}</span></div></div></div>`;
        })
        .catch(() => { document.getElementById('pmBody').innerHTML = '<div class="cm-loading">Error loading profile.</div>'; });
}
</script>
</body>
</html>
