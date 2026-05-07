<?php
session_start();
include("../../php/connection.php");

if (!isset($_SESSION['faculty_id'])) { header("Location: faculty_login.php"); die; }
$faculty_id = (int)$_SESSION['faculty_id'];

$faculty = mysqli_fetch_assoc(mysqli_query($con,
    "SELECT * FROM faculty WHERE faculty_id = $faculty_id LIMIT 1"
));
if (!$faculty) { session_destroy(); header("Location: faculty_login.php"); die; }

// Classes assigned to this faculty - split into current and past
$current_classes = [];
$past_classes    = [];
$cq = mysqli_query($con,
    "SELECT c.class_id, c.section, c.semester, c.school_year, c.grades_finalized,
            s.subject_code, s.subject_name
     FROM classes c
     JOIN subjects s ON c.subject_id = s.subject_id
     WHERE c.faculty_id = $faculty_id
     ORDER BY c.grades_finalized ASC, s.subject_code, c.section"
);
while ($r = mysqli_fetch_assoc($cq)) {
    if ((int)$r['grades_finalized'] === 1) $past_classes[] = $r;
    else $current_classes[] = $r;
}
$classes = array_merge($current_classes, $past_classes);

$view_mode = $_GET['view'] ?? 'current'; // 'current' or 'past'
$display_classes = $view_mode === 'past' ? $past_classes : $current_classes;

$selected_class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : ($display_classes[0]['class_id'] ?? 0);
$selected_class = null;
foreach ($classes as $c) {
    if ($c['class_id'] == $selected_class_id) { $selected_class = $c; break; }
}
if ($selected_class_id && !$selected_class) { $selected_class_id = 0; }

// Search — use prepared statement with LIKE, no raw interpolation
$search = trim($_GET['search'] ?? '');

// Students enrolled in selected class
$students = [];
if ($selected_class_id) {
    if ($search) {
        $like = '%' . $search . '%';
        $q = mysqli_prepare($con,
            "SELECT s.student_number, s.first_name, s.last_name, s.middle_name,
                    s.email, s.course, s.year_level, e.status AS enroll_status
             FROM enrollments e
             JOIN students s ON e.student_id = s.student_id
             WHERE e.class_id = ?
               AND e.status IN ('ongoing','confirmed','dropped')
               AND e.enrollment_id = (
                   SELECT MAX(e2.enrollment_id) FROM enrollments e2
                   WHERE e2.student_id = e.student_id
                     AND e2.class_id = ?
               )
               AND (s.last_name LIKE ? OR s.first_name LIKE ? OR s.student_number LIKE ? OR s.email LIKE ?)
             ORDER BY s.last_name, s.first_name"
        );
        mysqli_stmt_bind_param($q, 'iissss', $selected_class_id, $selected_class_id, $like, $like, $like, $like);
    } else {
        $q = mysqli_prepare($con,
            "SELECT s.student_number, s.first_name, s.last_name, s.middle_name,
                    s.email, s.course, s.year_level, e.status AS enroll_status
             FROM enrollments e
             JOIN students s ON e.student_id = s.student_id
             WHERE e.class_id = ?
               AND e.status IN ('ongoing','confirmed','dropped')
               AND e.enrollment_id = (
                   SELECT MAX(e2.enrollment_id) FROM enrollments e2
                   WHERE e2.student_id = e.student_id
                     AND e2.class_id = ?
               )
             ORDER BY s.last_name, s.first_name"
        );
        mysqli_stmt_bind_param($q, 'ii', $selected_class_id, $selected_class_id);
    }
    mysqli_stmt_execute($q);
    $res = mysqli_stmt_get_result($q);
    while ($r = mysqli_fetch_assoc($res)) $students[] = $r;
}

$enrolled_count = count(array_filter($students, fn($s) => in_array($s['enroll_status'], ['ongoing','confirmed'])));
$dropped_count  = count(array_filter($students, fn($s) => $s['enroll_status'] === 'dropped'));
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Class List</title>
    <link rel="icon" href="../../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"/>
    <script>
        (function() {
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark-mode');
        }
        })();
    </script>
    <link rel="stylesheet" href="../../css/faculty/faculty_tables.css" />
    <link rel="stylesheet" href="../../css/faculty/faculty_main.css" />
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
                <div class="acc-name"><?php echo htmlspecialchars($faculty['first_name'] . ' ' . $faculty['last_name']); ?></div>
                <div class="acc-img">
                    <img src="<?php echo !empty($faculty['profile_photo']) ? htmlspecialchars('../../' . $faculty['profile_photo']) : '../../uploads/default.jpg'; ?>" alt="Profile">
                </div>
            </div>
        </div>

        <nav class="main-nav" id="navMenu">
            <div class="nav-wrapper">
                <ul class="main-ul">
                    <li>
                        <a href="faculty_home.php">
                            <i class="fa-solid fa-house"></i>
                            <div class="li-name">Dashboard</div>
                        </a>
                    </li>
                    <li>
                        <a href="faculty_load.php">
                            <i class="fa-solid fa-calendar"></i>
                            <div class="li-name">Schedule</div>
                        </a>
                    </li>
                    <li>
                        <a href="faculty_classlist.php" class="active">
                            <i class="fa-solid fa-list"></i>
                            <div class="li-name">Class List</div>
                        </a>
                    </li>
                    <li>
                        <a href="faculty_spreadsheet.php">
                            <i class="fa-solid fa-table"></i>
                            <div class="li-name">Spreadsheet</div>
                        </a>
                    </li>
                    <li>
                        <a href="faculty_gradebook.php">
                            <i class="fa-solid fa-book"></i>
                            <div class="li-name">Gradebook</div>
                        </a>
                    </li>
                    <li>
                        <a href="faculty_grade_history.php">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            <div class="li-name">Grade History</div>
                        </a>
                    </li>
                    <li>
                        <a href="faculty_profile.php">
                            <i class="fa-solid fa-user"></i>
                            <div class="li-name">Profile</div>
                        </a>
                    </li>
                    <li>
                        <a href="../../php/faculty_logout.php" class="logout-bg">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                            <div class="li-name">Logout</div>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Dark Mode Toggle -->
            <div class="drk-mode-container">
                <div class="drk-label">
                    <i class="fa-solid fa-moon" id="modeIcon"></i>
                    <span class="li-name" id="modeLabel">Dark Mode</span>
                </div>
                <div class="toggle-track li-name" id="toggleTrack">
                    <div class="toggle-thumb"></div>
                </div>
            </div>
        </nav>
    </header>

    <div class="main-flex">
    <div class="spacer"></div>

    <main>

        <!-- Current / Past toggle -->
        <div class="sched-toggle-wrapper">
            <a href="?view=current" class="sched-toggle-btn sched-toggle-btn--current <?php echo $view_mode==='current'?'active':''; ?>">
                <i class="fa-solid fa-chalkboard"></i> Current Classes
                <?php if (count($current_classes)): ?>
                    <span class="sched-toggle-badge sched-toggle-badge--maroon"><?php echo count($current_classes); ?></span>
                <?php endif; ?>
            </a>
            <a href="?view=past" class="sched-toggle-btn sched-toggle-btn--past <?php echo $view_mode==='past'?'active':''; ?>">
                <i class="fa-solid fa-clock-rotate-left"></i> Past Classes
                <?php if (count($past_classes)): ?>
                    <span class="sched-toggle-badge sched-toggle-badge--navy"><?php echo count($past_classes); ?></span>
                <?php endif; ?>
            </a>
        </div>

        <!-- Class Selector Card -->
        <div class="class-nav">
            <div class="class-nav-left">
                <div class="class-nav-title">Class List <?php if($view_mode==='past'): ?><span style="font-size:.75rem;color:var(--text-label);font-weight:400;"> - Past / Finalized</span><?php endif; ?></div>
                <?php if ($selected_class): ?>
                <div class="class-meta" id="classMeta">
                    <span class="class-meta-badge code" id="metaCode"><?php echo htmlspecialchars($selected_class['subject_code']); ?></span>
                    <span class="class-meta-sep"><i class="fa-solid fa-chevron-right"></i></span>
                    <span class="class-meta-badge section" id="metaSection"><?php echo htmlspecialchars($selected_class['section']); ?></span>
                    <span class="class-meta-sep"><i class="fa-solid fa-chevron-right"></i></span>
                    <span class="class-meta-name" id="metaName"><?php echo htmlspecialchars($selected_class['subject_name']); ?></span>
                </div>
                <?php endif; ?>
            </div>
            <div class="class-nav-right">
                <form method="GET" id="classSelectForm">
                    <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_mode); ?>">
                    <div class="sched-label-container">
                        <label>Class</label>
                        <select name="class_id" id="classSelect" onchange="this.form.submit()">
                            <?php if (empty($display_classes)): ?>
                                <option>No classes found</option>
                            <?php else: ?>
                                <?php foreach ($display_classes as $c): ?>
                                <option value="<?php echo $c['class_id']; ?>"
                                    data-code="<?php echo htmlspecialchars($c['subject_code']); ?>"
                                    data-section="<?php echo htmlspecialchars($c['section']); ?>"
                                    data-name="<?php echo htmlspecialchars($c['subject_name']); ?>"
                                    <?php echo $c['class_id'] == $selected_class_id ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['subject_code'] . ' - ' . $c['section'] . ' (' . $c['semester'] . ' ' . $c['school_year'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <?php if (!$selected_class || empty($students)): ?>
        <!-- Empty State -->
        <div class="sched-empty">
            <i class="fa-solid fa-list"></i>
            <span><?php echo empty($display_classes) ? 'No ' . ($view_mode==='past'?'past':'current') . ' classes found.' : 'No students in this class.'; ?></span>
        </div>

        <?php else: ?>

        <!-- Summary Stats + Search + Export Toolbar -->
        <div class="cl-toolbar">
            <div class="cl-summary">
                <div class="cl-stat">
                    <span class="cl-stat-value"><?php echo count($students); ?></span>
                    <span class="cl-stat-label">Total</span>
                </div>
                <div class="sched-stat-divider"></div>
                <div class="cl-stat">
                    <span class="cl-stat-value"><?php echo $enrolled_count; ?></span>
                    <span class="cl-stat-label">Enrolled</span>
                </div>
                <div class="sched-stat-divider"></div>
                <div class="cl-stat">
                    <span class="cl-stat-value"><?php echo $dropped_count; ?></span>
                    <span class="cl-stat-label">Dropped</span>
                </div>
            </div>
            <div class="cl-toolbar-right">
                <form method="GET" style="display:contents;">
                    <input type="hidden" name="class_id" value="<?php echo $selected_class_id; ?>">
                    <div class="cl-search-wrapper">
                        <i class="fa-solid fa-magnifying-glass cl-search-icon"></i>
                        <input type="text" name="search" class="cl-search" placeholder="Search student-"
                               value="<?php echo htmlspecialchars($search); ?>"
                               oninput="filterRows(this.value)">
                    </div>
                </form>
                <button class="btn-export" onclick="exportCSV()">
                    <i class="fa-solid fa-file-csv"></i> Export CSV
                </button>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <button class="filter-tab active" id="tab-all"      onclick="setTab('all')">All</button>
            <button class="filter-tab"        id="tab-enrolled" onclick="setTab('enrolled')">Enrolled</button>
            <button class="filter-tab"        id="tab-dropped"  onclick="setTab('dropped')">Dropped</button>
        </div>

        <!-- Class List Table -->
        <div class="sec-table-wrapper">
            <div class="sec-table">
                <div class="sec-table-header">
                    <span class="col-head">#</span>
                    <span class="col-head">STUDENT NO.</span>
                    <span class="col-head col-align-left">FULL NAME (LN, FN MI)</span>
                    <span class="col-head col-align-left">EMAIL</span>
                    <span class="col-head">COURSE</span>
                    <span class="col-head">SECTION</span>
                    <span class="col-head">YEAR</span>
                    <span class="col-head">STATUS</span>
                </div>

                <div class="sec-table-body" id="clBody">
                    <?php foreach ($students as $idx => $st):
                        $mi = $st['middle_name'] ? ' ' . $st['middle_name'][0] . '.' : '';
                        $fullname = $st['last_name'] . ', ' . $st['first_name'] . $mi;
                        $status   = in_array($st['enroll_status'], ['ongoing', 'confirmed']) ? 'enrolled' : $st['enroll_status'];
                        $year_labels = ['1'=>'1st Year','2'=>'2nd Year','3'=>'3rd Year','4'=>'4th Year','5'=>'5th Year','6'=>'6th Year'];
                    ?>
                    <div class="sec-row cl-row" data-status="<?php echo $status; ?>">
                        <span class="col-num"><?php echo $idx + 1; ?></span>
                        <span class="col-container">
                            <span class="student-no"><?php echo htmlspecialchars($st['student_number']); ?></span>
                        </span>
                        <span class="col-side"><?php echo htmlspecialchars($fullname); ?></span>
                        <span class="col-email"><?php echo htmlspecialchars($st['email'] ?? '-'); ?></span>
                        <span class="col-container">
                            <span class="section-badge"><?php echo htmlspecialchars($st['course'] ?? '—'); ?></span>
                        </span>
                        <span class="col-container">
                            <span class="section-badge"><?php echo htmlspecialchars($selected_class['section']); ?></span>
                        </span>
                        <span class="col-container">
                            <span class="section-badge"><?php echo htmlspecialchars($year_labels[$st['year_level']] ?? '—'); ?></span>
                        </span>
                        <span class="col-container">
                            <span class="status-badge <?php echo $status; ?>">
                                <i class="fa-solid <?php echo $status === 'enrolled' ? 'fa-circle-check' : 'fa-circle-xmark'; ?>"></i>
                                <?php echo ucfirst($status); ?>
                            </span>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="table-footer">
                    <span id="clCount">Total Students: <strong><?php echo count($students); ?></strong></span>
                </div>
            </div>
        </div>

        <?php endif; ?>

    </main>
    </div>

    <script src="../../js/faculty/faculty_main.js"></script>
    <script>
        // Update meta badges when class changes (instant feedback before form submits)
        const classSelect = document.getElementById('classSelect');
        if (classSelect) {
            classSelect.addEventListener('change', function () {
                const opt = this.options[this.selectedIndex];
                const metaCode    = document.getElementById('metaCode');
                const metaSection = document.getElementById('metaSection');
                const metaName    = document.getElementById('metaName');
                if (metaCode)    metaCode.textContent    = opt.dataset.code    ?? '';
                if (metaSection) metaSection.textContent = opt.dataset.section ?? '';
                if (metaName)    metaName.textContent    = opt.dataset.name    ?? '';
            });
        }

        // Filter tabs
        let _currentTab = 'all';

        function setTab(tab) {
            _currentTab = tab;
            document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
            const tabEl = document.getElementById('tab-' + tab);
            if (tabEl) tabEl.classList.add('active');
            applyFilters();
        }

        function filterRows(q) { applyFilters(q); }

        function applyFilters(q) {
            q = (q ?? document.querySelector('.cl-search')?.value ?? '').toLowerCase();
            const rows = document.querySelectorAll('.cl-row');
            let visible = 0;
            rows.forEach(row => {
                const name   = row.querySelector('.col-side')?.textContent.toLowerCase() ?? '';
                const sno    = row.querySelector('.student-no')?.textContent.toLowerCase() ?? '';
                const email  = row.querySelector('.col-email')?.textContent.toLowerCase() ?? '';
                const status = row.dataset.status;
                const matchSearch = !q || name.includes(q) || sno.includes(q) || email.includes(q);
                const matchTab    = _currentTab === 'all' || status === _currentTab;
                const show = matchSearch && matchTab;
                row.style.display = show ? '' : 'none';
                if (show) visible++;
            });
            const cnt = document.getElementById('clCount');
            if (cnt) cnt.innerHTML = 'Total Students: <strong>' + visible + '</strong>';
        }

        function exportCSV() {
            const rows = document.querySelectorAll('.cl-row');
            const lines = [['#', 'Student No.', 'Full Name', 'Email', 'Course', 'Section', 'Year', 'Status']];
            let i = 1;
            rows.forEach(row => {
                if (row.style.display === 'none') return;
                lines.push([
                    i++,
                    row.querySelector('.student-no')?.textContent.trim() ?? '',
                    row.querySelector('.col-side')?.textContent.trim() ?? '',
                    row.querySelector('.col-email')?.textContent.trim() ?? '',
                    row.querySelector('.section-badge')?.textContent.trim() ?? '',
                    row.querySelector('.status-badge')?.textContent.trim() ?? '',
                ]);
            });
            const csv = lines.map(r => r.map(v => `"${String(v).replace(/"/g, '""')}"`).join(',')).join('\n');
            const a = document.createElement('a');
            a.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
            a.download = 'classlist_<?php echo $selected_class_id; ?>.csv';
            a.click();
        }
    </script>
  </body>
</html>
