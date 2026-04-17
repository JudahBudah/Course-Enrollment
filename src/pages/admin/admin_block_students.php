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
    <link rel="stylesheet" href="../../css/admin/admin_main.css">
    <link rel="stylesheet" href="../../css/admin/admin_block_students.css">
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
                        <a href="admin_blocks.php" class="active">
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
                        </div>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Student No.</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Registration</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($assigned_students) > 0): ?>
                                        <?php while ($s = mysqli_fetch_assoc($assigned_students)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($s['student_number'] ?? $s['student_id']); ?></td>
                                            <td><?php echo htmlspecialchars(trim(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? ''))); ?></td>
                                            <td><?php echo htmlspecialchars($s['email'] ?? ''); ?></td>
                                            <td>
                                                <span class="badge <?php echo strtolower($s['registration_status'] ?? 'regular'); ?>">
                                                    <?php echo htmlspecialchars($s['registration_status'] ?? 'Regular'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <form method="POST" action="../../php/remove_student_from_block.php"
                                                      style="display:inline;">
                                                    <input type="hidden" name="student_id" value="<?php echo $s['student_id']; ?>">
                                                    <input type="hidden" name="block_id"   value="<?php echo $block_id; ?>">
                                                    <button type="submit" class="btn-icon remove" title="Remove from Block"
                                                            onclick="return confirm('Remove student from this block?')">
                                                        <i class="fa-solid fa-user-minus"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" style="text-align:center;color:var(--text-label);padding:1.5rem;">
                                                No students assigned yet.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
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

                        <!-- Individual assignment -->
                        <div class="assign-section">
                            <h3><i class="fa-solid fa-user-plus"></i> Assign Individual Student</h3>
                            <form method="POST" action="../../php/assign_student_to_block.php">
                                <input type="hidden" name="block_id" value="<?php echo $block_id; ?>">
                                <div class="assign-select-row">
                                    <select name="student_id" id="studentSelect" required>
                                        <option value="">Choose a student…</option>
                                        <?php while ($s = mysqli_fetch_assoc($unassigned_students)): ?>
                                            <option value="<?php echo $s['student_id']; ?>"
                                                    data-name="<?php echo htmlspecialchars(strtolower(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? ''))); ?>"
                                                    data-id="<?php echo htmlspecialchars(strtolower($s['student_number'] ?? $s['student_id'])); ?>"
                                                    data-email="<?php echo htmlspecialchars(strtolower($s['email'] ?? '')); ?>"
                                                    data-status="<?php echo htmlspecialchars($s['registration_status'] ?? 'Regular'); ?>">
                                                <?php echo htmlspecialchars(
                                                    ($s['student_number'] ?? $s['student_id']) . ' — ' .
                                                    trim(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? ''))
                                                ); ?>
                                                <?php if (($s['registration_status'] ?? '') === 'Irregular'): ?>
                                                    (Irregular)
                                                <?php endif; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <button type="submit" class="btn-primary">
                                        <i class="fa-solid fa-user-plus"></i>
                                        <span class="li-name">Assign</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Batch assignment -->
                        <div class="batch-section">
                            <h3><i class="fa-solid fa-users-gear"></i> Batch Assign Students</h3>
                            <form method="POST" action="../../php/batch_assign_to_block.php"
                                  onsubmit="return confirm('Assign all filtered students to this block? This may take a moment.')">
                                <input type="hidden" name="block_id"    value="<?php echo $block_id; ?>">
                                <input type="hidden" name="course"      value="<?php echo htmlspecialchars($block['course']); ?>">
                                <input type="hidden" name="year_level"  value="<?php echo htmlspecialchars($block['year_level']); ?>">

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
                                    • All unassigned students from <?php echo htmlspecialchars($block['course']); ?>,
                                    Year <?php echo htmlspecialchars($block['year_level']); ?> will be assigned to this block<br>
                                    • Students will be auto-enrolled in all block subjects<br>
                                    • Block capacity: <?php echo $block['current_students']; ?> / <?php echo $block['max_students']; ?>
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
</body>
</html>