<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

include("../../php/connection.php");
include("../../php/admin_functions.php");

$admin_data         = check_admin_login($con);
$pending_applicants = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM applicants WHERE application_status='pending'"))['c'];

$student_id = (int)($_GET['student_id'] ?? 0);
$student    = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM students WHERE student_id = $student_id"));
if (!$student) { header("Location: admin_students.php"); exit; }

$student_course = $student['course'] ?? '';

// Get course_id
$course_id = null;
$cr = mysqli_prepare($con, "SELECT course_id FROM courses WHERE course_name = ? OR course_code = ? LIMIT 1");
mysqli_stmt_bind_param($cr, "ss", $student_course, $student_course);
mysqli_stmt_execute($cr);
$crow = mysqli_fetch_assoc(mysqli_stmt_get_result($cr));
if ($crow) $course_id = $crow['course_id'];

// Curriculum grouped by year > semester
$curriculum = [];
if ($course_id) {
    $stmt = mysqli_prepare($con, "SELECT subject_id, subject_code, subject_name, units, lecture_hours, lab_hours, COALESCE(year_level, '0') as year_level, COALESCE(semester, 'N/A') as semester, prerequisite FROM subjects WHERE course_id = ? AND status = 'active' ORDER BY CAST(year_level AS UNSIGNED), FIELD(semester,'1st','2nd','summer'), subject_code");
    mysqli_stmt_bind_param($stmt, "i", $course_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $curriculum[$row['year_level']][$row['semester']][] = $row;
    }
}

// Student grades keyed by subject_id
$grades = [];
$stmt = mysqli_prepare($con, "SELECT subject_id, grade, status FROM grades WHERE student_id = ? ORDER BY grade_id DESC");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
    if (!isset($grades[$row['subject_id']])) $grades[$row['subject_id']] = $row;
}

// Build failed subject codes for prerequisite locking
$failed_codes = [];
foreach ($grades as $g) {
    if ($g['grade'] === '5.00' || strtoupper($g['grade'] ?? '') === 'INC') {
        $sc = mysqli_fetch_assoc(mysqli_query($con, "SELECT subject_code FROM subjects WHERE subject_id = {$g['subject_id']}"));
        if ($sc) $failed_codes[] = $sc['subject_code'];
    }
}

// Detect irregular: failed grades OR already marked Irregular
$is_irregular = !empty($failed_codes) || ($student['registration_status'] ?? 'Regular') === 'Irregular';
if (!empty($failed_codes) && ($student['registration_status'] ?? 'Regular') !== 'Irregular') {
    mysqli_query($con, "UPDATE students SET registration_status = 'Irregular' WHERE student_id = $student_id");
    $student['registration_status'] = 'Irregular';
}

// Helper: check if subject is locked by a failed prerequisite
function is_prereq_locked_admin($prereq_str, $failed_codes) {
    if (empty($prereq_str)) return ['locked' => false, 'reason' => ''];
    foreach (array_map('trim', explode(',', $prereq_str)) as $code) {
        if (in_array($code, $failed_codes))
            return ['locked' => true, 'reason' => "Prereq $code failed"];
    }
    return ['locked' => false, 'reason' => ''];
}

// Irregular: all open classes for this course not yet enrolled/passed
$irregular_classes = [];
if ($is_irregular && $course_id) {
    $stmt = mysqli_prepare($con, "
        SELECT c.class_id, c.subject_id, c.section, c.schedule_day, c.schedule_time, c.room,
               c.max_slots, c.enrolled_count, c.school_year, c.semester,
               s.subject_code, s.subject_name, s.units, s.year_level,
               s.semester as subj_sem, s.prerequisite,
               CONCAT(f.first_name,' ',f.last_name) as faculty_name
        FROM classes c
        JOIN subjects s ON c.subject_id = s.subject_id
        LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
        WHERE c.status = 'open' AND s.course_id = ?
        AND c.class_id NOT IN (
            SELECT class_id FROM enrollments
            WHERE student_id = ? AND status IN ('reserved','confirmed','ongoing','drop_requested')
        )
        ORDER BY CAST(s.year_level AS UNSIGNED), FIELD(s.semester,'1st','2nd','summer'), s.subject_code
    ");
    mysqli_stmt_bind_param($stmt, "ii", $course_id, $student_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $g = $grades[$row['subject_id']] ?? null;
        $gv = $g['grade'] ?? null;
        $passed = $gv && $gv !== '5.00' && strtoupper($gv) !== 'INC';
        if (!$passed) $irregular_classes[] = $row;
    }
}

// Active enrollments keyed by subject_id
$active_enrollments = [];
$stmt = mysqli_prepare($con, "SELECT e.enrollment_id, e.status, e.class_id, c.subject_id, c.section, c.schedule_day, c.schedule_time, c.room, CONCAT(f.first_name,' ',f.last_name) as faculty_name FROM enrollments e JOIN classes c ON e.class_id = c.class_id LEFT JOIN faculty f ON c.faculty_id = f.faculty_id WHERE e.student_id = ? AND e.status IN ('reserved','confirmed','ongoing','drop_requested')");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
    $active_enrollments[$row['subject_id']] = $row;
}

// Available classes per subject keyed by subject_id
$available_classes = [];
if ($course_id) {
    $stmt = mysqli_prepare($con, "SELECT c.class_id, c.subject_id, c.section, c.schedule_day, c.schedule_time, c.room, c.max_slots, c.enrolled_count, CONCAT(f.first_name,' ',f.last_name) as faculty_name FROM classes c JOIN subjects s ON c.subject_id = s.subject_id LEFT JOIN faculty f ON c.faculty_id = f.faculty_id WHERE c.status = 'open' AND s.course_id = ? ORDER BY c.subject_id, c.section");
    mysqli_stmt_bind_param($stmt, "i", $course_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $available_classes[$row['subject_id']][] = $row;
    }
}

// Current enrollment summary for stats
$stats = mysqli_fetch_assoc(mysqli_query($con, "
    SELECT
        SUM(e.status = 'reserved')       as reserved_count,
        SUM(e.status = 'confirmed')      as confirmed_count,
        SUM(e.status = 'ongoing')        as ongoing_count,
        SUM(e.status = 'drop_requested') as drop_count,
        COALESCE(SUM(CASE WHEN e.status IN ('reserved','confirmed','ongoing') THEN s.units ELSE 0 END), 0) as total_units
    FROM enrollments e
    JOIN classes c ON e.class_id = c.class_id
    JOIN subjects s ON c.subject_id = s.subject_id
    WHERE e.student_id = $student_id
"));

// Drop requests for the action panel
$drop_requests = mysqli_query($con, "SELECT e.enrollment_id, e.class_id, s.subject_code, s.subject_name, s.units, c.section, c.schedule_day, c.schedule_time, c.room, CONCAT(f.first_name,' ',f.last_name) as faculty_name FROM enrollments e JOIN classes c ON e.class_id = c.class_id JOIN subjects s ON c.subject_id = s.subject_id LEFT JOIN faculty f ON c.faculty_id = f.faculty_id WHERE e.student_id = $student_id AND e.status = 'drop_requested' ORDER BY s.subject_code");

// Self-enrolled (ongoing) pending review
$self_enrolled = mysqli_query($con, "SELECT e.enrollment_id, e.class_id, s.subject_code, s.subject_name, s.units, c.section, c.schedule_day, c.schedule_time, c.room, CONCAT(f.first_name,' ',f.last_name) as faculty_name FROM enrollments e JOIN classes c ON e.class_id = c.class_id JOIN subjects s ON c.subject_id = s.subject_id LEFT JOIN faculty f ON c.faculty_id = f.faculty_id WHERE e.student_id = $student_id AND e.status = 'ongoing' ORDER BY s.subject_code");

$year_labels = ['1' => '1st Year', '2' => '2nd Year', '3' => '3rd Year', '4' => '4th Year', '5' => '5th Year', '6' => '6th Year', '0' => 'Unassigned'];
$sem_labels  = ['1st' => '1st Semester', '2nd' => '2nd Semester', 'summer' => 'Summer', 'N/A' => 'Unassigned'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual Enrollment - PLM Admin</title>
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
    <link rel="stylesheet" href="../../css/admin/admin_manual_enroll.css">
</head>
<body>
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

<div class="main-flex">
<div class="spacer"></div>
<main>
<div class="main-content">

    <!-- Page Header -->
    <div class="page-header">
        <h1>Manual Enrollment</h1>
        <p class="student-info">
            <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong>
            &nbsp;·&nbsp; <?php echo htmlspecialchars($student['student_number']); ?>
            &nbsp;·&nbsp; <?php echo htmlspecialchars($student_course); ?>
            &nbsp;·&nbsp; Year <?php echo htmlspecialchars($student['year_level'] ?? 'N/A'); ?>
        </p>
        <a href="admin_students.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Students</a>
    </div>

    <!-- Flash messages -->
    <?php if (isset($_GET['success'])): ?>
        <?php $smsgs = ['reserved' => 'Subject reserved for student.', 'drop_accepted' => 'Drop request accepted.', 'drop_rejected' => 'Drop request rejected.', 'self_accepted' => 'Self-enrollment accepted.', 'self_rejected' => 'Self-enrollment rejected.']; ?>
        <div class="success-message"><i class="fa-solid fa-check-circle"></i> <?php echo $smsgs[$_GET['success']] ?? 'Action completed.'; ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <?php $emsgs = ['invalid' => 'Invalid request.', 'class_full' => 'Class is full.', 'already_enrolled' => 'Student is already enrolled in this subject.', 'failed' => 'Action failed. Please try again.', 'prereq' => 'Cannot enroll: prerequisite not passed.']; ?>
        <div class="error-message"><i class="fa-solid fa-exclamation-triangle"></i>
            <?php
            $emsg = $emsgs[$_GET['error']] ?? 'An error occurred.';
            if ($_GET['error'] === 'prereq' && !empty($_GET['missing'])) {
                $emsg .= ' Missing: ' . htmlspecialchars(urldecode($_GET['missing']));
            }
            echo $emsg;
            ?>
        </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card gold">
            <div class="stat-icon"><i class="fa-solid fa-clock"></i></div>
            <div class="stat-content"><h3>Pending</h3><p class="stat-number"><?php echo (int)$stats['reserved_count']; ?></p></div>
        </div>
        <div class="stat-card blue">
            <div class="stat-icon"><i class="fa-solid fa-check-circle"></i></div>
            <div class="stat-content"><h3>Confirmed</h3><p class="stat-number"><?php echo (int)$stats['confirmed_count']; ?></p></div>
        </div>
        <div class="stat-card red">
            <div class="stat-icon"><i class="fa-solid fa-right-from-bracket"></i></div>
            <div class="stat-content"><h3>Drop Requests</h3><p class="stat-number"><?php echo (int)$stats['drop_count']; ?></p></div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon"><i class="fa-solid fa-calculator"></i></div>
            <div class="stat-content"><h3>Total Units</h3><p class="stat-number"><?php echo (int)$stats['total_units']; ?></p></div>
        </div>
    </div>

    <!-- Drop Requests -->
    <?php if (mysqli_num_rows($drop_requests) > 0): ?>
    <div class="card" style="margin-bottom:1.5rem;border-left:4px solid #dc2626;">
        <div class="card-header" style="background:#dc2626;">
            <h2><i class="fa-solid fa-right-from-bracket"></i> Drop Requests</h2>
        </div>

        <div class="faculty-drop-table-wrapper">
            <div class="faculty-drop-table">

                <div class="faculty-drop-table-header">
                    <div>Subject Code</div>
                    <div class="faculty-drop-col-left">Subject Name</div>
                    <div>Units</div>
                    <div>Section</div>
                    <div>Schedule</div>
                    <div>Instructor</div>
                    <div>Action</div>
                </div>

                <div class="faculty-drop-table-body">
                <?php while ($dr = mysqli_fetch_assoc($drop_requests)): ?>
                <div class="faculty-drop-row">
                    <div><strong><?php echo htmlspecialchars($dr['subject_code']); ?></strong></div>
                    <div class="faculty-drop-col-left"><?php echo htmlspecialchars($dr['subject_name']); ?></div>
                    <div><?php echo $dr['units']; ?></div>
                    <div><?php echo htmlspecialchars($dr['section'] ?? 'TBA'); ?></div>
                    <div><?php echo htmlspecialchars(($dr['schedule_day'] ?? '') . ' ' . ($dr['schedule_time'] ?? '')); ?></div>
                    <div><?php echo htmlspecialchars($dr['faculty_name'] ?? 'TBA'); ?></div>
                    <div>
                        <div class="action-buttons">
                            <form method="POST" action="../../php/handle_drop_request.php" style="display:inline;">
                                <input type="hidden" name="student_id"    value="<?php echo $student_id; ?>">
                                <input type="hidden" name="enrollment_id" value="<?php echo $dr['enrollment_id']; ?>">
                                <input type="hidden" name="class_id"      value="<?php echo $dr['class_id']; ?>">
                                <input type="hidden" name="action"        value="accept">
                                <button type="submit" class="btn-icon" style="background:#16a34a;" title="Accept"
                                        onclick="return confirm('Accept drop request?')">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                            </form>
                            <form method="POST" action="../../php/handle_drop_request.php" style="display:inline;">
                                <input type="hidden" name="student_id"    value="<?php echo $student_id; ?>">
                                <input type="hidden" name="enrollment_id" value="<?php echo $dr['enrollment_id']; ?>">
                                <input type="hidden" name="action"        value="reject">
                                <button type="submit" class="btn-icon cancel" title="Reject"
                                        onclick="return confirm('Reject drop request?')">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
                </div>

            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Self-Enrolled Pending Review -->
    <?php if (mysqli_num_rows($self_enrolled) > 0): ?>
    <div class="card" style="margin-bottom:1.5rem;border-left:4px solid #2563eb;">
        <div class="card-header" style="background:#2563eb;">
            <h2><i class="fa-solid fa-user-pen"></i> Student Self-Enrollments — Pending Review</h2>
        </div>

        <div class="self-enroll-table-wrapper">
            <div class="self-enroll-table">

                <div class="self-enroll-table-header">
                    <div>Subject Code</div>
                    <div class="self-enroll-col-left">Subject Name</div>
                    <div>Units</div>
                    <div>Section</div>
                    <div>Schedule</div>
                    <div>Instructor</div>
                    <div>Action</div>
                </div>

                <div class="self-enroll-table-body">
                <?php while ($se = mysqli_fetch_assoc($self_enrolled)): ?>
                <div class="self-enroll-row">
                    <div><strong><?php echo htmlspecialchars($se['subject_code']); ?></strong></div>
                    <div class="self-enroll-col-left"><?php echo htmlspecialchars($se['subject_name']); ?></div>
                    <div><?php echo $se['units']; ?></div>
                    <div><?php echo htmlspecialchars($se['section'] ?? 'TBA'); ?></div>
                    <div><?php echo htmlspecialchars(($se['schedule_day'] ?? '') . ' ' . ($se['schedule_time'] ?? '')); ?></div>
                    <div><?php echo htmlspecialchars($se['faculty_name'] ?? 'TBA'); ?></div>
                    <div>
                        <div class="action-buttons">
                            <form method="POST" action="../../php/handle_self_enrollment.php" style="display:inline;">
                                <input type="hidden" name="student_id"    value="<?php echo $student_id; ?>">
                                <input type="hidden" name="enrollment_id" value="<?php echo $se['enrollment_id']; ?>">
                                <input type="hidden" name="class_id"      value="<?php echo $se['class_id']; ?>">
                                <input type="hidden" name="action"        value="accept">
                                <button type="submit" class="btn-icon" style="background:#16a34a;" title="Accept"
                                        onclick="return confirm('Accept self-enrollment?')">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                            </form>
                            <form method="POST" action="../../php/handle_self_enrollment.php" style="display:inline;">
                                <input type="hidden" name="student_id"    value="<?php echo $student_id; ?>">
                                <input type="hidden" name="enrollment_id" value="<?php echo $se['enrollment_id']; ?>">
                                <input type="hidden" name="class_id"      value="<?php echo $se['class_id']; ?>">
                                <input type="hidden" name="action"        value="reject">
                                <button type="submit" class="btn-icon cancel" title="Reject"
                                        onclick="return confirm('Reject self-enrollment?')">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
                </div>

            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Curriculum Enrollment -->
    <div class="card">
        <div class="card-header"><h2>Curriculum — <?php echo htmlspecialchars($student_course); ?></h2></div>

        <?php if (empty($curriculum)): ?>
            <div style="padding:2rem;text-align:center;color:var(--text);">No curriculum found for this program.</div>
        <?php else: ?>

        <!-- Year tabs -->
        <div class="curric-nav">
            <div class="curric-year-tabs">
                <?php $yi = 0; foreach ($curriculum as $yr => $sems): ?>
                <button class="year-tab <?php echo $yi === 0 ? 'active' : ''; ?>" data-year="<?php echo $yr; ?>">
                    <?php echo $year_labels[$yr] ?? "Year $yr"; ?>
                </button>
                <?php $yi++; endforeach; ?>
            </div>
            <div class="curric-sem-tabs">
                <?php
                $first_yr = array_key_first($curriculum);
                foreach ($curriculum as $yr => $sems): foreach ($sems as $sem => $subjects): ?>
                <button class="sem-tab <?php echo ($yr == $first_yr && array_key_first($curriculum[$yr]) == $sem) ? 'active' : ''; ?>"
                        data-year="<?php echo $yr; ?>"
                        data-sem="<?php echo $sem; ?>"
                        data-target="apanel_<?php echo "{$yr}_{$sem}"; ?>"
                        style="<?php echo $yr == $first_yr ? '' : 'display:none;'; ?>">
                    <?php echo $sem_labels[$sem] ?? $sem; ?>
                </button>
                <?php endforeach; endforeach; ?>
            </div>
        </div>

        <!-- Panels -->
        <?php $pi = 0; foreach ($curriculum as $yr => $sems): foreach ($sems as $sem => $subjects): ?>
        <div class="curric-panel <?php echo $pi === 0 ? 'active' : ''; ?>" id="apanel_<?php echo "{$yr}_{$sem}"; ?>">

            <div class="admin-curric-table-wrapper">
                <div class="admin-curric-table">

                    <div class="admin-curric-table-header">
                        <div>Subject Code</div>
                        <div class="admin-curric-col-left">Subject Name</div>
                        <div>Units</div>
                        <div class="admin-curric-col-left">Prerequisite</div>
                        <div>Grade</div>
                        <div>Status</div>
                        <div class="admin-curric-col-left">Class / Schedule</div>
                        <div>Action</div>
                    </div>

                    <div class="admin-curric-table-body">
                    <?php foreach ($subjects as $subj):
                        $grade_info   = $grades[$subj['subject_id']] ?? null;
                        $enroll_info  = $active_enrollments[$subj['subject_id']] ?? null;
                        $classes_list = $available_classes[$subj['subject_id']] ?? [];
                        $grade_val    = $grade_info['grade'] ?? null;
                        $passed       = $grade_val && $grade_val !== '5.00' && strtoupper($grade_val) !== 'INC' && strtoupper($grade_val) !== 'DRP';
                        $failed       = $grade_val && ($grade_val === '5.00' || strtoupper($grade_val) === 'INC');

                        if ($enroll_info)    $row_class = 'row-enrolled';
                        elseif ($passed)     $row_class = 'row-passed';
                        elseif ($failed)     $row_class = 'row-failed';
                        else                 $row_class = '';

                        $status_map = [
                            'reserved'      => ['Pending',        '#f59e0b'],
                            'confirmed'     => ['Confirmed',      '#16a34a'],
                            'ongoing'       => ['Not Submitted',  '#2563eb'],
                            'drop_requested'=> ['Drop Requested', '#dc2626'],
                        ];
                    ?>
                    <div class="admin-curric-row <?php echo $row_class; ?>">
                        <div><strong><?php echo htmlspecialchars($subj['subject_code']); ?></strong></div>
                        <div class="admin-curric-col-left"><?php echo htmlspecialchars($subj['subject_name']); ?></div>
                        <div><?php echo $subj['units']; ?></div>
                        <div class="admin-curric-col-left" style="font-size:.78rem;color:var(--text);"><?php echo htmlspecialchars($subj['prerequisite'] ?: '—'); ?></div>
                        <div>
                            <?php if ($grade_val): ?>
                                <span class="grade-badge <?php echo $passed ? 'grade-pass' : 'grade-fail'; ?>"><?php echo htmlspecialchars($grade_val); ?></span>
                            <?php else: ?>
                                <span style="color:var(--text);">—</span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php if ($enroll_info):
                                [$elabel, $ecolor] = $status_map[$enroll_info['status']] ?? [ucfirst($enroll_info['status']), '#888'];
                            ?>
                                <span class="enroll-status-badge" style="background:<?php echo $ecolor; ?>1a;color:<?php echo $ecolor; ?>;padding:.2rem .6rem;border-radius:4px;"><?php echo $elabel; ?></span>
                            <?php elseif ($passed): ?>
                                <span class="enroll-status-badge" style="background:#16a34a1a;color:#16a34a;padding:.2rem .6rem;border-radius:4px;">Passed</span>
                            <?php elseif ($failed): ?>
                                <span class="enroll-status-badge" style="background:#dc26261a;color:#dc2626;padding:.2rem .6rem;border-radius:4px;">Failed</span>
                            <?php else: ?>
                                <span style="font-size:.78rem;color:var(--text);">Not Taken</span>
                            <?php endif; ?>
                        </div>
                        <div class="admin-curric-col-left">
                            <?php if ($enroll_info): ?>
                                <span style="font-size:.82rem;color:var(--text);">
                                    <?php echo htmlspecialchars($enroll_info['section'] . ' · ' . $enroll_info['schedule_day'] . ' ' . $enroll_info['schedule_time']); ?>
                                </span>
                            <?php elseif (!$passed && !empty($classes_list)): ?>
                                <select class="class-dropdown" data-subject-id="<?php echo $subj['subject_id']; ?>">
                                    <option value="">— Select class —</option>
                                    <?php foreach ($classes_list as $cls):
                                        $slots_left = $cls['max_slots'] - $cls['enrolled_count'];
                                    ?>
                                    <option value="<?php echo $cls['class_id']; ?>" <?php echo $slots_left <= 0 ? 'disabled' : ''; ?>>
                                        <?php echo htmlspecialchars(
                                            $cls['section'] . ' | ' .
                                            $cls['schedule_day'] . ' ' . $cls['schedule_time'] .
                                            ($cls['room'] ? ' | ' . $cls['room'] : '') .
                                            ' | ' . ($cls['faculty_name'] ?? 'TBA') .
                                            ' (' . $slots_left . ' slots)'
                                        ); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php elseif (!$passed): ?>
                                <span style="font-size:.78rem;color:var(--text);">No class available</span>
                            <?php else: ?>
                                <span style="font-size:.78rem;color:var(--text);">—</span>
                            <?php endif; ?>
                        </div>
                        <div class="center-btn">
                            <?php if ($enroll_info && in_array($enroll_info['status'], ['reserved','confirmed'])): ?>
                                <form method="POST" action="../../php/drop_enrollment.php" style="display:inline;">
                                    <input type="hidden" name="student_id"    value="<?php echo $student_id; ?>">
                                    <input type="hidden" name="enrollment_id" value="<?php echo $enroll_info['enrollment_id']; ?>">
                                    <input type="hidden" name="class_id"      value="<?php echo $enroll_info['class_id']; ?>">
                                    <button type="submit" class="btn-icon cancel" title="Drop"
                                            onclick="return confirm('Drop this subject?')">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            <?php elseif (!$enroll_info && !$passed && !empty($classes_list)): ?>
                                <button class="btn-reserve-row"
                                        data-subject-id="<?php echo $subj['subject_id']; ?>"
                                        data-student-id="<?php echo $student_id; ?>">
                                    <i class="fa-solid fa-bookmark"></i> Reserve
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    </div>

                </div>
            </div>
        </div>
        <?php $pi++; endforeach; endforeach; ?>

        <?php endif; ?>
    </div>

    <!-- IRREGULAR STUDENT: ADD SUBJECT -->
    <?php if ($is_irregular): ?>
    <div class="card" style="border-left:4px solid #f97416;">
        <div class="card-header" style="background:#f97416;">
            <h2><i class="fa-solid fa-triangle-exclamation"></i> Irregular Student — Add Subject</h2>
        </div>
        <div style="padding:.75rem 1.5rem;background:#f974161a;font-size:.85rem;color:#c2410c;border-bottom:1px solid #fed7aa;">
            <i class="fa-solid fa-circle-info"></i>
            Student is <strong>Irregular</strong>. Subjects locked by a failed prerequisite cannot be reserved until the prerequisite is retaken and passed.
        </div>
        <div style="padding:.75rem 1.5rem;display:flex;align-items:center;justify-content:flex-end;gap:.75rem;">
            <label style="font-size:.8rem;font-weight:500;">Search:</label>
            <div class="search-bar" style="border:1px solid var(--off);">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="irregularSearch" placeholder="Subject code or name…" style="width:200px;">
            </div>
        </div>

        <div class="admin-irreg-table-wrapper">
            <div class="admin-irreg-table" id="irregularTable">

                <div class="admin-irreg-table-header">
                    <div>Subject Code</div>
                    <div class="admin-irreg-col-left">Subject Name</div>
                    <div>Units</div>
                    <div>Yr</div>
                    <div>Sem</div>
                    <div class="admin-irreg-col-left">Prerequisite</div>
                    <div>Grade</div>
                    <div class="admin-irreg-col-left">Class / Schedule</div>
                    <div>Action</div>
                </div>

                <div class="admin-irreg-table-body">
                <?php foreach ($irregular_classes as $cls):
                    $g        = $grades[$cls['subject_id']] ?? null;
                    $gv       = $g['grade'] ?? null;
                    $failed_s = $gv && ($gv === '5.00' || strtoupper($gv) === 'INC');
                    $lock     = is_prereq_locked_admin($cls['prerequisite'], $failed_codes);
                    $slots    = $cls['max_slots'] - $cls['enrolled_count'];
                    $row_class = $lock['locked'] ? 'row-locked' : ($failed_s ? 'row-failed' : '');
                ?>
                <div class="admin-irreg-row <?php echo $row_class; ?>">
                    <div><strong><?php echo htmlspecialchars($cls['subject_code']); ?></strong></div>
                    <div class="admin-irreg-col-left"><?php echo htmlspecialchars($cls['subject_name']); ?></div>
                    <div><?php echo $cls['units']; ?></div>
                    <div><?php echo $cls['year_level'] ?? '—'; ?></div>
                    <div style="font-size:.78rem;"><?php echo $sem_labels[$cls['subj_sem'] ?? ''] ?? ($cls['subj_sem'] ?? '—'); ?></div>
                    <div class="admin-irreg-col-left" style="font-size:.78rem;color:var(--text);"><?php echo htmlspecialchars($cls['prerequisite'] ?: '—'); ?></div>
                    <div>
                        <?php if ($gv): ?>
                            <span class="grade-badge <?php echo $failed_s ? 'grade-fail' : 'grade-pass'; ?>"><?php echo htmlspecialchars($gv); ?></span>
                        <?php else: ?>
                            <span style="color:var(--text);">—</span>
                        <?php endif; ?>
                    </div>
                    <div class="admin-irreg-col-left">
                        <?php if (!$lock['locked'] && $slots > 0): ?>
                            <select class="class-dropdown" data-subject-id="<?php echo $cls['subject_id']; ?>">
                                <option value="">— Select class —</option>
                                <option value="<?php echo $cls['class_id']; ?>">
                                    <?php echo htmlspecialchars($cls['section'] . ' | ' . $cls['schedule_day'] . ' ' . $cls['schedule_time'] . ($cls['room'] ? ' | ' . $cls['room'] : '') . ' | ' . ($cls['faculty_name'] ?? 'TBA') . ' (' . $slots . ' slots)'); ?>
                                </option>
                            </select>
                        <?php elseif ($lock['locked']): ?>
                            <span class="lock-reason"><i class="fa-solid fa-lock"></i> <?php echo htmlspecialchars($lock['reason']); ?></span>
                        <?php else: ?>
                            <span style="font-size:.78rem;color:#dc2626;">Class Full</span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php if (!$lock['locked'] && $slots > 0): ?>
                            <button class="btn-reserve-row" data-subject-id="<?php echo $cls['subject_id']; ?>">
                                <i class="fa-solid fa-bookmark"></i> Reserve
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if (empty($irregular_classes)): ?>
                    <div class="admin-irreg-empty">No additional subjects available.</div>
                <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
    <?php endif; ?>

</div>
</main>
</div>

<!-- Hidden form for reserving -->
<form id="reserveForm" method="POST" action="../../php/manual_enroll.php" style="display:none;">
    <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
    <input type="hidden" name="class_id" id="reserveClassId">
</form>

<script src="../../js/admin/admin_main.js"></script>
<script src="../../js/admin/admin_manual_enroll.js"></script>
</body>
</html>
