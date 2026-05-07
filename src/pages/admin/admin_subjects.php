<?php
session_start();
include("../../php/connection.php");
include("../../php/admin_functions.php");

$admin_data = check_admin_login($con);

$pending_applicants = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM applicants WHERE application_status = 'pending'"))['c'];

// Flash messages
$flash_errors = [
    'missing_fields' => 'Please fill in all required fields.',
    'duplicate_code' => 'Subject code already exists.',
    'insert_failed'  => 'Failed to add subject. Please try again.',
    'update_failed'  => 'Failed to update subject. Please try again.',
    'in_use'         => 'Cannot delete — subject is assigned to an existing class.',
];
$flash = '';
if (isset($_GET['error']) && isset($flash_errors[$_GET['error']])) {
    $flash = '<div class="error-message"><i class="fa-solid fa-circle-exclamation"></i> ' . $flash_errors[$_GET['error']] . '</div>';
}
if (isset($_GET['success'])) {
    $msgs = [
        'added'   => 'Subject added successfully.',
        'updated' => 'Subject updated successfully.',
        'deleted' => 'Subject deleted successfully.',
    ];
    $flash = '<div class="success-message"><i class="fa-solid fa-check-circle"></i> ' . ($msgs[$_GET['success']] ?? 'Done.') . '</div>';
}

// Stats
$total_subjects    = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM subjects"))['c'];
$active_subjects   = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM subjects WHERE status = 'active'"))['c'];
$inactive_subjects = $total_subjects - $active_subjects;
$total_units_row   = mysqli_fetch_assoc(mysqli_query($con, "SELECT SUM(units) as s FROM subjects WHERE status = 'active'"));
$total_units       = $total_units_row['s'] ?? 0;

// Search & filters
$search       = trim($_GET['search'] ?? '');
$filter       = $_GET['filter']       ?? 'all';
$course_filter = $_GET['course']      ?? '';
$year_filter  = $_GET['year']         ?? '';

$where = "WHERE 1=1";
if ($filter === 'active')   $where .= " AND s.status = 'active'";
if ($filter === 'inactive') $where .= " AND s.status = 'inactive'";
if ($course_filter !== '')  $where .= " AND s.course_id = '" . mysqli_real_escape_string($con, $course_filter) . "'";
if ($year_filter !== '')    $where .= " AND s.year_level = '" . mysqli_real_escape_string($con, $year_filter) . "'";
if ($search !== '') {
    $s = mysqli_real_escape_string($con, $search);
    $where .= " AND (s.subject_code LIKE '%$s%' OR s.subject_name LIKE '%$s%' OR s.department LIKE '%$s%' OR s.prerequisite LIKE '%$s%' OR c.course_code LIKE '%$s%')";
}

$subjects = mysqli_query($con, "
    SELECT s.*, c.course_code, c.course_name 
    FROM subjects s 
    LEFT JOIN courses c ON s.course_id = c.course_id 
    $where 
    ORDER BY s.year_level ASC, s.semester ASC, s.subject_code ASC
");

// Get courses for filter dropdown — all active courses, not just those with subjects
$courses_filter_query = mysqli_query($con, "SELECT course_id, course_code, course_name, college_name FROM courses WHERE status = 'active' ORDER BY college_name, course_code");
$courses_filter = [];
while ($cf = mysqli_fetch_assoc($courses_filter_query)) $courses_filter[] = $cf;

// Get courses from courses table
$courses_query = mysqli_query($con, "SELECT course_id, course_code, course_name, college_name FROM courses WHERE status = 'active' ORDER BY college_name, course_name");
$courses = [];
while ($row = mysqli_fetch_assoc($courses_query)) {
    $courses[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subjects Management - PLM Admin</title>
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
    <link rel="stylesheet" href="../../css/admin/admin_subjects.css">
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

                    <?php if (($admin_data['role'] ?? '') === 'superadmin'): ?>
                    <li>
                        <a href="admin_settings.php" class="superadmin-link">
                            <i class="fa-solid fa-sliders"></i>
                            <span class="li-name">System Settings</span>
                        </a>
                    </li>
                    <?php endif; ?>
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
                    <h1>Subjects Management</h1>
                    <p>Manage curriculum subjects, units, and schedules</p>
                </div>

                <?php echo $flash; ?>

                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card blue">
                        <div class="stat-icon"><i class="fa-solid fa-book"></i></div>
                        <div class="stat-content"><h3>Total Subjects</h3><p class="stat-number"><?php echo $total_subjects; ?></p></div>
                    </div>
                    <div class="stat-card green">
                        <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
                        <div class="stat-content"><h3>Active</h3><p class="stat-number"><?php echo $active_subjects; ?></p></div>
                    </div>
                    <div class="stat-card gold">
                        <div class="stat-icon"><i class="fa-solid fa-calculator"></i></div>
                        <div class="stat-content"><h3>Total Active Units</h3><p class="stat-number"><?php echo $total_units; ?></p></div>
                    </div>
                    <div class="stat-card red">
                        <div class="stat-icon"><i class="fa-solid fa-circle-xmark"></i></div>
                        <div class="stat-content"><h3>Inactive</h3><p class="stat-number"><?php echo $inactive_subjects; ?></p></div>
                    </div>
                </div>

                <!-- Subjects Table Card -->
                <div class="card">
                    <div class="card-header">
                        <h2>All Subjects</h2>
                        <div class="card-header-actions">
                            <form method="GET" class="header-filter-form">
                                <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                                <select name="year" class="dept-select" onchange="this.form.submit()">
                                    <option value="">All Years</option>
                                    <option value="1" <?php echo $year_filter==='1'?'selected':''; ?>>1st Year</option>
                                    <option value="2" <?php echo $year_filter==='2'?'selected':''; ?>>2nd Year</option>
                                    <option value="3" <?php echo $year_filter==='3'?'selected':''; ?>>3rd Year</option>
                                    <option value="4" <?php echo $year_filter==='4'?'selected':''; ?>>4th Year</option>
                                    <option value="5" <?php echo $year_filter==='5'?'selected':''; ?>>5th Year</option>
                                    <option value="6" <?php echo $year_filter==='6'?'selected':''; ?>>6th Year</option>
                                </select>
                                <select name="course" class="dept-select" onchange="this.form.submit()">
                                    <option value="">All Courses</option>
                                    <?php
                                    $cur_college_filter = '';
                                    foreach ($courses_filter as $cf):
                                        if ($cur_college_filter !== $cf['college_name']) {
                                            if ($cur_college_filter !== '') echo '</optgroup>';
                                            echo '<optgroup label="' . htmlspecialchars($cf['college_name']) . '">';
                                            $cur_college_filter = $cf['college_name'];
                                        }
                                    ?>
                                        <option value="<?php echo $cf['course_id']; ?>" <?php echo $course_filter==$cf['course_id']?'selected':''; ?>>
                                            <?php echo htmlspecialchars($cf['course_code'] . ' — ' . $cf['course_name']); ?>
                                        </option>
                                    <?php endforeach; if ($cur_college_filter !== '') echo '</optgroup>'; ?>
                                </select>
                                <input type="text" name="search" class="header-search-input"
                                    placeholder="Search code, name, course..."
                                    value="<?php echo htmlspecialchars($search); ?>">
                                <button type="submit" class="btn-secondary" style="padding:0.45rem 0.75rem;">
                                    <i class="fa-solid fa-search"></i>
                                </button>
                                <?php if ($search || $course_filter || $year_filter): ?>
                                    <a href="?filter=<?php echo $filter; ?>" class="btn-secondary" style="padding:0.45rem 0.75rem;">Clear</a>
                                <?php endif; ?>
                            </form>
                            <a href="admin_subjects_batch_import.php" class="btn-secondary btn-import">
                                <i class="fa-solid fa-layer-group"></i>
                                <span class="li-name">Batch Create</span>
                            </a>
                            <button class="btn-secondary" onclick="openAdd()">
                                <i class="fa-solid fa-plus"></i>
                                <span class="li-name">Add Subject</span>
                            </button>
                        </div>
                    </div>

                    <div class="filter-tabs">
                        <a href="?filter=all&search=<?php echo urlencode($search); ?>&course=<?php echo urlencode($course_filter); ?>&year=<?php echo urlencode($year_filter); ?>"
                        class="filter-tab <?php echo $filter==='all'?'active':''; ?>">All</a>
                        <a href="?filter=active&search=<?php echo urlencode($search); ?>&course=<?php echo urlencode($course_filter); ?>&year=<?php echo urlencode($year_filter); ?>"
                        class="filter-tab <?php echo $filter==='active'?'active':''; ?>">Active</a>
                        <a href="?filter=inactive&search=<?php echo urlencode($search); ?>&course=<?php echo urlencode($course_filter); ?>&year=<?php echo urlencode($year_filter); ?>"
                        class="filter-tab <?php echo $filter==='inactive'?'active':''; ?>">Inactive</a>
                    </div>

                    <div class="subjects-table-wrapper">
                        <div class="subjects-table">

                            <div class="subjects-table-header">
                                <div>Code</div>
                                <div class="subjects-col-left">Subject Name</div>
                                <div>Units</div>
                                <div>Lec / Lab Hrs</div>
                                <div class="subjects-col-left">Department</div>
                                <div>Year</div>
                                <div>Semester</div>
                                <div class="subjects-col-left">Prerequisite</div>
                                <div>Status</div>
                                <div>Actions</div>
                            </div>

                            <div class="subjects-table-body">
                            <?php if (mysqli_num_rows($subjects) === 0): ?>
                                <div class="subjects-empty">No subjects found.</div>
                            <?php else: ?>
                            <?php while ($sub = mysqli_fetch_assoc($subjects)):
                                $yr_class = $sub['year_level'] ? 'yr' . $sub['year_level'] : 'incomplete';
                                $js = htmlspecialchars(json_encode($sub), ENT_QUOTES);
                            ?>
                            <div class="subjects-row">
                                <div><strong><?php echo htmlspecialchars($sub['subject_code']); ?></strong></div>
                                <div class="subjects-col-left"><?php echo htmlspecialchars($sub['subject_name']); ?></div>
                                <div><span class="units-badge"><?php echo $sub['units']; ?> units</span></div>
                                <div><?php echo $sub['lecture_hours']; ?> / <?php echo $sub['lab_hours']; ?></div>
                                <div class="subjects-col-left"><?php echo htmlspecialchars($sub['department'] ?? '—'); ?></div>
                                <div>
                                    <?php if ($sub['year_level']): ?>
                                        <span class="badge <?php echo $yr_class; ?>">Year <?php echo $sub['year_level']; ?></span>
                                    <?php else: ?>
                                        <span style="color:var(--text-label);">—</span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <?php echo $sub['semester']
                                        ? htmlspecialchars(ucfirst($sub['semester']))
                                        : '<span style="color:var(--text-label);">—</span>'; ?>
                                </div>
                                <div class="subjects-col-left"><?php echo htmlspecialchars($sub['prerequisite'] ?: '—'); ?></div>
                                <div><span class="badge <?php echo $sub['status']; ?>"><?php echo ucfirst($sub['status']); ?></span></div>
                                <div>
                                    <div class="action-buttons">
                                        <button class="btn-icon" title="View"
                                                onclick="openView('<?php echo $js; ?>')">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <button class="btn-icon" title="Edit"
                                                onclick="openEdit('<?php echo $js; ?>')">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <form method="POST" action="../../php/admin_subjects_handler.php" style="display:inline;">
                                            <input type="hidden" name="action"     value="toggle_status">
                                            <input type="hidden" name="subject_id" value="<?php echo $sub['subject_id']; ?>">
                                            <input type="hidden" name="new_status" value="<?php echo $sub['status']==='active'?'inactive':'active'; ?>">
                                            <input type="hidden" name="_filter" value="<?php echo htmlspecialchars($filter); ?>">
                                            <input type="hidden" name="_year"   value="<?php echo htmlspecialchars($year_filter); ?>">
                                            <input type="hidden" name="_course" value="<?php echo htmlspecialchars($course_filter); ?>">
                                            <input type="hidden" name="_search" value="<?php echo htmlspecialchars($search); ?>">
                                            <button type="submit"
                                                    class="btn-icon <?php echo $sub['status']==='active'?'toggle-on':'toggle-off'; ?>"
                                                    title="<?php echo $sub['status']==='active'?'Deactivate':'Activate'; ?>">
                                                <i class="fa-solid <?php echo $sub['status']==='active'?'fa-toggle-on':'fa-toggle-off'; ?>"></i>
                                            </button>
                                        </form>
                                        <button class="btn-icon danger" title="Delete"
                                                onclick="confirmDelete(
                                                    <?php echo $sub['subject_id']; ?>,
                                                    '<?php echo htmlspecialchars(addslashes($sub['subject_code'])); ?>',
                                                    '<?php echo htmlspecialchars(addslashes($sub['subject_name'])); ?>'
                                                )">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
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

    <!-- ── View Modal ─────────────────────────────────── -->
    <div id="viewModal" class="modal">
        <div class="modal-content wide">
            <span class="close" onclick="closeModal('viewModal')">&times;</span>
            <h2 class="modal-subject-title" id="vw_title"></h2>
            <p class="modal-subject-subtitle" id="vw_code_line"></p>

            <div class="view-grid">
                <div class="sec-label">Subject Details</div>
                <div class="view-item full"><label>Subject Name</label><span id="vw_subject_name"></span></div>
                <div class="view-item"><label>Subject Code</label><span id="vw_subject_code"></span></div>
                <div class="view-item"><label>Units</label><span id="vw_units"></span></div>
                <div class="view-item"><label>Lecture Hours</label><span id="vw_lecture_hours"></span></div>
                <div class="view-item"><label>Lab Hours</label><span id="vw_lab_hours"></span></div>
                <div class="view-item"><label>Total Hours</label><span id="vw_total_hours"></span></div>

                <div class="sec-label">Classification</div>
                <div class="view-item"><label>Department</label><span id="vw_department"></span></div>
                <div class="view-item"><label>Year Level</label><span id="vw_year_level"></span></div>
                <div class="view-item"><label>Semester</label><span id="vw_semester"></span></div>
                <div class="view-item"><label>Prerequisite</label><span id="vw_prerequisite"></span></div>
                <div class="view-item"><label>Status</label><span id="vw_status"></span></div>

                <div class="sec-label">Description</div>
                <div class="view-item full"><label>Description</label><span id="vw_description"></span></div>
            </div>

            <div class="modal-actions" style="margin-top:1.5rem;">
                <button class="btn-secondary" onclick="closeModal('viewModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- ── Add / Edit Modal ───────────────────────────── -->
    <div id="formModal" class="modal">
        <div class="modal-content wide">
            <span class="close" onclick="closeModal('formModal')">&times;</span>
            <h2 class="modal-subject-title" id="formModalTitle">Add Subject</h2>

            <form method="POST" action="../../php/admin_subjects_handler.php">
                <input type="hidden" name="action"     id="form_action"     value="add">
                <input type="hidden" name="subject_id" id="form_subject_id">
                <input type="hidden" name="_filter" value="<?php echo htmlspecialchars($filter); ?>">
                <input type="hidden" name="_year"   value="<?php echo htmlspecialchars($year_filter); ?>">
                <input type="hidden" name="_course" value="<?php echo htmlspecialchars($course_filter); ?>">
                <input type="hidden" name="_search" value="<?php echo htmlspecialchars($search); ?>">

                <p class="form-section-title">Basic Information</p>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Subject Code <span style="color:var(--red)">*</span></label>
                        <input type="text" name="subject_code" id="form_subject_code" placeholder="e.g. CS101" required>
                    </div>
                    <div class="form-group">
                        <label>Subject Name <span style="color:var(--red)">*</span></label>
                        <input type="text" name="subject_name" id="form_subject_name" placeholder="e.g. Introduction to Computing" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="form_description" placeholder="Brief description of the subject..."></textarea>
                </div>

                <p class="form-section-title">Units &amp; Hours</p>
                <div class="form-grid-3">
                    <div class="form-group">
                        <label>Units <span style="color:var(--red)">*</span></label>
                        <input type="number" name="units" id="form_units" min="1" max="9" value="3" required>
                    </div>
                    <div class="form-group">
                        <label>Lecture Hours</label>
                        <input type="number" name="lecture_hours" id="form_lecture_hours" min="0" max="9" step="0.5" value="3.0">
                    </div>
                    <div class="form-group">
                        <label>Lab Hours</label>
                        <input type="number" name="lab_hours" id="form_lab_hours" min="0" max="9" step="0.5" value="0.0">
                    </div>
                </div>

                <p class="form-section-title">Classification</p>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Course</label>
                        <select name="course_id" id="form_course_id">
                            <option value="">Not specified</option>
                            <?php 
                            $current_college = '';
                            foreach ($courses as $course): 
                                if ($current_college !== $course['college_name']) {
                                    if ($current_college !== '') echo '</optgroup>';
                                    echo '<optgroup label="' . htmlspecialchars($course['college_name']) . '">';
                                    $current_college = $course['college_name'];
                                }
                            ?>
                                <option value="<?php echo $course['course_id']; ?>"><?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?></option>
                            <?php 
                            endforeach; 
                            if ($current_college !== '') echo '</optgroup>';
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Department</label>
                        <input type="text" name="department" id="form_department" placeholder="e.g. Computer Science">
                    </div>
                    <div class="form-group">
                        <label>Prerequisite</label>
                        <input type="text" name="prerequisite" id="form_prerequisite" placeholder="e.g. CS101, CS102">
                    </div>
                    <div class="form-group">
                        <label>Year Level</label>
                        <select name="year_level" id="form_year_level">
                            <option value="">Not specified</option>
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                            <option value="5">5th Year</option>
                            <option value="6">6th Year</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Semester</label>
                        <select name="semester" id="form_semester">
                            <option value="">Not specified</option>
                            <option value="1st">1st Semester</option>
                            <option value="2nd">2nd Semester</option>
                            <option value="summer">Summer</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="form_status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="submit" class="btn-submit" id="formSubmitBtn">Add Subject</button>
                    <button type="button" class="btn-secondary" onclick="closeModal('formModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ── Delete Warning Modal ──────────────────────── -->
    <div id="deleteModal" class="modal">
        <div class="modal-content" style="max-width:520px;">
            <span class="close" onclick="closeModal('deleteModal')">&times;</span>
            <h2 class="modal-subject-title" style="color:var(--red);"><i class="fa-solid fa-triangle-exclamation"></i> Delete Subject</h2>

            <p id="deleteModalDesc" style="margin:.75rem 0 1rem;font-size:.9rem;color:var(--dark);"></p>

            <div id="deleteModalWarning" style="display:none;margin-bottom:1rem;">
                <div style="background:#dc26261a;border-left:4px solid #dc2626;border-radius:6px;padding:.85rem 1rem;">
                    <p style="font-size:.85rem;font-weight:600;color:#dc2626;margin-bottom:.5rem;">
                        <i class="fa-solid fa-link-slash"></i>
                        The following subjects list this as a prerequisite and will be affected:
                    </p>
                    <div id="deleteModalDependentsList" style="display:flex;flex-direction:column;gap:.35rem;"></div>
                </div>
            </div>

            <form method="POST" action="../../php/admin_subjects_handler.php" id="deleteForm">
                <input type="hidden" name="action"     value="delete">
                <input type="hidden" name="subject_id" id="delete_subject_id">
                <input type="hidden" name="_filter" value="<?php echo htmlspecialchars($filter); ?>">
                <input type="hidden" name="_year"   value="<?php echo htmlspecialchars($year_filter); ?>">
                <input type="hidden" name="_course" value="<?php echo htmlspecialchars($course_filter); ?>">
                <input type="hidden" name="_search" value="<?php echo htmlspecialchars($search); ?>">
                <div class="modal-actions">
                    <button type="submit" class="btn-submit" style="background:#dc2626;" id="deleteConfirmBtn">Delete</button>
                    <button type="button" class="btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../js/admin/admin_main.js"></script>
    <script src="../../js/admin/admin_subjects.js"></script>
    <script>
    function confirmDelete(subjectId, subjectCode, subjectName) {
        document.getElementById('delete_subject_id').value = subjectId;
        document.getElementById('deleteModalDesc').textContent =
            'You are about to permanently delete "' + subjectCode + ' — ' + subjectName + '". This cannot be undone.';
        document.getElementById('deleteModalWarning').style.display = 'none';
        document.getElementById('deleteModalDependentsList').innerHTML = '';
        document.getElementById('deleteConfirmBtn').textContent = 'Checking…';
        document.getElementById('deleteConfirmBtn').disabled = true;
        document.getElementById('deleteModal').style.display = 'flex';

        const fd = new FormData();
        fd.append('action', 'check_dependents');
        fd.append('subject_id', subjectId);

        fetch('../../php/admin_subjects_handler.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                document.getElementById('deleteConfirmBtn').textContent = 'Delete';
                document.getElementById('deleteConfirmBtn').disabled = false;
                if (data.dependents && data.dependents.length > 0) {
                    const list = document.getElementById('deleteModalDependentsList');
                    data.dependents.forEach(dep => {
                        const item = document.createElement('div');
                        item.style.cssText = 'font-size:.82rem;color:#dc2626;display:flex;align-items:center;gap:.4rem;';
                        const semMap = { '1st': '1st Sem', '2nd': '2nd Sem', 'summer': 'Summer' };
                        const sem = semMap[dep.semester] || dep.semester || '';
                        item.innerHTML = '<i class="fa-solid fa-arrow-right" style="font-size:.7rem;"></i>'
                            + '<strong>' + dep.subject_code + '</strong> — ' + dep.subject_name
                            + (dep.year_level ? ' <span style="opacity:.7">(Year ' + dep.year_level + (sem ? ', ' + sem : '') + ')</span>' : '');
                        list.appendChild(item);
                    });
                    document.getElementById('deleteModalWarning').style.display = 'block';
                    document.getElementById('deleteConfirmBtn').textContent = 'Delete Anyway';
                }
            })
            .catch(() => {
                document.getElementById('deleteConfirmBtn').textContent = 'Delete';
                document.getElementById('deleteConfirmBtn').disabled = false;
            });
    }
    </script>
</body>
</html>