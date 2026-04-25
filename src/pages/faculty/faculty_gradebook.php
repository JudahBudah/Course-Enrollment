<?php
session_start();
include("../../php/connection.php");

if (!isset($_SESSION['faculty_id'])) { header("Location: faculty_login.php"); die; }
$faculty_id = (int)$_SESSION['faculty_id'];

$faculty = mysqli_fetch_assoc(mysqli_query($con,
    "SELECT * FROM faculty WHERE faculty_id = $faculty_id LIMIT 1"
));
if (!$faculty) { session_destroy(); header("Location: faculty_login.php"); die; }

// Get classes assigned to this faculty - split current vs past
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

$view_mode = $_GET['view'] ?? 'current';
$display_classes = $view_mode === 'past' ? $past_classes : $current_classes;

$selected_class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : ($display_classes[0]['class_id'] ?? 0);
$selected_class = null;
foreach ($classes as $c) {
    if ($c['class_id'] == $selected_class_id) { $selected_class = $c; break; }
}

// Pull students + their grade entries for the selected class
$rows = [];
if ($selected_class_id) {
    $q = mysqli_query($con,
        "SELECT s.student_number, s.last_name, s.first_name, s.middle_name,
                ge.computed_grade
         FROM enrollments e
         JOIN students s ON e.student_id = s.student_id
         LEFT JOIN grade_entries ge ON ge.enrollment_id = e.enrollment_id
         WHERE e.class_id = $selected_class_id
           AND e.status IN ('ongoing','confirmed')
           AND e.enrollment_id = (
               SELECT MAX(e2.enrollment_id) FROM enrollments e2
               WHERE e2.student_id = e.student_id
                 AND e2.class_id = $selected_class_id
                 AND e2.status IN ('ongoing','confirmed')
           )
         ORDER BY s.last_name, s.first_name"
    );
    while ($r = mysqli_fetch_assoc($q)) $rows[] = $r;
}

// Transmutation helpers
function transmute(float $g): int {
    if ($g>=97) return 99; if ($g>=94) return 96; if ($g>=91) return 93;
    if ($g>=88) return 90; if ($g>=85) return 87; if ($g>=82) return 84;
    if ($g>=79) return 81; if ($g>=76) return 78; if ($g>=73) return 75;
    if ($g>=70) return 72; if ($g>=67) return 69; if ($g>=64) return 66;
    if ($g>=61) return 63; if ($g>=55) return 60; return 55;
}
function pointGrade(int $t): string {
    if ($t>=97) return '1.00'; if ($t>=94) return '1.25'; if ($t>=91) return '1.50';
    if ($t>=88) return '1.75'; if ($t>=85) return '2.00'; if ($t>=82) return '2.25';
    if ($t>=79) return '2.50'; if ($t>=76) return '2.75'; if ($t>=73) return '3.00';
    if ($t>=70) return '4.00'; return '5.00';
}
function remark(string $p): string {
    if ($p === '5.00') return 'failed';
    if ($p === '4.00') return 'conditional';
    return 'passed';
}

// Summary counts
$total  = count($rows);
$graded = array_filter($rows, fn($r) => $r['computed_grade'] !== null);
$passed = array_filter($graded, fn($r) => transmute((float)$r['computed_grade']) >= 73);
$failed = array_filter($graded, fn($r) => transmute((float)$r['computed_grade']) < 70);

$sum = 0;
foreach ($graded as $r) $sum += (float)$r['computed_grade'];
$avg_cg = count($graded) ? round($sum / count($graded), 2) : null;
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gradebook</title>
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
                        <a href="faculty_classlist.php">
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
                        <a href="faculty_gradebook.php" class="active">
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
                <div class="class-nav-title">Gradebook <?php if($view_mode==='past'): ?><span style="font-size:.75rem;color:var(--text-label);font-weight:400;"> - Past / Finalized</span><?php endif; ?></div>
                <?php if ($selected_class): ?>
                <div class="class-meta">
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

        <?php if (!$selected_class || empty($rows)): ?>
        <!-- Empty State -->
        <div class="sched-empty">
            <i class="fa-solid fa-book"></i>
            <span><?php echo empty($display_classes) ? 'No ' . ($view_mode==='past'?'past':'current') . ' classes found.' : 'No enrolled students in this class.'; ?></span>
        </div>

        <?php else: ?>

        <!-- Summary Stats + Search + Export Toolbar -->
        <div class="gb-toolbar">
            <div class="gb-summary">
                <div class="gb-stat">
                    <span class="gb-stat-value"><?php echo $total; ?></span>
                    <span class="gb-stat-label">Students</span>
                </div>
                <div class="sched-stat-divider"></div>
                <div class="gb-stat">
                    <span class="gb-stat-value"><?php echo count($graded); ?></span>
                    <span class="gb-stat-label">Graded</span>
                </div>
                <div class="sched-stat-divider"></div>
                <div class="gb-stat">
                    <span class="gb-stat-value"><?php echo count($passed); ?></span>
                    <span class="gb-stat-label">Passed</span>
                </div>
                <div class="sched-stat-divider"></div>
                <div class="gb-stat">
                    <span class="gb-stat-value"><?php echo count($failed); ?></span>
                    <span class="gb-stat-label">Failed</span>
                </div>
                <div class="sched-stat-divider"></div>
                <div class="gb-stat">
                    <span class="gb-stat-value"><?php echo $avg_cg ?? '-'; ?></span>
                    <span class="gb-stat-label">Class Avg</span>
                </div>
            </div>
            <div class="gb-toolbar-right">
                <div class="cl-search-wrapper">
                    <i class="fa-solid fa-magnifying-glass cl-search-icon"></i>
                    <input type="text" class="cl-search" id="gbSearch"
                           placeholder="Search student-"
                           oninput="filterRows(this.value)">
                </div>
                <button class="btn-export" onclick="exportCSV()">
                    <i class="fa-solid fa-file-csv"></i> Export CSV
                </button>
            </div>
        </div>

        <!-- Gradebook Table -->
        <div class="gb-table-wrapper">
            <div class="gb-table">
                <div class="gb-table-header">
                    <span class="col-head">#</span>
                    <span class="col-head">STUDENT NO.</span>
                    <span class="col-head col-align-left">FULL NAME (LN, FN MN)</span>
                    <span class="col-head">COMPUTED<br><small>GRADE</small></span>
                    <span class="col-head">TRANSMUTED<br><small>GRADE</small></span>
                    <span class="col-head">POINT<br><small>GRADE</small></span>
                    <span class="col-head">REMARKS</span>
                </div>

                <div class="gb-table-body" id="gbBody">
                    <?php foreach ($rows as $idx => $r):
                        $cg     = $r['computed_grade'] !== null ? (float)$r['computed_grade'] : null;
                        $tg     = $cg !== null ? transmute($cg) : null;
                        $pg     = $tg !== null ? pointGrade($tg) : null;
                        $rm     = $pg !== null ? remark($pg) : 'pending';
                        $rmIcon = match($rm) {
                            'passed'      => 'fa-circle-check',
                            'failed'      => 'fa-circle-xmark',
                            'conditional' => 'fa-circle-half-stroke',
                            default       => 'fa-clock'
                        };
                        $rmLabel = match($rm) {
                            'passed'      => 'Passed',
                            'failed'      => 'Failed',
                            'conditional' => 'Conditional',
                            default       => 'Pending'
                        };
                        $fullname = htmlspecialchars($r['last_name'] . ', ' . $r['first_name'] . ($r['middle_name'] ? ' ' . $r['middle_name'][0] . '.' : ''));
                    ?>
                    <div class="gb-row">
                        <span class="col-num"><?php echo $idx + 1; ?></span>
                        <span class="col-container">
                            <span class="student-no"><?php echo htmlspecialchars($r['student_number']); ?></span>
                        </span>
                        <span class="col-side"><?php echo $fullname; ?></span>
                        <span class="col-cg">
                            <?php echo $cg !== null ? number_format($cg, 2) : '<span class="no-grade">-</span>'; ?>
                        </span>
                        <span class="col-tg">
                            <?php echo $tg ?? '<span class="no-grade">-</span>'; ?>
                        </span>
                        <span class="col-fg">
                            <?php echo $pg ?? '<span class="no-grade">-</span>'; ?>
                        </span>
                        <span class="col-container">
                            <span class="col-remark <?php echo $rm; ?>">
                                <i class="fa-solid <?php echo $rmIcon; ?>"></i>
                                <?php echo $rmLabel; ?>
                            </span>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="table-footer">
                    <span id="gbCount">Total Students: <strong><?php echo $total; ?></strong></span>
                    <span><?php echo count($passed); ?> passed &bull; <?php echo count($failed); ?> failed</span>
                    <?php if ($avg_cg !== null): ?>
                    <span>Class avg: <strong><?php echo $avg_cg; ?></strong></span>
                    <?php endif; ?>
                    <a href="faculty_spreadsheet.php?class_id=<?php echo $selected_class_id; ?>" class="gb-edit-link">
                        <i class="fa-solid fa-table"></i> Edit in Spreadsheet
                    </a>
                </div>
            </div>
        </div>

        <?php endif; ?>

    </main>
    </div>

    <script src="../../js/faculty/faculty_main.js"></script>
    <script>
        // -- Meta badge updater -----------------------------------------------
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

        // -- Search filter ----------------------------------------------------
        function filterRows(q) {
            q = q.toLowerCase();
            const rows = document.querySelectorAll('.gb-row');
            let visible = 0;
            rows.forEach(row => {
                const name = row.querySelector('.col-side')?.textContent.toLowerCase() ?? '';
                const sno  = row.querySelector('.student-no')?.textContent.toLowerCase() ?? '';
                const show = name.includes(q) || sno.includes(q);
                row.style.display = show ? '' : 'none';
                if (show) visible++;
            });
            const cnt = document.getElementById('gbCount');
            if (cnt) cnt.innerHTML = 'Total Students: <strong>' + visible + '</strong>';
        }

        // -- CSV Export -------------------------------------------------------
        function exportCSV() {
            const rows = document.querySelectorAll('.gb-row');
            const lines = [['#', 'Student No.', 'Full Name', 'Computed Grade', 'Transmuted Grade', 'Point Grade', 'Remarks']];
            let i = 1;
            rows.forEach(row => {
                if (row.style.display === 'none') return;
                lines.push([
                    i++,
                    row.querySelector('.student-no')?.textContent.trim() ?? '',
                    row.querySelector('.col-side')?.textContent.trim() ?? '',
                    row.querySelector('.col-cg')?.textContent.trim() ?? '',
                    row.querySelector('.col-tg')?.textContent.trim() ?? '',
                    row.querySelector('.col-fg')?.textContent.trim() ?? '',
                    row.querySelector('.col-remark')?.textContent.trim() ?? '',
                ]);
            });
            const csv = lines.map(r => r.map(v => `"${String(v).replace(/"/g, '""')}"`).join(',')).join('\n');
            const a = document.createElement('a');
            a.href     = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
            a.download = 'gradebook_<?php echo $selected_class_id; ?>.csv';
            a.click();
        }
    </script>
  </body>
</html>
