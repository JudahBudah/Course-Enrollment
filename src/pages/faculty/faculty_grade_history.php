<?php
session_start();
include("../../php/connection.php");

if (!isset($_SESSION['faculty_id'])) { header("Location: faculty_login.php"); die; }
$faculty_id = (int)$_SESSION['faculty_id'];

$faculty = mysqli_fetch_assoc(mysqli_query($con,
    "SELECT * FROM faculty WHERE faculty_id = $faculty_id LIMIT 1"
));
if (!$faculty) { session_destroy(); header("Location: faculty_login.php"); die; }

// Ensure table exists
mysqli_query($con, "CREATE TABLE IF NOT EXISTS grade_history (
    history_id     INT AUTO_INCREMENT PRIMARY KEY,
    class_id       INT NOT NULL,
    faculty_id     INT NOT NULL,
    subject_code   VARCHAR(50) NOT NULL,
    subject_name   VARCHAR(255) NOT NULL,
    section        VARCHAR(100) NOT NULL,
    semester       VARCHAR(20) NOT NULL,
    school_year    VARCHAR(20) NOT NULL,
    student_id     INT NOT NULL,
    student_number VARCHAR(50) NOT NULL,
    student_name   VARCHAR(255) NOT NULL,
    class_standing DECIMAL(5,2) DEFAULT NULL,
    quiz           DECIMAL(5,2) DEFAULT NULL,
    midterms       DECIMAL(5,2) DEFAULT NULL,
    finals         DECIMAL(5,2) DEFAULT NULL,
    computed_grade DECIMAL(5,2) DEFAULT NULL,
    point_grade    VARCHAR(10) DEFAULT NULL,
    remarks        VARCHAR(20) DEFAULT NULL,
    finalized_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_class   (class_id),
    KEY idx_faculty (faculty_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Get distinct finalized classes for this faculty
$classes = [];
$cq = mysqli_query($con,
    "SELECT DISTINCT class_id, subject_code, subject_name, section, semester, school_year, finalized_at
     FROM grade_history
     WHERE faculty_id = $faculty_id
     ORDER BY finalized_at DESC"
);
while ($r = mysqli_fetch_assoc($cq)) $classes[] = $r;

// Selected class
$selected_class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : ($classes[0]['class_id'] ?? 0);
$selected_class = null;
foreach ($classes as $c) {
    if ($c['class_id'] == $selected_class_id) { $selected_class = $c; break; }
}
if ($selected_class_id && !$selected_class) { $selected_class_id = 0; }

// Pull history rows for selected class
$rows = [];
if ($selected_class_id) {
    $q = mysqli_prepare($con,
        "SELECT student_number, student_name, class_standing, quiz, midterms, finals,
                computed_grade, point_grade, remarks
         FROM grade_history
         WHERE class_id = ? AND faculty_id = ?
         ORDER BY student_name"
    );
    mysqli_stmt_bind_param($q, 'ii', $selected_class_id, $faculty_id);
    mysqli_stmt_execute($q);
    $res = mysqli_stmt_get_result($q);
    while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
}

$total  = count($rows);
$passed = count(array_filter($rows, fn($r) => $r['remarks'] === 'passed'));
$failed = count(array_filter($rows, fn($r) => $r['remarks'] === 'failed'));
$graded = count(array_filter($rows, fn($r) => $r['computed_grade'] !== null));
$avg_cg = $graded ? round(array_sum(array_column(array_filter($rows, fn($r) => $r['computed_grade'] !== null), 'computed_grade')) / $graded, 2) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade History</title>
    <link rel="icon" href="../../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <script>
        (function() {
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark-mode');
        }
        })();
    </script>
    <link rel="stylesheet" href="../../css/faculty/faculty_tables.css">
    <link rel="stylesheet" href="../../css/faculty/faculty_main.css">
</head>
<body>
<header>
    <div class="nav-section">
        <button class="nav-button" id="navButton"><i class="fa-solid fa-bars trans-bars" id="trans-bars"></i></button>
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
                <img src="<?php echo !empty($faculty['profile_photo']) ? htmlspecialchars('../../'.$faculty['profile_photo']) : '../../uploads/default.jpg'; ?>" alt="Profile">
            </div>
        </div>
    </div>
    <nav class="main-nav" id="navMenu">
        <div class="nav-wrapper">
            <ul class="main-ul">
                <li><a href="faculty_home.php"><i class="fa-solid fa-house"></i><div class="li-name">Dashboard</div></a></li>
                <li><a href="faculty_load.php"><i class="fa-solid fa-calendar"></i><div class="li-name">Schedule</div></a></li>
                <li><a href="faculty_classlist.php"><i class="fa-solid fa-list"></i><div class="li-name">Class List</div></a></li>
                <li><a href="faculty_spreadsheet.php"><i class="fa-solid fa-table"></i><div class="li-name">Spreadsheet</div></a></li>
                <li><a href="faculty_gradebook.php"><i class="fa-solid fa-book"></i><div class="li-name">Gradebook</div></a></li>
                <li><a href="faculty_grade_history.php" class="active"><i class="fa-solid fa-clock-rotate-left"></i><div class="li-name">Grade History</div></a></li>
                <li><a href="faculty_profile.php"><i class="fa-solid fa-user"></i><div class="li-name">Profile</div></a></li>
                <li><a href="../../php/faculty_logout.php" class="logout-bg"><i class="fa-solid fa-arrow-right-from-bracket"></i><div class="li-name">Logout</div></a></li>
            </ul>
        </div>
        <div class="drk-mode-container">
            <div class="drk-label"><i class="fa-solid fa-moon" id="modeIcon"></i><span class="li-name" id="modeLabel">Dark Mode</span></div>
            <div class="toggle-track li-name" id="toggleTrack"><div class="toggle-thumb"></div></div>
        </div>
    </nav>
</header>

<div class="main-flex">
<div class="spacer"></div>
<main>

    <!-- Class Selector -->
    <div class="class-nav">
        <div class="class-nav-left">
            <div class="class-nav-title">Grade History</div>
            <?php if ($selected_class): ?>
            <div class="class-meta">
                <span class="class-meta-badge code"><?php echo htmlspecialchars($selected_class['subject_code']); ?></span>
                <span class="class-meta-sep"><i class="fa-solid fa-chevron-right"></i></span>
                <span class="class-meta-badge section"><?php echo htmlspecialchars($selected_class['section']); ?></span>
                <span class="class-meta-sep"><i class="fa-solid fa-chevron-right"></i></span>
                <span class="class-meta-name"><?php echo htmlspecialchars($selected_class['subject_name']); ?></span>
                <span class="class-meta-sep"><i class="fa-solid fa-chevron-right"></i></span>
                <span style="font-size:.78rem;color:var(--text-label);">
                    Finalized <?php echo date('M j, Y', strtotime($selected_class['finalized_at'])); ?>
                </span>
            </div>
            <?php endif; ?>
        </div>
        <div class="class-nav-right">
            <form method="GET">
                <div class="sched-label-container">
                    <label>Class</label>
                    <select name="class_id" onchange="this.form.submit()">
                        <?php if (empty($classes)): ?>
                            <option>No finalized classes yet</option>
                        <?php else: ?>
                            <?php foreach ($classes as $c): ?>
                            <option value="<?php echo $c['class_id']; ?>" <?php echo $c['class_id'] == $selected_class_id ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['subject_code'] . ' — ' . $c['section'] . ' (' . $c['semester'] . ' ' . $c['school_year'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($classes)): ?>
    <div class="sched-empty">
        <i class="fa-solid fa-clock-rotate-left"></i>
        <span>No finalized grade records yet. Finalize a class from the Gradebook to see history here.</span>
    </div>

    <?php elseif (empty($rows)): ?>
    <div class="sched-empty">
        <i class="fa-solid fa-users"></i>
        <span>No students found in this class history.</span>
    </div>

    <?php else: ?>

    <!-- Toolbar -->
    <div class="gb-toolbar">
        <div class="gb-summary">
            <div class="gb-stat"><span class="gb-stat-value"><?php echo $total; ?></span><span class="gb-stat-label">Students</span></div>
            <div class="sched-stat-divider"></div>
            <div class="gb-stat"><span class="gb-stat-value"><?php echo $passed; ?></span><span class="gb-stat-label">Passed</span></div>
            <div class="sched-stat-divider"></div>
            <div class="gb-stat"><span class="gb-stat-value"><?php echo $failed; ?></span><span class="gb-stat-label">Failed</span></div>
            <div class="sched-stat-divider"></div>
            <div class="gb-stat"><span class="gb-stat-value"><?php echo $avg_cg ?? '—'; ?></span><span class="gb-stat-label">Class Avg</span></div>
        </div>
        <div class="gb-toolbar-right">
            <div class="cl-search-wrapper">
                <i class="fa-solid fa-magnifying-glass cl-search-icon"></i>
                <input type="text" class="cl-search" id="ghSearch" placeholder="Search student…" oninput="filterRows(this.value)">
            </div>
            <button class="btn-export" onclick="exportCSV()">
                <i class="fa-solid fa-file-csv"></i> Export CSV
            </button>
        </div>
    </div>

    <!-- History Table -->
    <div class="gb-table-wrapper">
        <div class="gb-table">
            <div class="gb-table-header">
                <span class="col-head">#</span>
                <span class="col-head">STUDENT NO.</span>
                <span class="col-head col-align-left">FULL NAME</span>
                <span class="col-head">CLASS<br><small>STANDING</small></span>
                <span class="col-head">QUIZ</span>
                <span class="col-head">MIDTERMS</span>
                <span class="col-head">FINALS</span>
                <span class="col-head">COMPUTED<br><small>GRADE</small></span>
                <span class="col-head">POINT<br><small>GRADE</small></span>
                <span class="col-head">REMARKS</span>
            </div>
            <div class="gb-table-body" id="ghBody">
                <?php foreach ($rows as $idx => $r):
                    $rm = $r['remarks'] ?? 'pending';
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
                ?>
                <div class="gb-row">
                    <span class="col-num"><?php echo $idx + 1; ?></span>
                    <span class="col-container"><span class="student-no"><?php echo htmlspecialchars($r['student_number']); ?></span></span>
                    <span class="col-side"><?php echo htmlspecialchars($r['student_name']); ?></span>
                    <span class="col-cg"><?php echo $r['class_standing'] !== null ? number_format($r['class_standing'], 2) : '<span class="no-grade">—</span>'; ?></span>
                    <span class="col-cg"><?php echo $r['quiz'] !== null ? number_format($r['quiz'], 2) : '<span class="no-grade">—</span>'; ?></span>
                    <span class="col-cg"><?php echo $r['midterms'] !== null ? number_format($r['midterms'], 2) : '<span class="no-grade">—</span>'; ?></span>
                    <span class="col-cg"><?php echo $r['finals'] !== null ? number_format($r['finals'], 2) : '<span class="no-grade">—</span>'; ?></span>
                    <span class="col-cg"><?php echo $r['computed_grade'] !== null ? number_format($r['computed_grade'], 2) : '<span class="no-grade">—</span>'; ?></span>
                    <span class="col-fg"><?php echo $r['point_grade'] ?? '<span class="no-grade">—</span>'; ?></span>
                    <span class="col-container">
                        <span class="col-remark <?php echo $rm; ?>">
                            <i class="fa-solid <?php echo $rmIcon; ?>"></i> <?php echo $rmLabel; ?>
                        </span>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="table-footer">
                <span id="ghCount">Total Students: <strong><?php echo $total; ?></strong></span>
                <span><?php echo $passed; ?> passed &bull; <?php echo $failed; ?> failed</span>
                <?php if ($avg_cg !== null): ?><span>Class avg: <strong><?php echo $avg_cg; ?></strong></span><?php endif; ?>
            </div>
        </div>
    </div>

    <?php endif; ?>

</main>
</div>

<script src="../../js/faculty/faculty_main.js"></script>
<script>
function filterRows(q) {
    q = q.toLowerCase();
    let visible = 0;
    document.querySelectorAll('.gb-row').forEach(row => {
        const name = row.querySelector('.col-side')?.textContent.toLowerCase() ?? '';
        const sno  = row.querySelector('.student-no')?.textContent.toLowerCase() ?? '';
        const show = name.includes(q) || sno.includes(q);
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    const cnt = document.getElementById('ghCount');
    if (cnt) cnt.innerHTML = 'Total Students: <strong>' + visible + '</strong>';
}

function exportCSV() {
    const rows = document.querySelectorAll('.gb-row');
    const lines = [['#','Student No.','Full Name','Class Standing','Quiz','Midterms','Finals','Computed Grade','Point Grade','Remarks']];
    let i = 1;
    rows.forEach(row => {
        if (row.style.display === 'none') return;
        const cols = row.querySelectorAll('span.col-num, span.col-container span.student-no, span.col-side, span.col-cg, span.col-fg, span.col-remark');
        lines.push([
            i++,
            row.querySelector('.student-no')?.textContent.trim() ?? '',
            row.querySelector('.col-side')?.textContent.trim() ?? '',
            ...Array.from(row.querySelectorAll('.col-cg')).map(c => c.textContent.trim()),
            row.querySelector('.col-fg')?.textContent.trim() ?? '',
            row.querySelector('.col-remark')?.textContent.trim() ?? '',
        ]);
    });
    const csv = lines.map(r => r.map(v => `"${String(v).replace(/"/g,'""')}"`).join(',')).join('\n');
    const a = document.createElement('a');
    a.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
    a.download = 'grade_history_<?php echo $selected_class_id; ?>.csv';
    a.click();
}
</script>
</body>
</html>
