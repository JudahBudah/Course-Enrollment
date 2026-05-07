<?php
session_start();
include("../../php/connection.php");
include("../../php/admin_functions.php");

$admin_data = check_admin_login($con);
$pending_applicants = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM applicants WHERE application_status='pending'"))['c'];

$block_id = (int)($_GET['block_id'] ?? 0);

// Get block info
$block = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM blocks WHERE block_id = $block_id"));
if (!$block) {
    header("Location: admin_blocks.php");
    exit;
}

// Students assigned to this block
$assigned_students = mysqli_query($con, "
    SELECT * FROM students
    WHERE block_id = $block_id
    ORDER BY last_name, first_name
");

// Unassigned students matching block's course + year
// Match on both course_code and course_name since students may store either
$course_esc = mysqli_real_escape_string($con, $block['course']);
$year_esc   = mysqli_real_escape_string($con, $block['year_level']);

// Get both the course_code and course_name for this block's course value
$course_match = mysqli_fetch_assoc(mysqli_query($con,
    "SELECT course_code, course_name FROM courses
     WHERE course_code = '$course_esc' OR course_name = '$course_esc' LIMIT 1"
));
$course_code_esc = mysqli_real_escape_string($con, $course_match['course_code'] ?? $course_esc);
$course_name_esc = mysqli_real_escape_string($con, $course_match['course_name'] ?? $course_esc);

$unassigned_students = mysqli_query($con, "
    SELECT * FROM students
    WHERE (block_id IS NULL OR block_id = 0)
    AND (course = '$course_code_esc' OR course = '$course_name_esc')
    AND year_level = '$year_esc'
    ORDER BY last_name, first_name
");

$slots_available = $block['max_students'] - $block['current_students'];
$is_full         = $block['current_students'] >= $block['max_students'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Block Students - PLM Admin</title>
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
    <link rel="stylesheet" href="../../css/admin/admin_block_students.css">
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

                <!-- Page Header -->
                <div class="page-header">
                    <h1>Block <?php echo htmlspecialchars($block['block_name']); ?> — Students</h1>
                    <p class="block-meta">
                        <?php echo htmlspecialchars($block['course']); ?> |
                        Year <?php echo htmlspecialchars($block['year_level']); ?> |
                        <?php echo htmlspecialchars($block['semester']); ?> Semester
                        <?php echo htmlspecialchars($block['school_year']); ?>
                    </p>
                    <p class="block-meta">
                        <strong>Capacity:</strong>
                        <?php echo $block['current_students']; ?> / <?php echo $block['max_students']; ?> students
                        <?php if ($is_full): ?>
                            <span style="color:#dc2626;font-weight:600;margin-left:0.5rem;">
                                <i class="fa-solid fa-exclamation-triangle"></i> Full
                            </span>
                        <?php else: ?>
                            <span style="color:var(--text-label);margin-left:0.5rem;">(<?php echo $slots_available; ?> slots available)</span>
                        <?php endif; ?>
                    </p>
                    <a href="admin_blocks.php" class="back-link">
                        <i class="fa-solid fa-arrow-left"></i> Back to Blocks
                    </a>
                </div>

                <?php if (isset($_GET['success']) && $_GET['success'] === 'assigned'): ?>
                    <div class="success-message"><i class="fa-solid fa-check-circle"></i> Student assigned successfully!</div>
                <?php elseif (isset($_GET['success']) && $_GET['success'] === 'removed'): ?>
                    <div class="success-message"><i class="fa-solid fa-check-circle"></i> Student removed from block successfully!</div>
                <?php elseif (isset($_GET['success']) && $_GET['success'] === 'batch'): ?>
                    <?php $count = (int)($_GET['count'] ?? 0); ?>
                    <div class="success-message"><i class="fa-solid fa-check-circle"></i> Successfully assigned <?php echo $count; ?> student<?php echo $count > 1 ? 's' : ''; ?> to block!</div>
                <?php endif; ?>
                <?php if (isset($_GET['error'])): ?>
                    <?php
                    $errors = [
                        'full' => 'Block is at full capacity.',
                        'failed' => 'Failed to assign student. Please try again.',
                        'block_not_found' => 'Block not found.',
                        'not_in_block' => 'Student is not in this block.',
                        'no_students' => 'No eligible students found to assign.',
                    ];
                    $error_msg = $errors[$_GET['error']] ?? 'An error occurred.';
                    ?>
                    <div class="error-message"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error_msg); ?></div>
                <?php endif; ?>

                <div class="content-grid">

                    <!-- ── Assigned Students Table ──────── -->
                    <div class="card">
                        <div class="card-header">
                            <h2>Students in Block <?php echo htmlspecialchars($block['block_name']); ?></h2>
                            <div style="margin-left:auto;">
                                <div class="assigned-search-bar">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    <input type="text" id="assignedSearch" placeholder="Search assigned students…">
                                </div>
                            </div>
                        </div>

                        <div class="capacity-bar-wrap">
                            <?php $pct = $block['max_students'] > 0 ? round($block['current_students'] / $block['max_students'] * 100) : 0; ?>
                            <div class="capacity-bar-track">
                                <div class="capacity-bar-fill <?php echo $is_full ? 'full' : ($pct >= 80 ? 'warn' : ''); ?>" id="capacityFill"
                                     style="width:<?php echo $pct; ?>%"></div>
                            </div>
                            <span class="capacity-bar-label" id="capacityLabel">
                                <?php echo $block['current_students']; ?> / <?php echo $block['max_students']; ?> students
                            </span>
                        </div>

                        <div class="block-students-table-wrapper">
                            <div class="block-students-table">

                                <div class="block-students-table-header">
                                    <div>Student No.</div>
                                    <div class="block-students-col-left">Name</div>
                                    <div class="block-students-col-left">Email</div>
                                    <div>Registration</div>
                                    <div>Action</div>
                                </div>

                                <div class="block-students-table-body" id="assignedTableBody">
                                <?php if (mysqli_num_rows($assigned_students) > 0): ?>
                                    <?php while ($s = mysqli_fetch_assoc($assigned_students)): ?>
                                    <div class="block-students-row">
                                        <div><?php echo htmlspecialchars($s['student_number'] ?? $s['student_id']); ?></div>
                                        <div class="block-students-col-left"><?php echo htmlspecialchars(trim(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? ''))); ?></div>
                                        <div class="block-students-col-left word-break"><?php echo htmlspecialchars($s['email'] ?? ''); ?></div>
                                        <div>
                                            <span class="badge <?php echo strtolower($s['registration_status'] ?? 'regular'); ?>">
                                                <?php echo htmlspecialchars($s['registration_status'] ?? 'Regular'); ?>
                                            </span>
                                        </div>
                                        <div>
                                            <div class="action-buttons">
                                                <form method="POST" action="../../php/remove_student_from_block.php" style="display:inline;">
                                                    <input type="hidden" name="student_id" value="<?php echo $s['student_id']; ?>">
                                                    <input type="hidden" name="block_id"   value="<?php echo $block_id; ?>">
                                                    <button type="submit" class="btn-icon remove" title="Remove from Block"
                                                            onclick="return confirm('Remove student from this block?')">
                                                        <i class="fa-solid fa-user-minus"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="block-students-empty" id="assignedEmpty">No students assigned yet.</div>
                                <?php endif; ?>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- ── Assign Students Panel ────────── -->
                    <div class="card">
                        <div class="card-header"><h2>Assign Students</h2></div>

                        <!-- Filter bar -->
                        <div class="assign-filter-bar">
                            <div class="assign-filter-grid">
                                <div>
                                    <label><i class="fa-solid fa-search"></i> Search Student</label>
                                    <input type="text" id="studentSearch"
                                        placeholder="Search by ID, name, or email…"
                                        oninput="filterStudents()">
                                </div>
                                <div>
                                    <label><i class="fa-solid fa-filter"></i> Registration Status</label>
                                    <select id="statusFilter" onchange="filterStudents()">
                                        <option value="">All Students</option>
                                        <option value="Regular">Regular Only</option>
                                        <option value="Irregular">Irregular Only</option>
                                    </select>
                                </div>
                                <div>
                                    <button onclick="clearFilters()" class="btn-secondary"
                                            style="padding:0.65rem 1rem;white-space:nowrap;">
                                        <i class="fa-solid fa-times"></i> Clear
                                    </button>
                                </div>
                            </div>
                            <p class="assign-filter-note">
                                <i class="fa-solid fa-info-circle"></i>
                                Showing students from <?php echo htmlspecialchars($course_name_esc ?: $block['course']); ?>,
                                Year <?php echo htmlspecialchars($block['year_level']); ?> without a block.
                                <span class="student-count-note" id="studentCount"></span>
                            </p>
                        </div>

                        <!-- Student picker list -->
                        <div class="assign-section">
                            <h3><i class="fa-solid fa-user-plus"></i> Assign Individual Student</h3>

                            <div class="student-picker" id="studentPicker">
                                <?php
                                // Re-run query since it was consumed above by the select
                                $unassigned2 = mysqli_query($con, "
                                    SELECT * FROM students
                                    WHERE (block_id IS NULL OR block_id = 0)
                                    AND (course = '$course_code_esc' OR course = '$course_name_esc')
                                    AND year_level = '$year_esc'
                                    ORDER BY last_name, first_name
                                ");
                                if (mysqli_num_rows($unassigned2) === 0): ?>
                                    <div class="picker-empty">No unassigned students found for this block's course and year.</div>
                                <?php else: ?>
                                    <?php while ($s = mysqli_fetch_assoc($unassigned2)): ?>
                                    <div class="picker-card"
                                         data-id="<?php echo $s['student_id']; ?>"
                                         data-name="<?php echo htmlspecialchars(strtolower(($s['first_name']??'').' '.($s['last_name']??''))); ?>"
                                         data-number="<?php echo htmlspecialchars(strtolower($s['student_number']??'')); ?>"
                                         data-email="<?php echo htmlspecialchars(strtolower($s['email']??'')); ?>"
                                         data-status="<?php echo htmlspecialchars($s['registration_status']??'Regular'); ?>">
                                        <div class="picker-card-info">
                                            <div class="picker-card-name"><?php echo htmlspecialchars(trim(($s['first_name']??'').' '.($s['last_name']??''))); ?></div>
                                            <div class="picker-card-sub">
                                                <?php echo htmlspecialchars($s['student_number']??''); ?>
                                                <?php if ($s['email']): ?> &middot; <?php echo htmlspecialchars($s['email']); ?><?php endif; ?>
                                            </div>
                                        </div>
                                        <span class="badge <?php echo strtolower($s['registration_status']??'regular'); ?>" style="flex-shrink:0;">
                                            <?php echo htmlspecialchars($s['registration_status']??'Regular'); ?>
                                        </span>
                                        <button type="button" class="btn-assign-card"
                                                data-student-id="<?php echo $s['student_id']; ?>"
                                                data-block-id="<?php echo $block_id; ?>">
                                            <i class="fa-solid fa-plus"></i> Assign
                                        </button>
                                    </div>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Batch assignment -->
                        <div class="batch-section">
                            <h3><i class="fa-solid fa-users-gear"></i> Batch Assign Students</h3>
                            <form method="POST" action="../../php/batch_assign_to_block.php"
                                onsubmit="return confirm('Assign all filtered students to this block? This may take a moment.')">
                                <input type="hidden" name="block_id"   value="<?php echo $block_id; ?>">
                                <input type="hidden" name="course"     value="<?php echo htmlspecialchars($block['course']); ?>">
                                <input type="hidden" name="year_level" value="<?php echo htmlspecialchars($block['year_level']); ?>">

                                <div class="batch-option-box">
                                    <label>
                                        <input type="checkbox" name="regular_only" value="1" checked>
                                        Assign only <strong>Regular</strong> students (skip Irregular students)
                                    </label>
                                </div>

                                <div class="batch-info-box">
                                    <div class="info-title">
                                        <i class="fa-solid fa-info-circle"></i> What will happen:
                                    </div>
                                    &bull; All unassigned students from <?php echo htmlspecialchars($block['course']); ?>,
                                    Year <?php echo htmlspecialchars($block['year_level']); ?> will be assigned to this block<br>
                                    &bull; Students will be auto-enrolled in all block subjects<br>
                                    &bull; Block capacity: <?php echo $block['current_students']; ?> / <?php echo $block['max_students']; ?>
                                    (<?php echo $slots_available; ?> slots available)
                                </div>

                                <button type="submit" class="btn-submit" <?php echo $is_full ? 'disabled' : ''; ?>>
                                    <i class="fa-solid fa-users-gear"></i> Batch Assign All Eligible Students
                                </button>

                                <?php if ($is_full): ?>
                                    <p class="capacity-full-note">
                                        <i class="fa-solid fa-exclamation-triangle"></i> Block is at full capacity
                                    </p>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>

                </div><!-- /.content-grid -->

            </div><!-- /.main-content -->
        </main>
    </div><!-- /.main-flex -->

    <script src="../../js/admin/admin_main.js"></script>
    <script src="../../js/admin/admin_block_students.js"></script>
    <script>
        const leftCard  = document.querySelector('.content-grid > .card:first-child');
        const rightCard = document.querySelector('.content-grid > .card:last-child');

        function syncHeight() {
            const rightHeight = rightCard.getBoundingClientRect().height;
            leftCard.style.maxHeight = rightHeight + 'px';
        }

        const ro = new ResizeObserver(syncHeight);
        ro.observe(rightCard);

        syncHeight(); // run once on load
    </script>
</body>
</html>