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
$search      = trim($_GET['search'] ?? '');
$filter      = $_GET['filter']      ?? 'all';
$dept_filter = $_GET['dept']        ?? '';
$year_filter = $_GET['year']        ?? '';

$where = "WHERE 1=1";
if ($filter === 'active')   $where .= " AND status = 'active'";
if ($filter === 'inactive') $where .= " AND status = 'inactive'";
if ($dept_filter !== '')    $where .= " AND department = '" . mysqli_real_escape_string($con, $dept_filter) . "'";
if ($year_filter !== '')    $where .= " AND year_level = '" . mysqli_real_escape_string($con, $year_filter) . "'";
if ($search !== '') {
    $s = mysqli_real_escape_string($con, $search);
    $where .= " AND (subject_code LIKE '%$s%' OR subject_name LIKE '%$s%' OR department LIKE '%$s%' OR prerequisite LIKE '%$s%')";
}

$subjects = mysqli_query($con, "SELECT * FROM subjects $where ORDER BY year_level ASC, semester ASC, subject_code ASC");

// Departments for filter dropdown
$depts_query = mysqli_query($con, "SELECT DISTINCT department FROM subjects WHERE department IS NOT NULL AND department != '' ORDER BY department");
$departments = [];
while ($d = mysqli_fetch_assoc($depts_query)) $departments[] = $d['department'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subjects Management - PLM Admin</title>
    <link rel="icon" href="../../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/admin/admin_main.css">
    <link rel="stylesheet" href="../../css/admin/admin_subjects.css">
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
                        <a href="admin_subjects.php" class="active">
                            <i class="fa-solid fa-book"></i>
                            <span class="li-name">Subjects</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_classes.php">
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
                                </select>
                                <select name="dept" class="dept-select" onchange="this.form.submit()">
                                    <option value="">All Departments</option>
                                    <?php foreach ($departments as $d): ?>
                                        <option value="<?php echo htmlspecialchars($d); ?>" <?php echo $dept_filter===$d?'selected':''; ?>>
                                            <?php echo htmlspecialchars($d); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" name="search" class="header-search-input"
                                       placeholder="Search code, name, dept..."
                                       value="<?php echo htmlspecialchars($search); ?>">
                                <button type="submit" class="btn-secondary" style="padding:0.45rem 0.75rem;">
                                    <i class="fa-solid fa-search"></i>
                                </button>
                                <?php if ($search || $dept_filter || $year_filter): ?>
                                    <a href="?filter=<?php echo $filter; ?>" class="btn-secondary" style="padding:0.45rem 0.75rem;">Clear</a>
                                <?php endif; ?>
                            </form>
                            <a href="admin_subjects_batch_import.php" class="btn-secondary btn-import">
                                <i class="fa-solid fa-file-import"></i>
                                <span class="li-name">Batch Import</span>
                            </a>
                            <button class="btn-secondary" onclick="openAdd()">
                                <i class="fa-solid fa-plus"></i>
                                <span class="li-name">Add Subject</span>
                            </button>
                        </div>
                    </div>

                    <div class="filter-tabs">
                        <a href="?filter=all&search=<?php echo urlencode($search); ?>&dept=<?php echo urlencode($dept_filter); ?>&year=<?php echo urlencode($year_filter); ?>"
                           class="filter-tab <?php echo $filter==='all'?'active':''; ?>">All</a>
                        <a href="?filter=active&search=<?php echo urlencode($search); ?>&dept=<?php echo urlencode($dept_filter); ?>&year=<?php echo urlencode($year_filter); ?>"
                           class="filter-tab <?php echo $filter==='active'?'active':''; ?>">Active</a>
                        <a href="?filter=inactive&search=<?php echo urlencode($search); ?>&dept=<?php echo urlencode($dept_filter); ?>&year=<?php echo urlencode($year_filter); ?>"
                           class="filter-tab <?php echo $filter==='inactive'?'active':''; ?>">Inactive</a>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Subject Name</th>
                                    <th>Units</th>
                                    <th>Lec / Lab Hrs</th>
                                    <th>Department</th>
                                    <th>Year</th>
                                    <th>Semester</th>
                                    <th>Prerequisite</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (mysqli_num_rows($subjects) === 0): ?>
                                <tr>
                                    <td colspan="10" style="text-align:center;color:var(--text-label);padding:2rem;">
                                        No subjects found.
                                    </td>
                                </tr>
                            <?php else: ?>
                            <?php while ($sub = mysqli_fetch_assoc($subjects)):
                                $yr_class = $sub['year_level'] ? 'yr' . $sub['year_level'] : 'incomplete';
                                $js = htmlspecialchars(json_encode($sub), ENT_QUOTES);
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($sub['subject_code']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($sub['subject_name']); ?></td>
                                    <td><span class="units-badge"><?php echo $sub['units']; ?> units</span></td>
                                    <td><?php echo $sub['lecture_hours']; ?> / <?php echo $sub['lab_hours']; ?></td>
                                    <td><?php echo htmlspecialchars($sub['department'] ?? '—'); ?></td>
                                    <td>
                                        <?php if ($sub['year_level']): ?>
                                            <span class="badge <?php echo $yr_class; ?>">Year <?php echo $sub['year_level']; ?></span>
                                        <?php else: ?>
                                            <span style="color:var(--text-label);">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo $sub['semester']
                                            ? htmlspecialchars(ucfirst($sub['semester']))
                                            : '<span style="color:var(--text-label);">—</span>'; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($sub['prerequisite'] ?: '—'); ?></td>
                                    <td><span class="badge <?php echo $sub['status']; ?>"><?php echo ucfirst($sub['status']); ?></span></td>
                                    <td>
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
                                                <button type="submit"
                                                        class="btn-icon <?php echo $sub['status']==='active'?'toggle-on':'toggle-off'; ?>"
                                                        title="<?php echo $sub['status']==='active'?'Deactivate':'Activate'; ?>">
                                                    <i class="fa-solid <?php echo $sub['status']==='active'?'fa-toggle-on':'fa-toggle-off'; ?>"></i>
                                                </button>
                                            </form>
                                            <form method="POST" action="../../php/admin_subjects_handler.php" style="display:inline;"
                                                  onsubmit="return confirm('Delete this subject? This cannot be undone.')">
                                                <input type="hidden" name="action"     value="delete">
                                                <input type="hidden" name="subject_id" value="<?php echo $sub['subject_id']; ?>">
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

    <script src="../../js/admin/admin_main.js"></script>
    <script src="../../js/admin/admin_subjects.js"></script>
</body>
</html>