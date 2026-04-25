<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
include("../../php/connection.php");
include("../../php/admin_functions.php");

$admin_data = check_admin_login($con);

$pending_applicants = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM applicants WHERE application_status = 'pending'"))['count'];

// Flash messages
$flash_errors = [
    'missing_fields'           => 'Please fill in all required fields.',
    'duplicate_email'          => 'That email is already used by another student.',
    'duplicate_student_number' => 'That student number is already taken.',
    'update_failed'            => 'Update failed. Please try again.',
];
$flash = '';
if (isset($_GET['error']) && isset($flash_errors[$_GET['error']])) {
    $flash = '<div class="error-message"><i class="fa-solid fa-circle-exclamation"></i> ' . $flash_errors[$_GET['error']] . '</div>';
}
if (isset($_GET['success']) && $_GET['success'] === 'updated') {
    $flash = '<div class="success-message"><i class="fa-solid fa-check-circle"></i> Student record updated successfully.</div>';
}
if (isset($_GET['success']) && $_GET['success'] === 'deleted') {
    $flash = '<div class="success-message"><i class="fa-solid fa-check-circle"></i> Student deleted successfully.</div>';
}

// Handle delete
if (isset($_POST['delete_student'])) {
    $del_id = (int)$_POST['student_id'];
    $stmt = mysqli_prepare($con, "DELETE FROM students WHERE student_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $del_id);
    mysqli_stmt_execute($stmt);
    header('Location: admin_students.php?success=deleted');
    exit;
}

// Stats
$total_students  = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM students"))['c'];
$enrolled_count  = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM students WHERE status = 'Enrolled'"))['c'];
$dropped_count   = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM students WHERE status = 'Dropped'"))['c'];
$not_enrolled    = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM students WHERE status = 'Not Enrolled'"))['c'];
$irregular_count = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM students WHERE registration_status = 'Irregular'"))['c'];

// Search & filter
$search = trim($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? 'all';

$where = "WHERE 1=1";
if ($filter === 'enrolled')     $where .= " AND s.status = 'Enrolled'";
if ($filter === 'not_enrolled') $where .= " AND s.status = 'Not Enrolled'";
if ($filter === 'dropped')      $where .= " AND s.status = 'Dropped'";
if ($filter === 'irregular')    $where .= " AND s.registration_status = 'Irregular'";
if ($search !== '') {
    $s = mysqli_real_escape_string($con, $search);
    $where .= " AND (s.first_name LIKE '%$s%' OR s.last_name LIKE '%$s%' OR s.student_number LIKE '%$s%' OR s.email LIKE '%$s%' OR s.course LIKE '%$s%')";
}

$students_query = mysqli_query($con, "
    SELECT s.*, b.block_name
    FROM students s
    LEFT JOIN blocks b ON s.block_id = b.block_id
    $where
    ORDER BY s.student_id DESC
");

// All blocks for dropdowns
$blocks_list = mysqli_query($con, "SELECT block_id, block_name, course, year_level FROM blocks ORDER BY block_name");
$blocks_arr  = [];
while ($b = mysqli_fetch_assoc($blocks_list)) $blocks_arr[] = $b;

// Get courses from courses table
$courses_query = mysqli_query($con, "SELECT course_code, course_name, college_name FROM courses WHERE status = 'active' ORDER BY college_name, course_name");
$courses = [];
$course_name_to_code = [];
while ($row = mysqli_fetch_assoc($courses_query)) {
    $courses[] = $row;
    $course_name_to_code[$row['course_name']] = $row['course_code'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students Management - PLM Admin</title>
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
    <link rel="stylesheet" href="../../css/admin/admin_students.css">
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
                    <h1>Students Management</h1>
                    <p>View, edit, and manage all student records</p>
                </div>

                <?php echo $flash; ?>

                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card blue">
                        <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                        <div class="stat-content">
                            <h3>Total Students</h3>
                            <p class="stat-number"><?php echo $total_students; ?></p>
                        </div>
                    </div>
                    <div class="stat-card green">
                        <div class="stat-icon"><i class="fa-solid fa-user-check"></i></div>
                        <div class="stat-content">
                            <h3>Enrolled</h3>
                            <p class="stat-number"><?php echo $enrolled_count; ?></p>
                        </div>
                    </div>
                    <div class="stat-card gold">
                        <div class="stat-icon"><i class="fa-solid fa-user-clock"></i></div>
                        <div class="stat-content">
                            <h3>Not Enrolled</h3>
                            <p class="stat-number"><?php echo $not_enrolled; ?></p>
                        </div>
                    </div>
                    <div class="stat-card red">
                        <div class="stat-icon"><i class="fa-solid fa-user-xmark"></i></div>
                        <div class="stat-content">
                            <h3>Dropped</h3>
                            <p class="stat-number"><?php echo $dropped_count; ?></p>
                        </div>
                    </div>
                    <div class="stat-card purple">
                        <div class="stat-icon"><i class="fa-solid fa-shuffle"></i></div>
                        <div class="stat-content">
                            <h3>Irregular</h3>
                            <p class="stat-number"><?php echo $irregular_count; ?></p>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2>All Students</h2>
                        <form method="GET" class="search-form">
                            <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                            <input type="text" name="search" placeholder="Search name, ID, email, course..." value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit"><i class="fa-solid fa-search"></i></button>
                            <?php if ($search): ?>
                                <a href="?filter=<?php echo $filter; ?>" class="btn-secondary clear-btn">Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <div class="filter-tabs">
                        <a href="?filter=all&search=<?php echo urlencode($search); ?>"         class="filter-tab <?php echo $filter==='all'?'active':''; ?>">All</a>
                        <a href="?filter=enrolled&search=<?php echo urlencode($search); ?>"    class="filter-tab <?php echo $filter==='enrolled'?'active':''; ?>">Enrolled</a>
                        <a href="?filter=not_enrolled&search=<?php echo urlencode($search); ?>" class="filter-tab <?php echo $filter==='not_enrolled'?'active':''; ?>">Not Enrolled</a>
                        <a href="?filter=dropped&search=<?php echo urlencode($search); ?>"     class="filter-tab <?php echo $filter==='dropped'?'active':''; ?>">Dropped</a>
                        <a href="?filter=irregular&search=<?php echo urlencode($search); ?>"   class="filter-tab <?php echo $filter==='irregular'?'active':''; ?>">Irregular</a>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Student No.</th>
                                    <th>Photo</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Course</th>
                                    <th>Year</th>
                                    <th>Block</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="studentsTable">
                            <?php while ($student = mysqli_fetch_assoc($students_query)):
                                // Ensure registration_status has a default value
                                if (empty($student['registration_status'])) {
                                    $student['registration_status'] = 'Unknown';
                                }
                                
                                $initials     = strtoupper(substr($student['first_name'] ?? '', 0, 1) . substr($student['last_name'] ?? '', 0, 1));
                                $photo        = $student['profile_photo'] ? '../../' . $student['profile_photo'] : null;
                                $fullname     = htmlspecialchars(trim(
                                    ($student['first_name'] ?? '') . ' ' .
                                    ($student['middle_name'] ? $student['middle_name'] . ' ' : '') .
                                    ($student['last_name'] ?? '') .
                                    ($student['suffix_name'] ? ' ' . $student['suffix_name'] : '')
                                ));
                                $status_class = strtolower(str_replace(' ', '-', $student['status'] ?? ''));
                                $js_data      = htmlspecialchars(json_encode($student), ENT_QUOTES);
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($student['student_number']); ?></strong></td>
                                <td>
                                    <?php if ($photo): ?>
                                        <div class="photo-container">
                                            <img src="<?php echo htmlspecialchars($photo); ?>"
                                                class="student-thumb"
                                                onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                        </div>
                                    <?php endif; ?>
                                    <div class="photo-container">
                                        <div class="student-initials" style="display:<?php echo $photo ? 'none' : 'flex'; ?>;">
                                            <?php echo $initials; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo $fullname; ?></td>
                                <td><?php echo htmlspecialchars($student['email'] ?? ''); ?></td>
                                <td><?php 
                                    $c = $student['course'] ?? 'N/A';
                                    // If stored as full name, show just the code; if already a code, show as-is
                                    echo htmlspecialchars(isset($course_name_to_code[$c]) ? $course_name_to_code[$c] : $c);
                                ?></td>
                                <td><?php echo htmlspecialchars($student['year_level'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if ($student['block_name']): ?>
                                        <span class="badge blue"><?php echo htmlspecialchars($student['block_name']); ?></span>
                                    <?php else: ?>
                                        <span class="badge no-block">No Block</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $reg_status = $student['registration_status'] ?? 'Unknown';
                                    if (empty($reg_status)) $reg_status = 'Unknown';
                                    if ($reg_status === 'Irregular') {
                                        $badge_class = 'pending';
                                    } elseif ($reg_status === 'Unknown') {
                                        $badge_class = 'no-block';
                                    } else {
                                        $badge_class = 'approved';
                                    }
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo htmlspecialchars($reg_status); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $status_class; ?>">
                                        <?php echo htmlspecialchars($student['status'] ?? 'N/A'); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-icon" title="View Details" onclick="openView('<?php echo $js_data; ?>')">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <button class="btn-icon" title="Edit Student" onclick="openEdit('<?php echo $js_data; ?>')">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <a href="admin_manual_enroll.php?student_id=<?php echo $student['student_id']; ?>" class="btn-icon" title="Manual Enrollment">
                                            <i class="fa-solid fa-user-pen"></i>
                                        </a>
                                        <button class="btn-icon danger" title="Delete Student" onclick="deleteStudent(<?php echo $student['student_id']; ?>, '<?php echo htmlspecialchars(addslashes($fullname)); ?>')">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
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

    <!-- ── View Modal ───────────────────────────────── -->
    <div id="viewModal" class="modal">
        <div class="modal-content wide">
            <span class="close" onclick="closeModal('viewModal')">&times;</span>

            <div class="modal-header">
                <div class="student-avatar-lg" id="view_avatar"></div>
                <div class="modal-header-info">
                    <h3 id="view_fullname"></h3>
                    <p id="view_student_number"></p>
                    <p id="view_status_badge"></p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="view-tabs">
                <button class="view-tab active" onclick="switchTab('tab-info', this)"><i class="fa-solid fa-id-card"></i> Personal Info</button>
                <button class="view-tab" onclick="switchTab('tab-address', this)"><i class="fa-solid fa-location-dot"></i> Address</button>
                <button class="view-tab" onclick="switchTab('tab-docs', this)"><i class="fa-solid fa-file-arrow-up"></i> Documents</button>
            </div>

            <!-- Tab: Personal Info -->
            <div id="tab-info" class="tab-panel active">
                <div class="view-grid">
                    <div class="section-title">Personal Information</div>
                    <div class="view-item"><label>First Name</label><span id="vw_first_name"></span></div>
                    <div class="view-item"><label>Last Name</label><span id="vw_last_name"></span></div>
                    <div class="view-item"><label>Middle Name</label><span id="vw_middle_name"></span></div>
                    <div class="view-item"><label>Suffix</label><span id="vw_suffix_name"></span></div>
                    <div class="view-item"><label>Married Name</label><span id="vw_married_name"></span></div>
                    <div class="view-item"><label>Gender</label><span id="vw_gender"></span></div>
                    <div class="view-item"><label>Birthdate</label><span id="vw_birthdate"></span></div>
                    <div class="view-item"><label>Place of Birth</label><span id="vw_place_of_birth"></span></div>
                    <div class="view-item"><label>Civil Status</label><span id="vw_civil_status"></span></div>
                    <div class="view-item"><label>Religion</label><span id="vw_religion"></span></div>
                    <div class="view-item"><label>Nationality</label><span id="vw_nationality"></span></div>
                    <div class="view-item"><label>Disability</label><span id="vw_disability"></span></div>

                    <div class="section-title">Contact Information</div>
                    <div class="view-item"><label>Email</label><span id="vw_email"></span></div>
                    <div class="view-item"><label>Contact Number</label><span id="vw_contact_number"></span></div>

                    <div class="section-title">Academic Information</div>
                    <div class="view-item"><label>Student No.</label><span id="vw_student_number"></span></div>
                    <div class="view-item"><label>LRN</label><span id="vw_lrn"></span></div>
                    <div class="view-item"><label>College</label><span id="vw_college"></span></div>
                    <div class="view-item"><label>Course</label><span id="vw_course"></span></div>
                    <div class="view-item"><label>Year Level</label><span id="vw_year_level"></span></div>
                    <div class="view-item"><label>Block</label><span id="vw_block"></span></div>
                    <div class="view-item"><label>Registration Type</label><span id="vw_registration_status"></span></div>
                    <div class="view-item"><label>Enrollment Status</label><span id="vw_status"></span></div>
                    <div class="view-item"><label>Account Status</label><span id="vw_account_status"></span></div>
                    <div class="view-item"><label>Date Created</label><span id="vw_created_at"></span></div>
                </div>
            </div>

            <!-- Tab: Address -->
            <div id="tab-address" class="tab-panel">
                <div class="view-grid">
                    <div class="section-title">Permanent Address</div>
                    <div class="view-item"><label>Region</label><span id="vw_perm_region"></span></div>
                    <div class="view-item"><label>Province</label><span id="vw_perm_province"></span></div>
                    <div class="view-item"><label>Municipality</label><span id="vw_perm_municipality"></span></div>
                    <div class="view-item"><label>Barangay</label><span id="vw_perm_barangay"></span></div>
                    <div class="view-item"><label>Zip Code</label><span id="vw_perm_zipcode"></span></div>
                    <div class="view-item view-item-full"><label>Complete Address</label><span id="vw_perm_address"></span></div>

                    <div class="section-title">Mailing Address</div>
                    <div class="view-item"><label>Region</label><span id="vw_mail_region"></span></div>
                    <div class="view-item"><label>Province</label><span id="vw_mail_province"></span></div>
                    <div class="view-item"><label>Municipality</label><span id="vw_mail_municipality"></span></div>
                    <div class="view-item"><label>Barangay</label><span id="vw_mail_barangay"></span></div>
                    <div class="view-item"><label>Zip Code</label><span id="vw_mail_zipcode"></span></div>
                    <div class="view-item view-item-full"><label>Complete Address</label><span id="vw_mail_address"></span></div>
                </div>
            </div>

            <!-- Tab: Documents -->
            <div id="tab-docs" class="tab-panel">
                <div class="doc-list" id="vw_doc_list"></div>
            </div>
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

    <!-- ── Edit Modal ───────────────────────────────── -->
    <div id="editModal" class="modal">
        <div class="modal-content wide">
            <span class="close" onclick="closeModal('editModal')">&times;</span>
            <h2>Edit Student Record</h2>

            <form method="POST" action="../../php/admin_update_student.php">
                <input type="hidden" name="student_id" id="edit_student_id">

                <div class="form-grid-2">
                    <div class="form-section-title">Academic Info</div>
                    <div class="form-group">
                        <label>Student Number <span style="color:var(--red)">*</span></label>
                        <input type="text" name="student_number" id="edit_student_number" required>
                    </div>
                    <div class="form-group">
                        <label>College</label>
                        <input type="text" name="college" id="edit_college" placeholder="e.g. CE, CBA">
                    </div>
                    <div class="form-group">
                        <label>Course</label>
                        <select name="course" id="edit_course">
                            <option value="">Select Course</option>
                            <?php 
                            $current_college = '';
                            foreach ($courses as $course): 
                                if ($current_college !== $course['college_name']) {
                                    if ($current_college !== '') echo '</optgroup>';
                                    echo '<optgroup label="' . htmlspecialchars($course['college_name']) . '">';
                                    $current_college = $course['college_name'];
                                }
                            ?>
                                <option value="<?php echo htmlspecialchars($course['course_code']); ?>"><?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?></option>
                            <?php 
                            endforeach; 
                            if ($current_college !== '') echo '</optgroup>';
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Year Level</label>
                        <select name="year_level" id="edit_year_level">
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                            <option value="5">5th Year</option>
                            <option value="6">6th Year</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Block</label>
                        <select name="block_id" id="edit_block_id">
                            <option value="">No Block (Irregular)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Registration Type</label>
                        <select name="registration_status" id="edit_registration_status" required>
                            <option value="Unknown">Unknown</option>
                            <option value="Regular">Regular</option>
                            <option value="Irregular">Irregular</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Enrollment Status</label>
                        <select name="status" id="edit_status">
                            <option value="Not Enrolled">Not Enrolled</option>
                            <option value="Enrolled">Enrolled</option>
                            <option value="Graduated">Graduated</option>
                            <option value="Leave of Absence">Leave of Absence</option>
                            <option value="Dropped">Dropped</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Account Status</label>
                        <select name="account_status" id="edit_account_status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="form-section-title">Personal Info</div>
                    <div class="form-group">
                        <label>First Name <span style="color:var(--red)">*</span></label>
                        <input type="text" name="first_name" id="edit_first_name" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name <span style="color:var(--red)">*</span></label>
                        <input type="text" name="last_name" id="edit_last_name" required>
                    </div>
                    <div class="form-group">
                        <label>Middle Name</label>
                        <input type="text" name="middle_name" id="edit_middle_name">
                    </div>
                    <div class="form-group">
                        <label>Suffix</label>
                        <input type="text" name="suffix_name" id="edit_suffix_name" placeholder="e.g. Jr., III">
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender" id="edit_gender">
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Birthdate</label>
                        <input type="date" name="birthdate" id="edit_birthdate">
                    </div>

                    <div class="form-section-title">Contact Info</div>
                    <div class="form-group">
                        <label>Email <span style="color:var(--red)">*</span></label>
                        <input type="email" name="email" id="edit_email" required>
                    </div>
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text" name="contact_number" id="edit_contact_number">
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="submit" class="btn-primary">Save Changes</button>
                    <button type="button" class="btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    const ALL_BLOCKS = <?php echo json_encode($blocks_arr); ?>;
    const COURSE_NAME_TO_CODE = <?php echo json_encode($course_name_to_code); ?>;
    </script>
    <script src="../../js/admin/admin_main.js"></script>
    <script src="../../js/admin/admin_students.js"></script>
    <script>
    // Force page reload on back navigation to show updated data
    window.addEventListener('pageshow', function(event) {
        if (event.persisted || (window.performance && window.performance.navigation.type === 2)) {
            window.location.reload();
        }
    });
    </script>
</body>
</html>