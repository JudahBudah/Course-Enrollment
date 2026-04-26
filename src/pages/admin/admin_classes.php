<?php
session_start();
include("../../php/connection.php");
include("../../php/admin_functions.php");

$admin_data = check_admin_login($con);
$pending_applicants = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM applicants WHERE application_status = 'pending'"))['c'];

// Flash messages
$flash_errors = [
    'missing_fields'    => 'Please fill in all required fields.',
    'insert_failed'     => 'Failed to create class. Please try again.',
    'update_failed'     => 'Failed to update class. Please try again.',
    'has_enrollments'   => 'Cannot delete — class has enrolled students.',
    'duplicate_class'   => 'This section already has this subject for the selected school year and semester.',
    'schedule_conflict' => 'Schedule conflict: Another class is already using this room at the same day and time.',
    'faculty_conflict'  => 'Schedule conflict: This faculty member already has a class at the same day and time.',
    'wrong_school_year' => 'School year does not match the current system school year. Update it in Admin Accounts.',
];
$flash = '';
if (isset($_GET['error']) && isset($flash_errors[$_GET['error']])) {
    $flash = '<div class="error-message"><i class="fa-solid fa-circle-exclamation"></i> ' . $flash_errors[$_GET['error']] . '</div>';
}
if (isset($_GET['success'])) {
    $msgs = [
        'added'   => 'Class created successfully.',
        'updated' => 'Class updated successfully.',
        'deleted' => 'Class deleted successfully.',
    ];
    $flash = '<div class="success-message"><i class="fa-solid fa-check-circle"></i> ' . ($msgs[$_GET['success']] ?? 'Done.') . '</div>';
}

// Stats
$total_classes  = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM classes"))['c'];
$open_classes   = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM classes WHERE status = 'open'"))['c'];
$closed_classes = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM classes WHERE status = 'closed'"))['c'];
$total_enrolled = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as s FROM enrollments WHERE status IN ('confirmed','reserved','ongoing')"))['s'] ?? 0;

// Search & filter
$search          = trim($_GET['search'] ?? '');
$filter          = $_GET['filter']   ?? 'all';
$semester_filter = $_GET['semester'] ?? '';
$year_filter     = $_GET['year']     ?? '';

$where = "WHERE 1=1";
if ($filter === 'open')      $where .= " AND c.status = 'open'";
if ($filter === 'closed')    $where .= " AND c.status = 'closed'";
if ($filter === 'cancelled') $where .= " AND c.status = 'cancelled'";
if ($semester_filter !== '') $where .= " AND c.semester = '" . mysqli_real_escape_string($con, $semester_filter) . "'";
if ($year_filter !== '')     $where .= " AND s.year_level = '" . mysqli_real_escape_string($con, $year_filter) . "'";
if ($search !== '') {
    $s = mysqli_real_escape_string($con, $search);
    $where .= " AND (s.subject_code LIKE '%$s%' OR s.subject_name LIKE '%$s%' OR c.section LIKE '%$s%' OR c.room LIKE '%$s%')";
}

$classes = mysqli_query($con, "
    SELECT c.*, s.subject_code, s.subject_name, s.units, s.year_level,
           CONCAT(f.first_name, ' ', f.last_name) as faculty_name,
           (SELECT COUNT(*) FROM enrollments e WHERE e.class_id = c.class_id AND e.status IN ('confirmed','reserved','ongoing')) as real_enrolled
    FROM classes c
    JOIN subjects s ON c.subject_id = s.subject_id
    LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
    $where
    ORDER BY c.school_year DESC, c.semester ASC, s.subject_code ASC
");

// Courses for modal selector
$courses_query = mysqli_query($con, "SELECT course_id, course_code, course_name, college_name FROM courses WHERE status = 'active' ORDER BY college_name, course_name");
$modal_courses = [];
while ($c = mysqli_fetch_assoc($courses_query)) $modal_courses[] = $c;

// All subjects grouped by course_id → year_level → semester
$subjects_query = mysqli_query($con, "
    SELECT subject_id, subject_code, subject_name, units, year_level, semester, department, course_id
    FROM subjects WHERE status = 'active'
    ORDER BY course_id, CAST(year_level AS UNSIGNED), FIELD(semester,'1st','2nd','summer'), subject_code
");
$subjects_by_course = [];
while ($s = mysqli_fetch_assoc($subjects_query)) {
    $cid = $s['course_id'] ?? 0;
    $yr  = $s['year_level'] ?? '0';
    $sem = $s['semester'] ?? 'N/A';
    $subjects_by_course[$cid][$yr][$sem][] = $s;
}

// Faculty for dropdown
$faculty_query = mysqli_query($con, "
    SELECT faculty_id, first_name, last_name FROM faculty
    WHERE status = 'active'
    ORDER BY last_name, first_name
");
$faculty = [];
while ($f = mysqli_fetch_assoc($faculty_query)) $faculty[] = $f;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classes Management - PLM Admin</title>
    <link rel="icon" href="../../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <script>
        (function() {
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark-mode');
        }
        })();
    </script>
    <link rel="stylesheet" href="../../css/admin/admin_main.css">
    <link rel="stylesheet" href="../../css/admin/admin_classes.css">
</head>
<body>

    <!-- ── Top Nav Bar ────────────────────────────────── -->
    <header>
        <div class="nav-section">
            <!-- Mobile toggle -->
            <button class="nav-button" id="navButton">
                <i class="fa-solid fa-bars" id="trans-bars"></i>
            </button>

            <div class="logo-container">
                <img src="../../assets/plm-logo.png" alt="PLM Logo" loading="lazy">
                <div class="title-container">
                    <div class="logo-title">PAMANTASAN NG LUNGSOD NG MAYNILA</div>
                    <div class="logo-sub">University of the City of Manila</div>
                </div>
            </div>

            <div class="acc-display-container">
                <div class="acc-name">
                    <?php echo htmlspecialchars($admin_data['username'] ?? 'Admin'); ?>
                </div>
                <div class="user-avatar">
                    <?php echo strtoupper(substr($admin_data['username'] ?? 'A', 0, 1)); ?>
                </div>
            </div>
        </div>

        <!-- ── Side Nav ───────────────────────────────── -->
        <nav class="main-nav" id="navMenu">
            <div class="nav-wrapper">
                <ul class="main-ul">
                    <li>
                        <a href="admin_home.php">
                            <i class="fa-solid fa-house"></i>
                            <span class="li-name">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_applicants.php">
                            <i class="fa-solid fa-user-plus"></i>
                            <span class="li-name">Applicants</span>
                            <?php if ($pending_applicants > 0): ?>
                                <span class="sidebar-badge li-name"><?php echo $pending_applicants; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <!-- Student Records Dropdown -->
                    <li class="course-dropdown">
                        <a href="#" id="student-records-dropdown">
                            <i class="fa-solid fa-user-graduate"></i>
                            <span class="li-name chev-space">
                                Student Records
                                <i class="fa-solid fa-chevron-down"></i>
                            </span>
                        </a>
                        <div class="acad-dropdown-menu" id="student-records-menu">
                            <ul>
                                <li><a href="admin_students.php">Students</a></li>
                                <li><a href="admin_enrollments.php">Enrollments</a></li>
                                <li>
                                    <a href="admin_drop_requests.php">
                                        Drop Requests
                                        <?php if (!empty($GLOBALS['pending_drops'])): ?>
                                            <span class="sidebar-badge"><?php echo $GLOBALS['pending_drops']; ?></span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- Academic Records Dropdown -->
                    <li class="course-dropdown">
                        <a href="#" id="acad-records-dropdown">
                            <i class="fa-solid fa-graduation-cap"></i>
                            <span class="li-name chev-space">
                                Academic Records
                                <i class="fa-solid fa-chevron-down"></i>
                            </span>
                        </a>
                        <div class="acad-dropdown-menu" id="acad-records-menu">
                            <ul>
                                <li><a href="admin_subjects.php">Subjects</a></li>
                                <li><a href="admin_classes.php">Classes</a></li>
                                <li><a href="admin_blocks.php">Blocks</a></li>
                            </ul>
                        </div>
                    </li>

                    <!-- Personnel Dropdown -->
                    <li class="course-dropdown">
                        <a href="#" id="personnel-dropdown">
                            <i class="fa-solid fa-users-gear"></i>
                            <span class="li-name chev-space">
                                Personnel
                                <i class="fa-solid fa-chevron-down"></i>
                            </span>
                        </a>
                        <div class="acad-dropdown-menu" id="personnel-menu">
                            <ul>
                                <li><a href="admin_faculty.php">Faculty</a></li>
                                <?php if (($admin_data['role'] ?? 'admin') === 'superadmin'): ?>
                                    <li><a href="admin_accounts.php">Admin Accounts</a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </li>

                    <!-- Communications Dropdown -->
                    <li class="course-dropdown">
                        <a href="#" id="comms-dropdown">
                            <i class="fa-solid fa-bullhorn"></i>
                            <span class="li-name chev-space">
                                Communications
                                <i class="fa-solid fa-chevron-down"></i>
                            </span>
                        </a>
                        <div class="acad-dropdown-menu" id="comms-menu">
                            <ul>
                                <li><a href="admin_announcements.php">Announcements</a></li>
                                <li><a href="admin_calendar.php">Calendar</a></li>
                            </ul>
                        </div>
                    </li>

                    <li>
                        <a href="../../php/admin_logout.php" class="logout-bg">
                            <i class="fa-solid fa-right-from-bracket"></i>
                            <span class="li-name">Logout</span>
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

    <!-- ── Page Body ──────────────────────────────────── -->
    <div class="main-flex">
        <div class="spacer"></div>

        <main>
            <div class="main-content">

                <div class="page-header">
                    <h1>Classes Management</h1>
                    <p>Manage class schedules, sections, and capacity</p>
                </div>

                <?php echo $flash; ?>

                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card blue">
                        <div class="stat-icon"><i class="fa-solid fa-door-open"></i></div>
                        <div class="stat-content"><h3>Total Classes</h3><p class="stat-number"><?php echo $total_classes; ?></p></div>
                    </div>
                    <div class="stat-card green">
                        <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
                        <div class="stat-content"><h3>Open Classes</h3><p class="stat-number"><?php echo $open_classes; ?></p></div>
                    </div>
                    <div class="stat-card red">
                        <div class="stat-icon"><i class="fa-solid fa-circle-xmark"></i></div>
                        <div class="stat-content"><h3>Closed</h3><p class="stat-number"><?php echo $closed_classes; ?></p></div>
                    </div>
                    <div class="stat-card gold">
                        <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                        <div class="stat-content"><h3>Total Enrolled</h3><p class="stat-number"><?php echo $total_enrolled; ?></p></div>
                    </div>
                </div>

                <!-- Classes Table Card -->
                <div class="card">
                    <div class="card-header">
                        <h2>All Classes</h2>
                        <div class="card-header-actions">
                            <form method="GET" class="header-filter-form">
                                <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                                <select name="year" class="filter-select" onchange="this.form.submit()">
                                    <option value="">All Years</option>
                                    <option value="1" <?php echo $year_filter==='1'?'selected':''; ?>>1st Year</option>
                                    <option value="2" <?php echo $year_filter==='2'?'selected':''; ?>>2nd Year</option>
                                    <option value="3" <?php echo $year_filter==='3'?'selected':''; ?>>3rd Year</option>
                                    <option value="4" <?php echo $year_filter==='4'?'selected':''; ?>>4th Year</option>
                                    <option value="5" <?php echo $year_filter==='5'?'selected':''; ?>>5th Year</option>
                                    <option value="6" <?php echo $year_filter==='6'?'selected':''; ?>>6th Year</option>
                                </select>
                                <select name="semester" class="filter-select" onchange="this.form.submit()">
                                    <option value="">All Semesters</option>
                                    <option value="1st"    <?php echo $semester_filter==='1st'?'selected':''; ?>>1st Semester</option>
                                    <option value="2nd"    <?php echo $semester_filter==='2nd'?'selected':''; ?>>2nd Semester</option>
                                    <option value="summer" <?php echo $semester_filter==='summer'?'selected':''; ?>>Summer</option>
                                </select>
                                <input type="text" name="search" class="header-search-input"
                                    placeholder="Search code, section, room..."
                                    value="<?php echo htmlspecialchars($search); ?>">
                                <button type="submit" class="btn-secondary" style="padding:0.45rem 0.75rem;">
                                    <i class="fa-solid fa-search"></i>
                                </button>
                                <?php if ($search || $semester_filter || $year_filter): ?>
                                    <a href="?filter=<?php echo $filter; ?>" class="btn-secondary" style="padding:0.45rem 0.75rem;">Clear</a>
                                <?php endif; ?>
                            </form>
                            <button class="btn-secondary" onclick="openAdd()">
                                <i class="fa-solid fa-plus"></i>
                                <span class="li-name">Add Class</span>
                            </button>
                        </div>
                    </div>

                    <div class="filter-tabs">
                        <?php $base = '?search=' . urlencode($search) . '&semester=' . urlencode($semester_filter) . '&year=' . urlencode($year_filter); ?>
                        <a href="<?php echo $base; ?>&filter=all"       class="filter-tab <?php echo $filter==='all'?'active':''; ?>">All</a>
                        <a href="<?php echo $base; ?>&filter=open"      class="filter-tab <?php echo $filter==='open'?'active':''; ?>">Open</a>
                        <a href="<?php echo $base; ?>&filter=closed"    class="filter-tab <?php echo $filter==='closed'?'active':''; ?>">Closed</a>
                        <a href="<?php echo $base; ?>&filter=cancelled" class="filter-tab <?php echo $filter==='cancelled'?'active':''; ?>">Cancelled</a>
                    </div>

                    <div class="classes-table-wrapper">
                        <div class="classes-table">

                            <div class="classes-table-header">
                                <div class="classes-col-left">Subject</div>
                                <div>Section</div>
                                <div class="classes-col-left">Faculty</div>
                                <div>Schedule</div>
                                <div>Room</div>
                                <div>Capacity</div>
                                <div>Semester</div>
                                <div>Status</div>
                                <div>Actions</div>
                            </div>

                            <div class="classes-table-body">
                            <?php if (mysqli_num_rows($classes) === 0): ?>
                                <div class="classes-empty">No classes found.</div>
                            <?php else: ?>
                            <?php while ($cls = mysqli_fetch_assoc($classes)):
                                $capacity_pct   = $cls['max_slots'] > 0 ? ($cls['real_enrolled'] / $cls['max_slots']) * 100 : 0;
                                $capacity_class = $capacity_pct >= 100 ? 'full' : ($capacity_pct >= 80 ? 'almost' : 'available');
                                $js = htmlspecialchars(json_encode($cls), ENT_QUOTES);
                            ?>
                            <div class="classes-row">
                                <div class="classes-col-left">
                                    <strong><?php echo htmlspecialchars($cls['subject_code']); ?></strong>
                                    <span class="subject-name-small"><?php echo htmlspecialchars($cls['subject_name']); ?></span>
                                </div>
                                <div><?php echo htmlspecialchars($cls['section']); ?></div>
                                <div class="classes-col-left"><?php echo htmlspecialchars($cls['faculty_name'] ?? 'TBA'); ?></div>
                                <div>
                                    <?php if ($cls['schedule_day']): ?>
                                        <?php echo htmlspecialchars($cls['schedule_day']); ?>
                                        <span class="schedule-time-small"><?php echo htmlspecialchars($cls['schedule_time'] ?? ''); ?></span>
                                    <?php else: ?>
                                        <span style="color:var(--text-label);">TBA</span>
                                    <?php endif; ?>
                                </div>
                                <div><?php echo htmlspecialchars($cls['room'] ?? 'TBA'); ?></div>
                                <div>
                                    <span class="capacity-badge <?php echo $capacity_class; ?>">
                                        <?php echo $cls['real_enrolled']; ?>/<?php echo $cls['max_slots']; ?>
                                    </span>
                                </div>
                                <div><?php echo htmlspecialchars($cls['semester'] . ' ' . $cls['school_year']); ?></div>
                                <div>
                                    <span class="badge <?php echo $cls['status']; ?>">
                                        <?php echo ucfirst($cls['status']); ?>
                                    </span>
                                </div>
                                <div>
                                    <div class="action-buttons">
                                        <button class="btn-icon" title="View Students"
                                                onclick="viewStudents(<?php echo $cls['class_id']; ?>, '<?php echo htmlspecialchars($cls['subject_code'], ENT_QUOTES); ?>')">
                                            <i class="fa-solid fa-users"></i>
                                        </button>
                                        <button class="btn-icon" title="Edit"
                                                onclick="openEdit('<?php echo $js; ?>')">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <form method="POST" action="../../php/admin_classes_handler.php" style="display:inline;">
                                            <input type="hidden" name="action"     value="toggle_status">
                                            <input type="hidden" name="class_id"   value="<?php echo $cls['class_id']; ?>">
                                            <input type="hidden" name="new_status" value="<?php echo $cls['status']==='open'?'closed':'open'; ?>">
                                            <button type="submit" class="btn-icon"
                                                    title="<?php echo $cls['status']==='open'?'Close':'Open'; ?>">
                                                <i class="fa-solid <?php echo $cls['status']==='open'?'fa-lock-open':'fa-lock'; ?>"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="../../php/admin_classes_handler.php" style="display:inline;"
                                            onsubmit="return confirm('Delete this class? This cannot be undone.')">
                                            <input type="hidden" name="action"   value="delete">
                                            <input type="hidden" name="class_id" value="<?php echo $cls['class_id']; ?>">
                                            <button type="submit" class="btn-icon danger" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                            <?php endif; ?>
                            </div>

                        </div>
                    </div>
                </div>

            </div><!-- /.main-content -->
        </main>
    </div><!-- /.main-flex -->

    <!-- ── Add / Edit Class Modal ─────────────────────── -->
    <div id="formModal" class="modal">
        <div class="modal-content class-modal">
            <span class="close" onclick="closeModal('formModal')">&times;</span>
            <h2 style="font-family: 'DM Serif Display', serif;margin-bottom:1.5rem;" id="formModalTitle">Add Class</h2>

            <form method="POST" action="../../php/admin_classes_handler.php">
                <input type="hidden" name="action"   id="form_action"   value="add">
                <input type="hidden" name="class_id" id="form_class_id">
                <input type="hidden" name="subject_id" id="form_subject_id">

                <!-- Course selector -->
                <div class="form-group" id="course_selector_group">
                    <label>Course <span style="color:var(--red)">*</span></label>
                    <select id="modal_course_select" class="modal-filter-select" onchange="loadCourseSubjects()" style="width:100%;">
                        <option value="">— Select a course to view subjects —</option>
                        <?php
                        $cur_college = '';
                        foreach ($modal_courses as $mc):
                            if ($cur_college !== $mc['college_name']) {
                                if ($cur_college !== '') echo '</optgroup>';
                                echo '<optgroup label="' . htmlspecialchars($mc['college_name']) . '">';
                                $cur_college = $mc['college_name'];
                            }
                        ?>
                            <option value="<?php echo $mc['course_id']; ?>"><?php echo htmlspecialchars($mc['course_code'] . ' — ' . $mc['course_name']); ?></option>
                        <?php endforeach; if ($cur_college !== '') echo '</optgroup>'; ?>
                    </select>
                </div>

                <!-- Subject checklist (populated by JS) -->
                <div id="subject_checklist_wrap" style="display:none;">
                    <div class="subject-filter-box">
                        <p class="filter-box-label"><i class="fa-solid fa-book-open"></i> Select Subject</p>
                        <div id="subject_checklist"></div>
                    </div>
                    <p id="subject_locked_note" style="display:none;font-size:0.75rem;color:var(--gold);margin-top:0.4rem;background:rgba(212,175,55,0.1);padding:0.5rem;border-radius:4px;">
                        <i class="fa-solid fa-lock"></i> Subject cannot be changed when editing a class.
                    </p>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Section <span style="color:var(--red)">*</span></label>
                        <input type="text" name="section" id="form_section" placeholder="e.g., BSIT 1-A" required>
                    </div>
                    <div class="form-group">
                        <label>Faculty</label>
                        <select name="faculty_id" id="form_faculty_id">
                            <option value="">TBA</option>
                            <?php foreach ($faculty as $fac): ?>
                                <option value="<?php echo $fac['faculty_id']; ?>">
                                    <?php echo htmlspecialchars($fac['last_name'] . ', ' . $fac['first_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>School Year <span style="color:var(--red)">*</span></label>
                        <input type="text" name="school_year" id="form_school_year" placeholder="e.g., 2024-2025" value="<?php echo htmlspecialchars(get_setting($con, 'current_school_year', date('Y') . '-' . (date('Y')+1))); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Semester <span style="color:var(--red)">*</span></label>
                        <select name="semester" id="form_semester" required>
                            <option value="">Select Semester</option>
                            <option value="1st">1st Semester</option>
                            <option value="2nd">2nd Semester</option>
                            <option value="summer">Summer</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Schedule Day</label>
                    <div class="day-checkboxes">
                        <?php foreach (['M'=>'Mon','T'=>'Tue','W'=>'Wed','TH'=>'Thu','F'=>'Fri','S'=>'Sat','SU'=>'Sun'] as $val => $lbl): ?>
                        <label class="day-cb-label">
                            <input type="checkbox" name="schedule_days[]" value="<?php echo $val; ?>" class="day-cb">
                            <span><?php echo $lbl; ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label>Schedule Time</label>
                    <input type="text" name="schedule_time" id="form_schedule_time" placeholder="e.g., 8:00 AM - 10:00 AM">
                </div>

                <div class="form-grid-3">
                    <div class="form-group">
                        <label>Room</label>
                        <input type="text" name="room" id="form_room" placeholder="e.g., GV 208">
                    </div>
                    <div class="form-group">
                        <label>Max Slots <span style="color:var(--red)">*</span></label>
                        <input type="number" name="max_slots" id="form_max_slots" min="1" max="100" value="40" required>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="form_status">
                            <option value="open">Open</option>
                            <option value="closed">Closed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="submit" class="btn-submit" id="formSubmitBtn">Create Class</button>
                    <button type="button" class="btn-secondary" onclick="closeModal('formModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ── View Students Modal ─────────────────────── -->
    <div id="studentsModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <span class="close" onclick="closeModal('studentsModal')">&times;</span>
            <h2 style="font-family: 'DM Serif Display', serif;margin-bottom:1.5rem;" id="studentsModalTitle">Enrolled Students</h2>
            
            <div id="studentsContent">
                <div style="text-align:center;padding:2rem;color:var(--text-label);">
                    <i class="fa-solid fa-spinner fa-spin" style="font-size:2rem;"></i>
                    <p style="margin-top:1rem;">Loading students...</p>
                </div>
            </div>
            
            <div class="modal-actions" style="margin-top:1.5rem;">
                <button type="button" class="btn-secondary" onclick="closeModal('studentsModal')">Close</button>
            </div>
        </div>
    </div>

    <script>
    const SUBJECTS_BY_COURSE = <?php echo json_encode($subjects_by_course); ?>;
    const YEAR_LABELS = {"1":"1st Year","2":"2nd Year","3":"3rd Year","4":"4th Year","5":"5th Year","6":"6th Year","0":"Unassigned"};
    const SEM_LABELS  = {"1st":"1st Semester","2nd":"2nd Semester","summer":"Summer","N/A":"Unassigned"};
    const CURRENT_SCHOOL_YEAR = <?php echo json_encode(get_setting($con, 'current_school_year', date('Y').'-'.(date('Y')+1))); ?>;
    const CURRENT_SEMESTER    = <?php echo json_encode(get_setting($con, 'current_semester', '1st')); ?>;
    </script>
    <script src="../../js/admin/admin_main.js"></script>
    <script src="../../js/admin/admin_classes.js"></script>
</body>
</html>