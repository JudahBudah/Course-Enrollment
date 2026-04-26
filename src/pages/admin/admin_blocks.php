<?php
session_start();
include("../../php/connection.php");
include("../../php/admin_functions.php");

$admin_data = check_admin_login($con);

// Sync current_students to actual count on every page load
mysqli_query($con, "UPDATE blocks b SET b.current_students = (
    SELECT COUNT(*) FROM students s WHERE s.block_id = b.block_id
)");

$blocks_query       = mysqli_query($con, "SELECT * FROM blocks ORDER BY course, year_level, block_name");
$pending_applicants = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM applicants WHERE application_status = 'pending'"))['count'];

$sys_semester    = get_setting($con, 'current_semester', '1st');
$sys_school_year = get_setting($con, 'current_school_year', date('Y') . '-' . (date('Y') + 1));

// Auto-sync all blocks to current system semester and school year
mysqli_query($con, "UPDATE blocks SET semester = '" . mysqli_real_escape_string($con, $sys_semester) . "', school_year = '" . mysqli_real_escape_string($con, $sys_school_year) . "'");

// Get courses from courses table
$courses_query = mysqli_query($con, "SELECT course_code, course_name, college_name FROM courses WHERE status = 'active' ORDER BY college_name, course_name");
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
    <title>Blocks Management - PLM Admin</title>
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
    <link rel="stylesheet" href="../../css/admin/admin_blocks.css">
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
                    <h1>Blocks Management</h1>
                    <p>Create and manage student blocks with assigned subjects</p>
                </div>

                <?php if (isset($_GET['success']) && $_GET['success'] === 'updated'): ?>
                    <div class="success-message"><i class="fa-solid fa-check-circle"></i> Block updated successfully!</div>
                <?php elseif (isset($_GET['success']) && $_GET['success'] === 'deleted'): ?>
                    <div class="success-message"><i class="fa-solid fa-check-circle"></i> Block deleted successfully!</div>
                <?php endif; ?>
                <?php if (isset($_GET['error'])): ?>
                    <?php
                    $errors = [
                        'missing_fields' => 'Please fill in all required fields.',
                        'duplicate_block' => 'A block with the same name, course, year, semester and school year already exists.',
                        'update_failed' => 'Failed to update block. Please try again.',
                        'delete_failed' => 'Failed to delete block. Please try again.',
                        'has_students' => 'Cannot delete block with enrolled students.',
                    ];
                    $error_msg = $errors[$_GET['error']] ?? 'An error occurred.';
                    ?>
                    <div class="error-message"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error_msg); ?></div>
                <?php endif; ?>

                <!-- Blocks Table -->
                <div class="card">
                    <div class="card-header">
                        <h2>All Blocks</h2>
                        <button class="btn-secondary" onclick="document.getElementById('createBlockModal').style.display='block'">
                            <i class="fa-solid fa-plus"></i>
                            <span class="li-name">Create New Block</span>
                        </button>
                    </div>

                    <div class="blocks-table-wrapper">
                        <div class="blocks-table">

                            <div class="blocks-table-header">
                                <div class="blocks-col-left">Block Name</div>
                                <div class="blocks-col-left">Course</div>
                                <div>Year Level</div>
                                <div>Semester</div>
                                <div>School Year</div>
                                <div>Students</div>
                                <div>Status</div>
                                <div>Actions</div>
                            </div>

                            <div class="blocks-table-body">
                            <?php while ($block = mysqli_fetch_assoc($blocks_query)): ?>
                            <div class="blocks-row">
                                <div class="blocks-col-left"><strong><?php echo htmlspecialchars($block['block_name']); ?></strong></div>
                                <div class="blocks-col-left"><?php echo htmlspecialchars($block['course']); ?></div>
                                <div><?php echo htmlspecialchars($block['year_level']); ?></div>
                                <div><?php echo htmlspecialchars($block['semester']); ?></div>
                                <div><?php echo htmlspecialchars($block['school_year']); ?></div>
                                <div>
                                    <span class="student-count <?php echo ($block['current_students'] >= $block['max_students']) ? 'full' : ''; ?>">
                                        <?php echo $block['current_students']; ?> / <?php echo $block['max_students']; ?>
                                    </span>
                                </div>
                                <div>
                                    <span class="badge <?php echo strtolower($block['status']); ?>">
                                        <?php echo htmlspecialchars(ucfirst($block['status'])); ?>
                                    </span>
                                </div>
                                <div>
                                    <div class="action-buttons">
                                        <a href="admin_block_subjects.php?block_id=<?php echo $block['block_id']; ?>" class="btn-icon" title="Manage Subjects">
                                            <i class="fa-solid fa-book"></i>
                                        </a>
                                        <a href="admin_block_students.php?block_id=<?php echo $block['block_id']; ?>" class="btn-icon" title="View Students">
                                            <i class="fa-solid fa-users"></i>
                                        </a>
                                        <button class="btn-icon" title="Edit" onclick="editBlock(<?php echo $block['block_id']; ?>)">
                                            <i class="fa-solid fa-edit"></i>
                                        </button>
                                        <button class="btn-icon danger" title="Delete"
                                                onclick="deleteBlock(<?php echo $block['block_id']; ?>, '<?php echo htmlspecialchars($block['block_name'], ENT_QUOTES); ?>')">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                            </div>

                        </div>
                    </div>
                </div>

            </div><!-- /.main-content -->
        </main>
    </div><!-- /.main-flex -->

    <!-- ── Create Block Modal ─────────────────────────── -->
    <div id="createBlockModal" class="modal">
        <div class="modal-content block-modal">
            <span class="close" onclick="document.getElementById('createBlockModal').style.display='none'">&times;</span>
            <h2>Create New Block</h2>
            <form method="POST" action="../../php/create_block.php">
                <div class="form-group">
                    <label>Block Name</label>
                    <input type="text" name="block_name" placeholder="e.g., 1A, 1B, 2A" required>
                </div>
                <div class="form-group">
                    <label>Course</label>
                    <select name="course" required>
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
                    <select name="year_level" required>
                        <option value="">Select Year</option>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Semester</label>
                    <input type="text" name="semester" value="<?php echo htmlspecialchars($sys_semester); ?>" readonly style="background:var(--surface-alt);color:var(--text-label);cursor:not-allowed;">
                </div>
                <div class="form-group">
                    <label>School Year</label>
                    <input type="text" name="school_year" value="<?php echo htmlspecialchars($sys_school_year); ?>" readonly style="background:var(--surface-alt);color:var(--text-label);cursor:not-allowed;">
                </div>
                <div class="form-group">
                    <label>Max Students</label>
                    <input type="number" name="max_students" value="40" min="1" required>
                </div>
                <button type="submit" class="btn-submit">Create Block</button>
            </form>
        </div>
    </div>

    <!-- ── Edit Block Modal ───────────────────────────── -->
    <div id="editBlockModal" class="modal">
        <div class="modal-content block-modal">
            <span class="close" onclick="document.getElementById('editBlockModal').style.display='none'">&times;</span>
            <h2>Edit Block</h2>
            <form method="POST" action="../../php/update_block.php">
                <input type="hidden" name="block_id" id="edit_block_id">
                <div class="form-group">
                    <label>Block Name</label>
                    <input type="text" name="block_name" id="edit_block_name" required>
                </div>
                <div class="form-group">
                    <label>Course</label>
                    <select name="course" id="edit_course" required>
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
                    <select name="year_level" id="edit_year_level" required>
                        <option value="">Select Year</option>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Semester</label>
                    <input type="text" name="semester" id="edit_semester" readonly style="background:var(--surface-alt);color:var(--text-label);cursor:not-allowed;">
                </div>
                <div class="form-group">
                    <label>School Year</label>
                    <input type="text" name="school_year" id="edit_school_year" readonly style="background:var(--surface-alt);color:var(--text-label);cursor:not-allowed;">
                </div>
                <div class="form-group">
                    <label>Max Students</label>
                    <input type="number" name="max_students" id="edit_max_students" min="1" required>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="edit_status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <button type="submit" class="btn-submit">Update Block</button>
            </form>
        </div>
    </div>

    <script src="../../js/admin/admin_main.js"></script>
    <script src="../../js/admin/admin_block.js"></script>
</body>
</html>