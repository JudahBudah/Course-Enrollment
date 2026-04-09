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
$total_enrolled = mysqli_fetch_assoc(mysqli_query($con, "SELECT SUM(enrolled_count) as s FROM classes"))['s'] ?? 0;

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
           CONCAT(f.first_name, ' ', f.last_name) as faculty_name
    FROM classes c
    JOIN subjects s ON c.subject_id = s.subject_id
    LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
    $where
    ORDER BY c.school_year DESC, c.semester ASC, s.subject_code ASC
");

// Subjects for dropdown (add/edit modal)
$subjects_query = mysqli_query($con, "
    SELECT subject_id, subject_code, subject_name, year_level, semester, department
    FROM subjects WHERE status = 'active'
    ORDER BY department, year_level, semester, subject_code
");
$subjects = [];
while ($s = mysqli_fetch_assoc($subjects_query)) $subjects[] = $s;

// Departments for modal filter
$dept_query = mysqli_query($con, "
    SELECT DISTINCT department FROM subjects
    WHERE status = 'active' AND department IS NOT NULL AND department != ''
    ORDER BY department
");
$modal_departments = [];
while ($d = mysqli_fetch_assoc($dept_query)) $modal_departments[] = $d['department'];

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
    <link rel="stylesheet" href="../../css/admin/admin_main.css">
    <link rel="stylesheet" href="../../css/admin/admin_classes.css">
</head>
<body>

    <!-- ── Top Nav Bar ────────────────────────────────── -->
    <header>
        <div class="nav-section">
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
                    <li>
                        <a href="admin_students.php">
                            <i class="fa-solid fa-users"></i>
                            <span class="li-name">Students</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_blocks.php">
                            <i class="fa-solid fa-layer-group"></i>
                            <span class="li-name">Blocks</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_faculty.php">
                            <i class="fa-solid fa-chalkboard-user"></i>
                            <span class="li-name">Faculty</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_subjects.php">
                            <i class="fa-solid fa-book"></i>
                            <span class="li-name">Subjects</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_classes.php" class="active">
                            <i class="fa-solid fa-door-open"></i>
                            <span class="li-name">Classes</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_enrollments.php">
                            <i class="fa-solid fa-file-lines"></i>
                            <span class="li-name">Enrollments</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_announcements.php">
                            <i class="fa-solid fa-bullhorn"></i>
                            <span class="li-name">Announcements</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_calendar.php">
                            <i class="fa-solid fa-calendar-days"></i>
                            <span class="li-name">Calendar</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_accounts.php">
                            <i class="fa-solid fa-user-shield"></i>
                            <span class="li-name">Admin Accounts</span>
                        </a>
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
                        <?php
                        $base = '?search=' . urlencode($search) . '&semester=' . urlencode($semester_filter) . '&year=' . urlencode($year_filter);
                        ?>
                        <a href="<?php echo $base; ?>&filter=all"       class="filter-tab <?php echo $filter==='all'?'active':''; ?>">All</a>
                        <a href="<?php echo $base; ?>&filter=open"      class="filter-tab <?php echo $filter==='open'?'active':''; ?>">Open</a>
                        <a href="<?php echo $base; ?>&filter=closed"    class="filter-tab <?php echo $filter==='closed'?'active':''; ?>">Closed</a>
                        <a href="<?php echo $base; ?>&filter=cancelled" class="filter-tab <?php echo $filter==='cancelled'?'active':''; ?>">Cancelled</a>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Section</th>
                                    <th>Faculty</th>
                                    <th>Schedule</th>
                                    <th>Room</th>
                                    <th>Capacity</th>
                                    <th>Semester</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (mysqli_num_rows($classes) === 0): ?>
                                <tr>
                                    <td colspan="9" style="text-align:center;color:var(--text-label);padding:2rem;">
                                        No classes found.
                                    </td>
                                </tr>
                            <?php else: ?>
                            <?php while ($cls = mysqli_fetch_assoc($classes)):
                                $capacity_pct   = $cls['max_slots'] > 0 ? ($cls['enrolled_count'] / $cls['max_slots']) * 100 : 0;
                                $capacity_class = $capacity_pct >= 100 ? 'full' : ($capacity_pct >= 80 ? 'almost' : 'available');
                                $js = htmlspecialchars(json_encode($cls), ENT_QUOTES);
                            ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($cls['subject_code']); ?></strong>
                                        <span class="subject-name-small"><?php echo htmlspecialchars($cls['subject_name']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($cls['section']); ?></td>
                                    <td><?php echo htmlspecialchars($cls['faculty_name'] ?? 'TBA'); ?></td>
                                    <td>
                                        <?php if ($cls['schedule_day']): ?>
                                            <?php echo htmlspecialchars($cls['schedule_day']); ?>
                                            <span class="schedule-time-small"><?php echo htmlspecialchars($cls['schedule_time'] ?? ''); ?></span>
                                        <?php else: ?>
                                            <span style="color:var(--text-label);">TBA</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($cls['room'] ?? 'TBA'); ?></td>
                                    <td>
                                        <span class="capacity-badge <?php echo $capacity_class; ?>">
                                            <?php echo $cls['enrolled_count']; ?>/<?php echo $cls['max_slots']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($cls['semester'] . ' ' . $cls['school_year']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $cls['status']; ?>">
                                            <?php echo ucfirst($cls['status']); ?>
                                        </span>
                                    </td>
                                    <td>
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
                                                    <i class="fa-solid <?php echo $cls['status']==='open'?'fa-lock':'fa-lock-open'; ?>"></i>
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
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div><!-- /.main-content -->
        </main>
    </div><!-- /.main-flex -->

    <!-- ── Add / Edit Class Modal ─────────────────────── -->
    <div id="formModal" class="modal">
        <div class="modal-content class-modal">
            <span class="close" onclick="closeModal('formModal')">&times;</span>
            <h2 style="font-family:'Playfair Display',serif;margin-bottom:1.5rem;" id="formModalTitle">Add Class</h2>

            <form method="POST" action="../../php/admin_classes_handler.php">
                <input type="hidden" name="action"   id="form_action"   value="add">
                <input type="hidden" name="class_id" id="form_class_id">

                <!-- Subject filter box -->
                <div class="subject-filter-box">
                    <p class="filter-box-label">
                        <i class="fa-solid fa-filter"></i> Filter Subjects:
                    </p>
                    <div class="form-grid-3">
                        <div>
                            <label style="font-size:0.75rem;color:var(--text-label);margin-bottom:0.3rem;display:block;">Department</label>
                            <select id="filter_dept" class="modal-filter-select" onchange="filterSubjects()">
                                <option value="">All Departments</option>
                                <?php foreach ($modal_departments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="font-size:0.75rem;color:var(--text-label);margin-bottom:0.3rem;display:block;">Year Level</label>
                            <select id="filter_year" class="modal-filter-select" onchange="filterSubjects()">
                                <option value="">All Years</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-size:0.75rem;color:var(--text-label);margin-bottom:0.3rem;display:block;">Semester</label>
                            <select id="filter_sem" class="modal-filter-select" onchange="filterSubjects()">
                                <option value="">All Semesters</option>
                                <option value="1st">1st Semester</option>
                                <option value="2nd">2nd Semester</option>
                                <option value="summer">Summer</option>
                            </select>
                        </div>
                    </div>
                    <div style="margin-top:0.75rem;">
                        <label style="font-size:0.75rem;color:var(--text-label);margin-bottom:0.3rem;display:block;">Search Subject</label>
                        <input type="text" id="filter_search" class="modal-filter-select" placeholder="Type to search subject code or name..." oninput="filterSubjects()" style="width:100%;">
                    </div>
                    <p class="filter-count-note" id="filter_count">Showing all subjects</p>
                </div>

                <div class="form-group">
                    <label>Subject <span style="color:var(--red)">*</span></label>
                    <select name="subject_id" id="form_subject_id" required size="8" style="height:auto;">
                        <option value="" disabled selected>Select a subject from the list below</option>
                        <?php foreach ($subjects as $subj): ?>
                            <option value="<?php echo $subj['subject_id']; ?>"
                                    data-year="<?php echo $subj['year_level']; ?>"
                                    data-sem="<?php echo $subj['semester']; ?>"
                                    data-dept="<?php echo htmlspecialchars($subj['department']); ?>"
                                    data-code="<?php echo htmlspecialchars($subj['subject_code']); ?>"
                                    data-name="<?php echo htmlspecialchars($subj['subject_name']); ?>">
                                <?php echo htmlspecialchars($subj['subject_code']); ?> - <?php echo htmlspecialchars($subj['subject_name']); ?>
                                <?php if ($subj['department']): ?>
                                    (<?php echo htmlspecialchars($subj['department']); ?><?php echo $subj['year_level'] ? ', Year ' . $subj['year_level'] : ''; ?><?php echo $subj['semester'] ? ', ' . $subj['semester'] : ''; ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p style="font-size:0.75rem;color:var(--text-label);margin-top:0.4rem;">
                        <i class="fa-solid fa-info-circle"></i> Use the filters above to narrow down subjects, then select from the list
                    </p>
                    <p id="subject_locked_note" style="display:none;font-size:0.75rem;color:var(--gold);margin-top:0.4rem;background:rgba(212,175,55,0.1);padding:0.5rem;border-radius:4px;">
                        <i class="fa-solid fa-lock"></i> Subject cannot be changed when editing a class. Create a new class if you need a different subject.
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
                        <input type="text" name="school_year" id="form_school_year" placeholder="e.g., 2024-2025" value="2024-2025" required>
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

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Schedule Day</label>
                        <input type="text" name="schedule_day"  id="form_schedule_day"  placeholder="e.g., MW, TTH, THS, MWF, Monday Tuesday">
                    </div>
                    <div class="form-group">
                        <label>Schedule Time</label>
                        <input type="text" name="schedule_time" id="form_schedule_time" placeholder="e.g., 8:00 AM - 10:00 AM">
                    </div>
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

                <div class="form-group">
                    <label>Class Availability</label>
                    <div style="margin-bottom:0.5rem;">
                        <label style="display:flex;align-items:center;gap:0.5rem;font-weight:normal;cursor:pointer;">
                            <input type="radio" name="availability_type" value="all" checked onchange="toggleDepartmentSelect()">
                            <span>Available to all departments (e.g., PE, GE subjects)</span>
                        </label>
                    </div>
                    <div>
                        <label style="display:flex;align-items:center;gap:0.5rem;font-weight:normal;cursor:pointer;">
                            <input type="radio" name="availability_type" value="specific" onchange="toggleDepartmentSelect()">
                            <span>Restrict to specific department only</span>
                        </label>
                    </div>
                </div>

                <div class="form-group" id="specific_dept_group" style="display:none;">
                    <label>Specific Department <span style="color:var(--red)">*</span></label>
                    <select name="specific_department" id="form_specific_department">
                        <option value="">Select Department</option>
                        <?php foreach ($modal_departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p style="font-size:0.75rem;color:var(--text-label);margin-top:0.4rem;">
                        <i class="fa-solid fa-info-circle"></i> Only students from this department can see and enroll in this class
                    </p>
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
            <h2 style="font-family:'Playfair Display',serif;margin-bottom:1.5rem;" id="studentsModalTitle">Enrolled Students</h2>
            
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

    <script src="../../js/admin/admin_main.js"></script>
    <script src="../../js/admin/admin_classes.js"></script>
</body>
</html>