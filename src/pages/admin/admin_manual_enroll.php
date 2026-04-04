<?php
session_start();
include("../../php/connection.php");
include("../../php/admin_functions.php");

$admin_data = check_admin_login($con);
$pending_applicants = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM applicants WHERE application_status='pending'"))['c'];

$student_id = (int)($_GET['student_id'] ?? 0);

// Get student info
$student = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM students WHERE student_id = $student_id"));
if (!$student) {
    header("Location: admin_students.php");
    exit;
}

// Check required tables
$enrollments_exists = mysqli_num_rows(mysqli_query($con, "SHOW TABLES LIKE 'enrollments'")) > 0;
$classes_exists     = mysqli_num_rows(mysqli_query($con, "SHOW TABLES LIKE 'classes'"))     > 0;

// Fetch enrollment sets
if ($enrollments_exists && $classes_exists) {
    $reserved_query = mysqli_query($con, "
        SELECT e.enrollment_id, e.status, c.class_id, c.section, c.schedule_day,
               c.schedule_time, c.room, c.max_slots, c.enrolled_count,
               s.subject_code, s.subject_name, s.units,
               CONCAT(f.first_name,' ',f.last_name) as faculty_name
        FROM enrollments e
        JOIN classes c ON e.class_id = c.class_id
        JOIN subjects s ON c.subject_id = s.subject_id
        LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
        WHERE e.student_id = $student_id AND e.status = 'reserved'
        ORDER BY s.subject_code
    ");

    $drop_requests_query = mysqli_query($con, "
        SELECT e.enrollment_id, e.status, c.class_id, c.section, c.schedule_day,
               c.schedule_time, c.room,
               s.subject_code, s.subject_name, s.units,
               CONCAT(f.first_name,' ',f.last_name) as faculty_name
        FROM enrollments e
        JOIN classes c ON e.class_id = c.class_id
        JOIN subjects s ON c.subject_id = s.subject_id
        LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
        WHERE e.student_id = $student_id AND e.status = 'drop_requested'
        ORDER BY s.subject_code
    ");

    $enrolled_query = mysqli_query($con, "
        SELECT e.enrollment_id, e.status, c.class_id, c.section, c.schedule_day,
               c.schedule_time, c.room,
               s.subject_code, s.subject_name, s.units,
               CONCAT(f.first_name,' ',f.last_name) as faculty_name
        FROM enrollments e
        JOIN classes c ON e.class_id = c.class_id
        JOIN subjects s ON c.subject_id = s.subject_id
        LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
        WHERE e.student_id = $student_id AND e.status IN ('confirmed','ongoing')
        ORDER BY s.subject_code
    ");

    $available_query = mysqli_query($con, "
        SELECT c.class_id, c.section, c.schedule_day, c.schedule_time, c.room,
               c.max_slots, c.enrolled_count,
               s.subject_code, s.subject_name, s.units,
               CONCAT(f.first_name,' ',f.last_name) as faculty_name
        FROM classes c
        JOIN subjects s ON c.subject_id = s.subject_id
        LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
        WHERE c.class_id NOT IN (
            SELECT class_id FROM enrollments
            WHERE student_id = $student_id
            AND status IN ('reserved','confirmed','ongoing')
        )
        AND c.status = 'open'
        AND c.enrolled_count < c.max_slots
        ORDER BY s.subject_code, c.section
    ");
} else {
    $reserved_query = $drop_requests_query = $enrolled_query = $available_query = false;
}

// Stats
$reserved_count      = $reserved_query      ? mysqli_num_rows($reserved_query)      : 0;
$drop_requests_count = $drop_requests_query ? mysqli_num_rows($drop_requests_query) : 0;
$enrolled_count      = $enrolled_query      ? mysqli_num_rows($enrolled_query)      : 0;
$total_units = 0;
if ($enrolled_query && $enrolled_count > 0) {
    while ($r = mysqli_fetch_assoc($enrolled_query)) $total_units += $r['units'];
    mysqli_data_seek($enrolled_query, 0);
}

// Collect reserved rows
$reserved_rows = [];
if ($reserved_query) {
    while ($r = mysqli_fetch_assoc($reserved_query)) $reserved_rows[] = $r;
}

// Debug rows if none reserved
$debug_rows = null;
if (empty($reserved_rows)) {
    $dq = mysqli_query($con, "SELECT enrollment_id, status FROM enrollments WHERE student_id = $student_id");
    $debug_rows = [];
    while ($d = mysqli_fetch_assoc($dq)) $debug_rows[] = $d;
}

// Block name
$block_name = null;
if ($student['block_id']) {
    $bq = mysqli_fetch_assoc(mysqli_query($con, "SELECT block_name FROM blocks WHERE block_id = {$student['block_id']}"));
    $block_name = $bq['block_name'] ?? null;
}

$load_status_card = $total_units > 24 ? 'red' : 'navy';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual Enrollment - PLM Admin</title>
    <link rel="icon" href="../../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/admin/admin_main.css">
    <link rel="stylesheet" href="../../css/admin/admin_manual_enroll.css">
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
                        <a href="admin_students.php" class="active">
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
                    <h1>Manual Enrollment</h1>
                    <p class="student-info">
                        <strong><?php echo htmlspecialchars(trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''))); ?></strong>
                        (<?php echo htmlspecialchars($student['student_id']); ?>)
                    </p>
                    <p class="student-info">
                        <?php echo htmlspecialchars($student['course'] ?? 'N/A'); ?> |
                        Year <?php echo htmlspecialchars($student['year_level'] ?? 'N/A'); ?> |
                        <?php if ($block_name): ?>
                            Block: <strong><?php echo htmlspecialchars($block_name); ?></strong>
                        <?php else: ?>
                            <span class="irregular-note">Irregular Student (No Block)</span>
                        <?php endif; ?>
                    </p>
                    <a href="admin_students.php" class="back-link">
                        <i class="fa-solid fa-arrow-left"></i> Back to Students
                    </a>
                </div>

                <?php if (!$block_name): ?>
                <div class="info-message">
                    <i class="fa-solid fa-info-circle"></i>
                    This is an irregular student without a block assignment. Use manual enrollment to add subjects individually.
                </div>
                <?php endif; ?>

                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card gold">
                        <div class="stat-icon"><i class="fa-solid fa-clock"></i></div>
                        <div class="stat-content"><h3>Pending Confirmation</h3><p class="stat-number"><?php echo $reserved_count; ?></p></div>
                    </div>
                    <div class="stat-card red">
                        <div class="stat-icon"><i class="fa-solid fa-right-from-bracket"></i></div>
                        <div class="stat-content"><h3>Drop Requests</h3><p class="stat-number"><?php echo $drop_requests_count; ?></p></div>
                    </div>
                    <div class="stat-card blue">
                        <div class="stat-icon"><i class="fa-solid fa-book"></i></div>
                        <div class="stat-content"><h3>Enrolled Subjects</h3><p class="stat-number"><?php echo $enrolled_count; ?></p></div>
                    </div>
                    <div class="stat-card green">
                        <div class="stat-icon"><i class="fa-solid fa-calculator"></i></div>
                        <div class="stat-content"><h3>Total Units</h3><p class="stat-number"><?php echo $total_units; ?></p></div>
                    </div>
                    <div class="stat-card <?php echo $load_status_card; ?>">
                        <div class="stat-icon"><i class="fa-solid fa-gauge"></i></div>
                        <div class="stat-content">
                            <h3>Load Status</h3>
                            <p class="stat-number"><?php echo $total_units <= 24 ? 'Normal' : 'Overload'; ?></p>
                            <small>Max: 24 units</small>
                        </div>
                    </div>
                </div>

                <!-- Missing tables warning -->
                <?php if (!$enrollments_exists || !$classes_exists): ?>
                <div class="error-message">
                    <i class="fa-solid fa-exclamation-triangle"></i>
                    <strong>Required tables not found!</strong> Please create the following tables first:
                    <?php if (!$classes_exists): ?>
                        <br>• classes table &nbsp;• subjects table &nbsp;• faculty table
                    <?php endif; ?>
                    <?php if (!$enrollments_exists): ?>
                        <br>• enrollments table
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- ── Pending Confirmation Table ──────── -->
                <div class="card" style="margin-bottom:1.5rem;">
                    <div class="card-header gold-header">
                        <h2><i class="fa-solid fa-clock"></i> Pending Student Confirmation (<?php echo count($reserved_rows); ?>)</h2>
                    </div>

                    <?php if ($debug_rows !== null): ?>
                    <div class="debug-bar">
                        <strong>Debug:</strong> No reserved rows found. Raw enrollment statuses for this student:
                        <?php if (empty($debug_rows)): ?>
                            <span style="margin-left:8px;">No enrollments at all for this student.</span>
                        <?php else: ?>
                            <?php foreach ($debug_rows as $d): ?>
                                <span class="debug-status-chip">#<?php echo $d['enrollment_id']; ?> = <strong><?php echo htmlspecialchars($d['status']); ?></strong></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Subject Code</th>
                                    <th>Subject Name</th>
                                    <th>Units</th>
                                    <th>Section</th>
                                    <th>Schedule</th>
                                    <th>Instructor</th>
                                    <th>Room</th>
                                    <th>Slots</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reserved_rows)): ?>
                                    <tr>
                                        <td colspan="9" style="text-align:center;color:var(--text-label);padding:1.5rem;">
                                            No pending reservations.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reserved_rows as $r): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($r['subject_code']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($r['subject_name']); ?></td>
                                        <td><?php echo $r['units']; ?></td>
                                        <td><?php echo htmlspecialchars($r['section'] ?? 'TBA'); ?></td>
                                        <td><?php echo htmlspecialchars(($r['schedule_day'] ?? 'TBA') . ' ' . ($r['schedule_time'] ?? '')); ?></td>
                                        <td><?php echo htmlspecialchars($r['faculty_name'] ?? 'TBA'); ?></td>
                                        <td><?php echo htmlspecialchars($r['room'] ?? 'TBA'); ?></td>
                                        <td><?php echo $r['enrolled_count'] . '/' . $r['max_slots']; ?></td>
                                        <td>
                                            <form method="POST" action="../../php/drop_enrollment.php" style="display:inline;">
                                                <input type="hidden" name="student_id"    value="<?php echo $student_id; ?>">
                                                <input type="hidden" name="enrollment_id" value="<?php echo $r['enrollment_id']; ?>">
                                                <input type="hidden" name="class_id"      value="<?php echo $r['class_id']; ?>">
                                                <button type="submit" class="btn-icon cancel" title="Cancel Reservation"
                                                        onclick="return confirm('Cancel this reservation?')">
                                                    <i class="fa-solid fa-xmark"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ── Confirmed Enrollment + Add Subject ─ -->
                <div class="content-grid">

                    <!-- Confirmed Enrollment Table -->
                    <div class="card">
                        <div class="card-header"><h2>Confirmed Enrollment</h2></div>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Subject Code</th>
                                        <th>Subject Name</th>
                                        <th>Units</th>
                                        <th>Section</th>
                                        <th>Schedule</th>
                                        <th>Instructor</th>
                                        <th>Room</th>
                                        <th>Type</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($enrolled_count === 0): ?>
                                        <tr>
                                            <td colspan="9" style="text-align:center;color:var(--text-label);padding:1.5rem;">
                                                No confirmed enrollments yet.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php
                                        $status_colors = ['confirmed' => '#16a34a', 'ongoing' => '#2563eb'];
                                        $status_labels = ['confirmed' => 'Confirmed', 'ongoing' => 'Self Enrolled'];
                                        while ($sub = mysqli_fetch_assoc($enrolled_query)):
                                            $s     = $sub['status'];
                                            $color = $status_colors[$s] ?? '#888';
                                            $label = $status_labels[$s] ?? ucfirst($s);
                                        ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($sub['subject_code']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($sub['subject_name']); ?></td>
                                            <td><?php echo $sub['units']; ?></td>
                                            <td><?php echo htmlspecialchars($sub['section'] ?? 'TBA'); ?></td>
                                            <td><?php echo htmlspecialchars(($sub['schedule_day'] ?? 'TBA') . ' ' . ($sub['schedule_time'] ?? '')); ?></td>
                                            <td><?php echo htmlspecialchars($sub['faculty_name'] ?? 'TBA'); ?></td>
                                            <td><?php echo htmlspecialchars($sub['room'] ?? 'TBA'); ?></td>
                                            <td>
                                                <span class="enroll-status-badge"
                                                      style="background:<?php echo $color; ?>1a;color:<?php echo $color; ?>;">
                                                    <?php echo $label; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <form method="POST" action="../../php/drop_enrollment.php" style="display:inline;">
                                                    <input type="hidden" name="student_id"    value="<?php echo $student_id; ?>">
                                                    <input type="hidden" name="enrollment_id" value="<?php echo $sub['enrollment_id']; ?>">
                                                    <input type="hidden" name="class_id"      value="<?php echo $sub['class_id']; ?>">
                                                    <button type="submit" class="btn-icon" title="Drop"
                                                            onclick="return confirm('Drop this subject?')">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Reserve Subject Card -->
                    <div class="card">
                        <div class="card-header"><h2>Reserve Subject for Student</h2></div>
                        <form method="POST" action="../../php/manual_enroll.php">
                            <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                            <div class="reserve-form-body">
                                <label>Select Class to Reserve</label>
                                <select name="class_id" id="classSelect"
                                        <?php echo !$available_query ? 'disabled' : 'required'; ?>>
                                    <option value="">
                                        <?php echo !$available_query ? 'No classes available' : 'Choose a class…'; ?>
                                    </option>
                                    <?php if ($available_query): ?>
                                        <?php while ($cls = mysqli_fetch_assoc($available_query)): ?>
                                        <option value="<?php echo $cls['class_id']; ?>"
                                                data-units="<?php echo $cls['units']; ?>">
                                            <?php echo htmlspecialchars(
                                                $cls['subject_code'] . ' - ' . $cls['subject_name'] .
                                                ' (' . $cls['units'] . ' units) | ' .
                                                $cls['section'] . ' | ' .
                                                ($cls['schedule_day'] ?? 'TBA') . ' ' . ($cls['schedule_time'] ?? '') . ' | ' .
                                                ($cls['room'] ?? 'TBA') . ' | ' .
                                                $cls['enrolled_count'] . '/' . $cls['max_slots']
                                            ); ?>
                                        </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>

                                <div class="unit-warning" id="unitWarning" style="display:none;">
                                    <i class="fa-solid fa-exclamation-triangle"></i>
                                    <span class="unit-warning-text"></span>
                                </div>

                                <button type="submit" class="btn-reserve"
                                        <?php echo !$available_query ? 'disabled' : ''; ?>>
                                    <i class="fa-solid fa-bookmark"></i> Reserve for Student
                                </button>
                            </div>
                        </form>
                        <p class="reserve-note">
                            <i class="fa-solid fa-info-circle"></i>
                            This creates a reservation. The student must confirm it on their portal.
                        </p>
                    </div>

                </div><!-- /.content-grid -->

            </div><!-- /.main-content -->
        </main>
    </div><!-- /.main-flex -->

    <script>window._currentUnits = <?php echo (int)$total_units; ?>;</script>
    <script src="../../js/admin/admin_main.js"></script>
    <script src="../../js/admin/admin_manual_enroll.js"></script>
</body>
</html>