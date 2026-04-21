<?php
session_start();
include("../../php/connection.php");
include("../../php/admin_functions.php");

$admin_data = check_admin_login($con);

// Handle delete
if (isset($_POST['delete_applicant'])) {
    $applicant_id = (int)$_POST['applicant_id'];
    $ap = mysqli_fetch_assoc(mysqli_query($con, "SELECT first_name, last_name FROM applicants WHERE applicant_id = $applicant_id"));
    $stmt = mysqli_prepare($con, "DELETE FROM applicants WHERE applicant_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $applicant_id);
    mysqli_stmt_execute($stmt);
    if ($ap) {
        log_activity($con, 'Deleted applicant', 'applicant', $ap['first_name'] . ' ' . $ap['last_name'] . ' (ID ' . $applicant_id . ')');
    }
    $success = "Applicant deleted successfully.";
}

// Handle status update
if (isset($_POST['update_status'])) {
    $applicant_id = (int)$_POST['applicant_id'];
    $new_status   = $_POST['status'];
    // Fetch applicant name for the log
    $ap = mysqli_fetch_assoc(mysqli_query($con, "SELECT first_name, last_name FROM applicants WHERE applicant_id = $applicant_id"));
    $stmt = mysqli_prepare($con, "UPDATE applicants SET application_status = ? WHERE applicant_id = ?");
    mysqli_stmt_bind_param($stmt, "si", $new_status, $applicant_id);
    mysqli_stmt_execute($stmt);
    if ($ap) {
        log_activity($con, 'Updated applicant status to ' . $new_status, 'applicant',
            $ap['first_name'] . ' ' . $ap['last_name'] . ' (ID ' . $applicant_id . ')');
    }
    $success = "Application status updated successfully!";
}

// Flash messages from convert_to_student.php
$error_messages = [
    'missing_fields'           => 'Please fill in all required fields.',
    'not_found'                => 'Applicant not found or not yet approved.',
    'duplicate_student_number' => 'Student number already exists.',
    'already_student'          => 'This applicant is already a student.',
    'insert_failed'            => 'Failed to create student record. Please try again.',
    'incomplete_profile'       => 'Cannot convert — applicant has not completed their profile (missing first name or last name).',
    'documents_not_submitted'  => 'Cannot convert — applicant has not submitted required documents.',
    'incomplete_information'   => 'Cannot convert — applicant has incomplete information (missing LRN, birthdate, contact number, program choice, or gender).',
];
if (isset($_GET['error']) && isset($error_messages[$_GET['error']])) {
    $error = $error_messages[$_GET['error']];
}
if (isset($_GET['success']) && $_GET['success'] === 'converted') {
    $success = 'Applicant successfully converted to student!';
}
if (isset($_GET['exam_success'])) {
    $success = 'Exam schedule assigned to ' . (int)$_GET['exam_success'] . ' applicant(s).';
}
if (isset($_GET['exam_cleared'])) {
    $success = 'Exam schedule cleared for selected applicants.';
}
if (isset($_GET['exam_error'])) {
    $error = 'Please fill in all required exam schedule fields and select at least one applicant.';
}

// Ensure exam columns exist
$cols    = [];
$col_res = mysqli_query($con, "SHOW COLUMNS FROM applicants");
while ($c = mysqli_fetch_assoc($col_res)) $cols[] = $c['Field'];
if (!in_array('exam_schedule_id', $cols))
    mysqli_query($con, "ALTER TABLE applicants ADD COLUMN exam_schedule_id INT DEFAULT NULL");
if (!in_array('exam_notified', $cols))
    mysqli_query($con, "ALTER TABLE applicants ADD COLUMN exam_notified TINYINT(1) DEFAULT 0");
mysqli_query($con, "CREATE TABLE IF NOT EXISTS exam_schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    exam_date   DATE NOT NULL,
    exam_time   VARCHAR(50) NOT NULL,
    location    VARCHAR(255) NOT NULL,
    notes       TEXT,
    created_by  INT DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Fetch courses for convert modal
$courses_by_college = []; // college_name => [ ['course_name'=>..., 'course_code'=>...], ... ]
$cr = mysqli_query($con, "SELECT course_name, course_code, college_name FROM courses WHERE status='active' ORDER BY college_name, course_name");
while ($row = mysqli_fetch_assoc($cr)) {
    $courses_by_college[$row['college_name']][] = ['course_name' => $row['course_name'], 'course_code' => $row['course_code']];
}

// Stats
$pending_applicants = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM applicants WHERE application_status = 'pending'"))['count'];

// Filter & search
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$query = "SELECT a.*, es.exam_date, es.exam_time, es.location as exam_location
          FROM applicants a
          LEFT JOIN exam_schedules es ON a.exam_schedule_id = es.schedule_id
          WHERE 1=1";
if ($filter === 'all') {
    $query .= " AND a.application_status != 'enrolled'";
} else {
    $query .= " AND a.application_status = '$filter'";
}
if (!empty($search)) {
    $s = mysqli_real_escape_string($con, $search);
    $query .= " AND (a.first_name LIKE '%$s%' OR a.last_name LIKE '%$s%' OR a.email LIKE '%$s%' OR a.lrn LIKE '%$s%')";
}
$query .= " ORDER BY a.created_at DESC";
$applicants = mysqli_query($con, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Applicants - PLM Admin</title>
    <link rel="icon" href="../../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/admin/admin_main.css">
    <link rel="stylesheet" href="../../css/admin/admin_applicants.css">
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
                        <a href="admin_applicants.php" class="active">
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
                        <a href="admin_drop_requests.php">
                            <i class="fa-solid fa-right-from-bracket"></i>
                            <span class="li-name">Drop Requests</span>
                            <?php if (!empty($GLOBALS['pending_drops'])): ?><span class="sidebar-badge li-name"><?php echo $GLOBALS['pending_drops']; ?></span><?php endif; ?>
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
                        <?php if (($admin_data['role'] ?? 'admin') === 'superadmin'): ?>
                        <a href="admin_accounts.php">
                            <i class="fa-solid fa-user-shield"></i>
                            <span class="li-name">Admin Accounts</span>
                        </a>
                        <?php endif; ?>
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
                    <h1>Manage Applicants</h1>
                    <p>Review and manage student applications</p>
                </div>

                <?php if (isset($success)): ?>
                    <div class="success-message"><i class="fa-solid fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="error-message"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h2>Applicants List</h2>
                        <div class="header-actions">
                            <form method="GET" class="search-form">
                                <input type="text" name="search" placeholder="Search applicants..." value="<?php echo htmlspecialchars($search); ?>">
                                <button type="submit"><i class="fa-solid fa-search"></i></button>
                            </form>
                            <button class="btn-primary" onclick="openExamModal()">
                                <i class="fa-solid fa-calendar-plus"></i>
                                <span class="li-name">Schedule Exam</span>
                            </button>
                        </div>
                    </div>

                    <!-- Batch Action Bar -->
                    <div class="batch-bar" id="batchBar">
                        <span class="batch-count" id="batchCount">0 selected</span>
                        <button class="btn-primary" onclick="openExamModal()" style="padding:0.4rem 0.9rem;font-size:0.82rem;">
                            <i class="fa-solid fa-calendar-plus"></i> Assign Exam Schedule
                        </button>
                        <button class="btn-secondary" onclick="clearExam()" style="padding:0.4rem 0.9rem;font-size:0.82rem;">
                            <i class="fa-solid fa-calendar-xmark"></i> Clear Exam
                        </button>
                        <button class="btn-secondary" onclick="deselectAll()" style="padding:0.4rem 0.9rem;font-size:0.82rem;">
                            Deselect All
                        </button>
                    </div>

                    <div class="filter-tabs">
                        <a href="?filter=all"        class="filter-tab <?php echo $filter==='all'        ? 'active':''; ?>">All</a>
                        <a href="?filter=incomplete" class="filter-tab <?php echo $filter==='incomplete' ? 'active':''; ?>">Incomplete</a>
                        <a href="?filter=pending"    class="filter-tab <?php echo $filter==='pending'    ? 'active':''; ?>">Pending</a>
                        <a href="?filter=approved"   class="filter-tab <?php echo $filter==='approved'   ? 'active':''; ?>">Approved</a>
                        <a href="?filter=rejected"   class="filter-tab <?php echo $filter==='rejected'   ? 'active':''; ?>">Rejected</a>
                        <a href="?filter=enrolled"   class="filter-tab <?php echo $filter==='enrolled'   ? 'active':''; ?>">Converted</a>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAll" onchange="toggleAll(this)" title="Select all"></th>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>LRN</th>
                                    <th>First Choice</th>
                                    <th>Status</th>
                                    <th>Exam Schedule</th>
                                    <th>Applied Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($applicant = mysqli_fetch_assoc($applicants)): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="row-check" value="<?php echo $applicant['applicant_id']; ?>" onchange="updateBatch()">
                                    </td>
                                    <td><?php echo $applicant['applicant_id']; ?></td>
                                    <td><?php echo htmlspecialchars(($applicant['first_name'] ?? '') . ' ' . ($applicant['last_name'] ?? 'N/A')); ?></td>
                                    <td><?php echo htmlspecialchars($applicant['email']); ?></td>
                                    <td><?php echo htmlspecialchars($applicant['lrn'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($applicant['first_choice'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge <?php echo strtolower($applicant['application_status']); ?>">
                                            <?php echo htmlspecialchars(ucfirst($applicant['application_status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($applicant['exam_schedule_id']): ?>
                                            <div class="exam-cell">
                                                <div class="exam-cell-date"><?php echo date('M j, Y', strtotime($applicant['exam_date'])); ?></div>
                                                <div class="exam-cell-time"><?php echo htmlspecialchars($applicant['exam_time']); ?></div>
                                                <div class="exam-cell-loc"><?php echo htmlspecialchars($applicant['exam_location']); ?></div>
                                            </div>
                                        <?php else: ?>
                                            <span class="exam-cell-empty">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($applicant['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button onclick="viewApplicant(<?php echo $applicant['applicant_id']; ?>)" class="btn-icon" title="View Details">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                            <button onclick="updateStatus(<?php echo $applicant['applicant_id']; ?>)" class="btn-icon" title="Update Status">
                                                <i class="fa-solid fa-edit"></i>
                                            </button>
                                            <?php if ($applicant['application_status'] === 'approved'): ?>
                                            <button onclick="openConvertModal(<?php echo $applicant['applicant_id']; ?>, '<?php echo htmlspecialchars(addslashes($applicant['first_name'] . ' ' . $applicant['last_name'])); ?>', '<?php echo htmlspecialchars(addslashes($applicant['first_choice'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($applicant['second_choice'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($applicant['third_choice'] ?? '')); ?>')" class="btn-icon convert" title="Convert to Student">
                                                <i class="fa-solid fa-user-graduate"></i>
                                            </button>
                                            <?php endif; ?>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this applicant? This cannot be undone.')">
                                                <input type="hidden" name="applicant_id" value="<?php echo $applicant['applicant_id']; ?>">
                                                <button type="submit" name="delete_applicant" class="btn-icon danger" title="Delete">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div><!-- /.main-content -->
        </main>
    </div><!-- /.main-flex -->

    <!-- ── Exam Schedule Modal ──────────────────────── -->
    <div id="examModal" class="modal">
        <div class="modal-content exam-modal">
            <span class="close" onclick="document.getElementById('examModal').style.display='none'">&times;</span>
            <h2>Schedule Exam</h2>
            <p class="modal-desc" id="examModalDesc"></p>
            <form method="POST" action="../../php/admin_exam_schedule_handler.php" id="examForm">
                <input type="hidden" name="action" value="assign_exam">
                <div id="examApplicantInputs"></div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Exam Date <span style="color:var(--red)">*</span></label>
                        <input type="date" name="exam_date" id="exam_date" required>
                    </div>
                    <div class="form-group">
                        <label>Exam Time <span style="color:var(--red)">*</span></label>
                        <input type="text" name="exam_time" id="exam_time" placeholder="e.g. 8:00 AM" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Location / Venue <span style="color:var(--red)">*</span></label>
                    <input type="text" name="location" id="exam_location" placeholder="e.g. PLM Main Building, Room 201" required>
                </div>
                <div class="form-group">
                    <label>Notes <small style="font-weight:400;text-transform:none;">(optional)</small></label>
                    <textarea name="notes" rows="2" placeholder="Additional instructions for applicants…"></textarea>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="btn-primary">Assign Schedule</button>
                    <button type="button" class="btn-secondary" onclick="document.getElementById('examModal').style.display='none'">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Clear Exam (hidden form) -->
    <form method="POST" action="../../php/admin_exam_schedule_handler.php" id="clearExamForm">
        <input type="hidden" name="action" value="clear_exam">
        <div id="clearExamInputs"></div>
    </form>

    <!-- ── Convert to Student Modal ─────────────────── -->
    <div id="convertModal" class="modal">
        <div class="modal-content convert-modal">
            <span class="close" onclick="document.getElementById('convertModal').style.display='none'">&times;</span>
            <h2>Convert to Student</h2>
            <p style="font-size:0.9rem;color:var(--text);margin-bottom:1.5rem;">
                Applicant: <strong class="convert-name" id="convertName"></strong>
            </p>
            <form id="convertForm">
                <input type="hidden" name="applicant_id" id="convert_applicant_id">
                <div class="convert-choices" id="convertChoices"></div>
                <div class="form-group">
                    <label>Student Number <span style="color:var(--red)">*</span></label>
                    <input type="text" name="student_number" placeholder="e.g., 202412345"
                           maxlength="9" pattern="\d{9}"
                           inputmode="numeric"
                           oninput="this.value=this.value.replace(/\D/g,'').slice(0,9)"
                           title="Student number must be exactly 9 digits" required>
                </div>
                <div class="form-group">
                    <label>Course <span style="color:var(--red)">*</span></label>
                    <select name="course" id="convert_course" class="modal-filter-select" style="width:100%;" required>
                        <option value="">— Select a course —</option>
                        <?php foreach ($courses_by_college as $college => $programs): ?>
                            <optgroup label="<?php echo htmlspecialchars($college); ?>">
                                <?php foreach ($programs as $p): ?>
                                    <option value="<?php echo htmlspecialchars($p['course_name']); ?>"
                                            data-college="<?php echo htmlspecialchars($college); ?>">
                                        <?php echo htmlspecialchars($p['course_code'] . ' — ' . $p['course_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>College <span style="color:var(--red)">*</span></label>
                    <input type="text" name="college" id="convert_college" placeholder="Auto-filled from course" readonly required>
                </div>
                <div class="form-group">
                    <label>Year Level <span style="color:var(--red)">*</span></label>
                    <select name="year_level" required>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                    </select>
                </div>
                <button type="submit" class="btn-submit-green">Convert to Student</button>
            </form>
        </div>
    </div>

    <!-- ── View Applicant Details Modal ──────────────── -->
    <div id="viewModal" class="modal">
        <div class="modal-content view-modal">
            <span class="close" onclick="document.getElementById('viewModal').style.display='none'">&times;</span>
            <h2>Applicant Details</h2>
            <div id="viewContent" style="max-height:65vh; overflow-y:auto;padding:1rem 0;"></div>
        </div>
    </div>

    <!-- ── Document Viewer Modal ──────────────────────── -->
    <div id="docModal" class="modal">
        <div class="modal-content doc-viewer-modal">
            <div class="doc-viewer-header">
                <span id="docModalName"></span>
                <button class="close" onclick="closeDocModal()">&times;</button>
            </div>
            <div class="doc-viewer-body" id="docModalBody"></div>
        </div>
    </div>

    <!-- ── Status Update Modal ───────────────────────── -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('statusModal').style.display='none'">&times;</span>
            <h2>Update Application Status</h2>
            <form method="POST">
                <input type="hidden" name="applicant_id" id="applicant_id">
                <div class="form-group">
                    <label>New Status</label>
                    <select name="status" required>
                        <option value="incomplete">Incomplete</option>
                        <option value="pending">Pending Review</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="submit" name="update_status" class="btn-primary">Update Status</button>
                    <button type="button" class="btn-secondary" onclick="document.getElementById('statusModal').style.display='none'">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../js/admin/admin_main.js"></script>
    <script src="../../js/admin/admin_applicants.js"></script>
</body>
</html>