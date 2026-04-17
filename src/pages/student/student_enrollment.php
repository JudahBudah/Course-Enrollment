<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

require_once '../../php/connection.php';
require_once '../../php/functions.php';

if (!isset($_SESSION['student_number'])) {
    header('Location: ../../php/student_logout.php');
    exit();
}

// Fetch student info via student_number (matches session)
$stmt = mysqli_prepare($con, "SELECT s.*, b.block_name, b.school_year, b.semester FROM students s LEFT JOIN blocks b ON s.block_id = b.block_id WHERE s.student_number = ?");
mysqli_stmt_bind_param($stmt, 's', $_SESSION['student_number']);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$student) {
    header('Location: ../../php/student_logout.php');
    exit();
}

$student_id     = $student['student_id'];
$student_course = $student['course'] ?? '';
$block_semester = $student['semester'] ?? null; // '1st', '2nd', 'summer' — from joined blocks table

// Get course_id — match against both course_name and course_code
$course_id = null;
if (!empty($student_course)) {
    $stmt = mysqli_prepare($con, "SELECT course_id FROM courses WHERE course_name = ? OR course_code = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ss", $student_course, $student_course);
    mysqli_stmt_execute($stmt);
    $course_row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if ($course_row) $course_id = $course_row['course_id'];
}

// Get all curriculum subjects for this course grouped by year_level and semester
$curriculum = [];
if ($course_id) {
    $stmt = mysqli_prepare($con, "SELECT subject_id, subject_code, subject_name, units, lecture_hours, lab_hours, COALESCE(year_level, '0') as year_level, COALESCE(semester, 'N/A') as semester, prerequisite FROM subjects WHERE course_id = ? AND status = 'active' ORDER BY CAST(year_level AS UNSIGNED), FIELD(semester,'1st','2nd','summer'), subject_code");
    mysqli_stmt_bind_param($stmt, "i", $course_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $curriculum[$row['year_level']][$row['semester']][] = $row;
    }
}

// Get student's grades keyed by subject_id
$grades = [];
$stmt = mysqli_prepare($con, "SELECT subject_id, grade, status FROM grades WHERE student_id = ? ORDER BY grade_id DESC");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    if (!isset($grades[$row['subject_id']])) $grades[$row['subject_id']] = $row;
}

// Build set of failed subject codes (used to lock dependent subjects)
$failed_codes = [];
foreach ($grades as $g) {
    if ($g['grade'] === '5.00' || strtoupper($g['grade']) === 'INC') {
        // Get subject code for this subject_id
        $sc = mysqli_fetch_assoc(mysqli_query($con, "SELECT subject_code FROM subjects WHERE subject_id = {$g['subject_id']}"));
        if ($sc) $failed_codes[] = $sc['subject_code'];
    }
}

// Auto-detect and update irregular status if student has any failed subject
$is_irregular = !empty($failed_codes) || ($student['registration_status'] ?? 'Regular') === 'Irregular';
if (!empty($failed_codes) && ($student['registration_status'] ?? 'Regular') !== 'Irregular') {
    mysqli_query($con, "UPDATE students SET registration_status = 'Irregular' WHERE student_id = $student_id");
    $student['registration_status'] = 'Irregular';
}

// Get currently active enrollments (reserved, confirmed, ongoing) keyed by subject_id
$active_enrollments = [];
$stmt = mysqli_prepare($con, "SELECT e.enrollment_id, e.status, e.class_id, c.subject_id FROM enrollments e JOIN classes c ON e.class_id = c.class_id WHERE e.student_id = ? AND e.status IN ('reserved','confirmed','ongoing','drop_requested')");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $active_enrollments[$row['subject_id']] = $row;
}

// Get available classes per subject for the modal (keyed by subject_id)
// Only show classes matching the student's current block semester
$available_classes = [];
if ($course_id) {
    $sem_filter = $block_semester ? "AND c.semester = '" . mysqli_real_escape_string($con, $block_semester) . "'" : '';
    $stmt = mysqli_prepare($con, "
        SELECT c.class_id, c.subject_id, c.section, c.schedule_day, c.schedule_time, c.room, c.max_slots, c.enrolled_count,
               CONCAT(f.first_name, ' ', f.last_name) as faculty_name
        FROM classes c
        JOIN subjects s ON c.subject_id = s.subject_id
        LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
        WHERE c.status = 'open' AND s.course_id = ? $sem_filter
        ORDER BY c.subject_id, c.section
    ");
    mysqli_stmt_bind_param($stmt, "i", $course_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $available_classes[$row['subject_id']][] = $row;
    }
}

// Fetch current enrolled subjects — exclude completed (grade given)
$stmt = mysqli_prepare($con, "
    SELECT e.enrollment_id, e.status, s.subject_code, s.subject_name, s.lecture_hours, s.lab_hours, s.units,
           c.class_id, c.schedule_day, c.schedule_time, c.room, c.section,
           CONCAT(f.first_name, ' ', f.last_name) as faculty_name
    FROM enrollments e
    JOIN classes c ON e.class_id = c.class_id
    JOIN subjects s ON c.subject_id = s.subject_id
    LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
    WHERE e.student_id = ? AND e.status IN ('reserved','confirmed','ongoing','drop_requested')
    ORDER BY s.subject_code
");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$enrolled_subjects = mysqli_stmt_get_result($stmt);

// Helper: check if a subject is locked due to failed prerequisites
function is_prereq_locked($prereq_str, $failed_codes) {
    if (empty($prereq_str)) return ['locked' => false, 'reason' => ''];
    $codes = array_map('trim', explode(',', $prereq_str));
    foreach ($codes as $code) {
        if (in_array($code, $failed_codes)) {
            return ['locked' => true, 'reason' => "Prerequisite $code was failed"];
        }
    }
    return ['locked' => false, 'reason' => ''];
}

// For irregular students: get all open classes NOT already in their curriculum
// so they can add subjects from other year levels or retake failed ones
$irregular_classes = [];
if ($is_irregular && $course_id) {
    $stmt = mysqli_prepare($con, "
        SELECT c.class_id, c.subject_id, c.section, c.schedule_day, c.schedule_time, c.room,
               c.max_slots, c.enrolled_count, c.semester, c.school_year,
               s.subject_code, s.subject_name, s.units, s.lecture_hours, s.lab_hours,
               s.year_level, s.semester as subj_sem, s.prerequisite,
               CONCAT(f.first_name, ' ', f.last_name) as faculty_name
        FROM classes c
        JOIN subjects s ON c.subject_id = s.subject_id
        LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
        WHERE c.status = 'open'
        AND s.course_id = ?
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
        $grade_info = $grades[$row['subject_id']] ?? null;
        $grade_val  = $grade_info['grade'] ?? null;
        $passed     = $grade_val && $grade_val !== '5.00' && strtoupper($grade_val) !== 'INC';
        if (!$passed) $irregular_classes[] = $row;
    }
}

// Subjects needed for retake: failed grades with available open classes
$retake_subjects = [];
if (!empty($failed_codes) && $course_id) {
    $stmt = mysqli_prepare($con, "
        SELECT DISTINCT s.subject_id, s.subject_code, s.subject_name, s.units,
               s.lecture_hours, s.lab_hours, g.grade,
               c.class_id, c.section, c.schedule_day, c.schedule_time, c.room,
               CONCAT(f.first_name, ' ', f.last_name) as faculty_name
        FROM grades g
        JOIN subjects s ON g.subject_id = s.subject_id
        JOIN classes c ON c.subject_id = s.subject_id AND c.status = 'open'
        LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
        WHERE g.student_id = ?
          AND (g.grade = '5.00' OR UPPER(g.grade) = 'INC')
          AND s.course_id = ?
          AND c.subject_id NOT IN (
              SELECT cc.subject_id FROM enrollments e
              JOIN classes cc ON e.class_id = cc.class_id
              WHERE e.student_id = ? AND e.status IN ('reserved','confirmed','ongoing','drop_requested')
          )
        ORDER BY s.subject_code
    ");
    mysqli_stmt_bind_param($stmt, "iii", $student_id, $course_id, $student_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) $retake_subjects[] = $row;
    mysqli_stmt_close($stmt);
}

$year_labels = ['1' => '1st Year', '2' => '2nd Year', '3' => '3rd Year', '4' => '4th Year', '0' => 'Unassigned'];
$sem_labels  = ['1st' => '1st Semester', '2nd' => '2nd Semester', 'summer' => 'Summer', 'N/A' => 'Unassigned'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollment</title>
    <link rel="icon" href="../../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/student/student_enrollment.css">
    <link rel="stylesheet" href="../../css/student/student_main.css">
</head>
<body>
<header>
    <div class="nav-section">
        <button class="nav-button" id="navButton">
            <i class="fa-solid fa-bars trans-bars" id="trans-bars"></i>
        </button>
        <div class="logo-container">
            <img src="../../assets/plm-logo.png" alt="PLM Logo" loading="lazy">
            <div class="title-container">
                <div class="logo-title">PAMANTASAN NG LUNGSOD NG MAYNILA</div>
                <div class="logo-sub">University of the City of Manila</div>
            </div>
        </div>
        <div class="acc-display-container">
            <div class="acc-name"><?php echo htmlspecialchars(trim($student['first_name'] . ' ' . ($student['middle_name'] ?? '') . ' ' . $student['last_name'])); ?></div>
            <div class="acc-img">
                <img src="<?php echo $student['profile_photo'] ? '../../' . $student['profile_photo'] : '../../assets/test/student-profile.webp'; ?>">
            </div>
        </div>
    </div>
    <nav class="main-nav" id="navMenu">
        <div class="nav-wrapper">
            <ul class="main-ul">
                <li><a href="student_home.php"><i class="fa-solid fa-house"></i><div class="li-name">Dashboard</div></a></li>
                <li><a href="student_subjects.php"><i class="fa-solid fa-calendar"></i><div class="li-name">Schedule</div></a></li>
                <li><a href="student_enrollment.php" class="active"><i class="fa-solid fa-id-card"></i><div class="li-name">Enrollment</div></a></li>
                <li><a href="student_grades.php"><i class="fa-solid fa-book"></i><div class="li-name">Grades</div></a></li>
                <li class="course-dropdown">
                    <a href="#" id="acad-dropdown">
                        <i class="fa-solid fa-school"></i>
                        <div class="li-name chev-space">Academics <i class="fa-solid fa-chevron-down"></i></div>
                    </a>
                    <div class="acad-dropdown-menu" id="acad-dropdown-menu">
                        <ul>
                            <li><a href="student_info-program.php">Program</a></li>
                            <li><a href="student_info-college.php">College</a></li>
                        </ul>
                    </div>
                </li>
                <li><a href="student_account.php"><i class="fa-solid fa-user"></i><div class="li-name">Profile</div></a></li>
                <li><a href="../../php/student_logout.php" class="logout-bg"><i class="fa-solid fa-arrow-right-from-bracket"></i><div class="li-name">Logout</div></a></li>
            </ul>
        </div>
        <div class="drk-mode-container">
            <div class="drk-label">
                <i class="fa-solid fa-moon" id="modeIcon"></i>
                <span class="li-name" id="modeLabel">Dark Mode</span>
            </div>
            <div class="toggle-track li-name" id="toggleTrack"><div class="toggle-thumb"></div></div>
        </div>
    </nav>
</header>

<div class="main-flex">
<div class="spacer"></div>
<main>

    <!-- FLASH MESSAGES -->
    <?php if (isset($_GET['success'])): ?>
        <div class="flash flash-success">
            <i class="fa-solid fa-check-circle"></i>
            <?php
            $msgs = [
                'enrolled'       => 'Successfully enrolled in subject.',
                'confirmed'      => 'Enrollment confirmed.',
                'drop_requested' => 'Drop request submitted. Awaiting admin approval.',
                'drop_cancelled' => 'Drop request cancelled.',
                'dropped'        => 'Reservation cancelled.',
                'removed'        => 'Subject removed from enrollment.',
                'submitted'      => 'Enrollment submitted for admin approval.',
            ];
            echo $msgs[$_GET['success']] ?? 'Action completed.';
            ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="flash flash-error">
            <i class="fa-solid fa-exclamation-triangle"></i>
            <?php
            $errs = [
                'invalid'          => 'Invalid request.',
                'full'             => 'Class is full.',
                'already_enrolled' => 'Already enrolled in this subject.',
                'wrong_semester'   => 'This subject is not available for your current semester.',
                'prereq'           => 'Missing prerequisites: ' . htmlspecialchars(urldecode($_GET['missing'] ?? '')),
                'no_class'         => 'No class selected.',
            ];
            echo $errs[$_GET['error']] ?? 'An error occurred.';
            ?>
        </div>
    <?php endif; ?>

    <!-- STUDENT INFO -->
    <div class="card">
        <div class="card-header"><h2>Student Information</h2></div>
        <div class="student-body">
            <div class="avatar-wrap">
                <img src="<?php echo $student['profile_photo'] ? '../../' . $student['profile_photo'] : '../../assets/test/student-profile.webp'; ?>">
            </div>
            <div class="student-details">
                <div class="detail-item"><label>Full Name</label><span><?php echo htmlspecialchars($student['first_name'] . ' ' . ($student['middle_name'] ?? '') . ' ' . $student['last_name']); ?></span></div>
                <div class="detail-item"><label>Student Number</label><span><?php echo htmlspecialchars($student['student_number']); ?></span></div>
                <div class="detail-item"><label>Program</label><span><?php echo htmlspecialchars($student['course']); ?></span></div>
                <div class="detail-item"><label>Year Level</label><span><?php echo $year_labels[$student['year_level']] ?? 'N/A'; ?></span></div>
                <div class="detail-item"><label>Status</label><span><?php echo htmlspecialchars(!empty($student['registration_status']) ? $student['registration_status'] : 'Regular'); ?></span></div>
                <div class="detail-item"><label>School Year</label><span><?php echo htmlspecialchars($student['school_year'] ?? 'N/A'); ?></span></div>
            </div>
        </div>
    </div>

    <!-- CURRENT ENROLLMENTS -->
    <div class="card">
        <div class="table-section-head">Current Enrollments</div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th class="center">Units</th>
                        <th>Schedule</th>
                        <th>Professor</th>
                        <th>Status</th>
                        <th class="center">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $has_enrolled = false;
                while ($row = mysqli_fetch_assoc($enrolled_subjects)):
                    $has_enrolled = true;
                    $status_map = [
                        'reserved'      => ['label' => 'Pending',       'color' => '#f59e0b'],
                        'confirmed'     => ['label' => 'Confirmed',      'color' => '#16a34a'],
                        'ongoing'       => ['label' => 'Not Submitted',  'color' => '#2563eb'],
                        'drop_requested'=> ['label' => 'Drop Requested', 'color' => '#dc2626'],
                    ];
                    $s = $status_map[$row['status']] ?? ['label' => ucfirst($row['status']), 'color' => '#888'];
                ?>
                <tr>
                    <td><span class="subj-code"><?php echo htmlspecialchars($row['subject_code']); ?></span></td>
                    <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                    <td class="center"><?php echo $row['units']; ?></td>
                    <td><div class="sched-cell"><span class="sched-tag"><?php echo htmlspecialchars($row['section'] . ' · ' . $row['schedule_day'] . ' ' . $row['schedule_time'] . ($row['room'] ? ' · ' . $row['room'] : '')); ?></span></div></td>
                    <td class="faculty-name"><?php echo htmlspecialchars($row['faculty_name'] ?? 'TBA'); ?></td>
                    <td><span class="status-badge" style="background:<?php echo $s['color']; ?>1a;color:<?php echo $s['color']; ?>;"><?php echo $s['label']; ?></span></td>
                    <td class="center">
                        <?php if ($row['status'] === 'ongoing'): ?>
                            <form method="POST" action="../../php/student_enrollment_action.php" style="display:inline;">
                                <input type="hidden" name="action" value="cancel_self_enroll">
                                <input type="hidden" name="enrollment_id" value="<?php echo $row['enrollment_id']; ?>">
                                <button type="submit" class="action-btn cancel-btn" onclick="return confirm('Remove this subject?')"><i class="fa-solid fa-xmark"></i></button>
                            </form>
                        <?php elseif ($row['status'] === 'confirmed'): ?>
                            <form method="POST" action="../../php/student_enrollment_action.php" style="display:inline;">
                                <input type="hidden" name="action" value="drop">
                                <input type="hidden" name="enrollment_id" value="<?php echo $row['enrollment_id']; ?>">
                                <button type="submit" class="action-btn drop-btn" onclick="return confirm('Request to drop this subject?')"><i class="fa-solid fa-right-from-bracket"></i> Drop</button>
                            </form>
                        <?php elseif ($row['status'] === 'reserved'): ?>
                            <form method="POST" action="../../php/student_enrollment_action.php" style="display:inline;">
                                <input type="hidden" name="action" value="cancel_reserved">
                                <input type="hidden" name="enrollment_id" value="<?php echo $row['enrollment_id']; ?>">
                                <button type="submit" class="action-btn cancel-btn" onclick="return confirm('Cancel this reservation?')"><i class="fa-solid fa-xmark"></i> Cancel</button>
                            </form>
                        <?php elseif ($row['status'] === 'drop_requested'): ?>
                            <form method="POST" action="../../php/student_enrollment_action.php" style="display:inline;">
                                <input type="hidden" name="action" value="cancel_drop_request">
                                <input type="hidden" name="enrollment_id" value="<?php echo $row['enrollment_id']; ?>">
                                <button type="submit" class="action-btn cancel-btn" onclick="return confirm('Cancel drop request?')"><i class="fa-solid fa-rotate-left"></i> Undo</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if (!$has_enrolled): ?>
                <tr><td colspan="7" class="center" style="padding:2rem;color:var(--text-label);">No current enrollments.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
        // Check if there are ongoing (not yet submitted) enrollments
        $ongoing_count = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM enrollments WHERE student_id = $student_id AND status = 'ongoing'"))['c'];
        if ($ongoing_count > 0):
        ?>
        <div class="submit-bar">
            <span><i class="fa-solid fa-circle-info"></i> You have <?php echo $ongoing_count; ?> subject(s) pending submission.</span>
            <form method="POST" action="../../php/student_enrollment_action.php">
                <input type="hidden" name="action" value="submit_enrollment">
                <button type="submit" class="btn-submit-enroll" onclick="return confirm('Submit all pending subjects for admin approval?')">
                    <i class="fa-solid fa-paper-plane"></i> Submit Enrollment
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <!-- CURRICULUM VIEW -->
    <div class="card">
        <div class="card-header">
            <h2>Curriculum — <?php echo htmlspecialchars($student_course); ?></h2>
            <div class="search-bar" style="margin-left:auto;">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="curricSearch" placeholder="Search subject…" style="width:200px;">
            </div>
        </div>

        <?php if (empty($curriculum)): ?>
            <div style="padding:2rem;text-align:center;color:var(--text-label);">
                <i class="fa-solid fa-circle-exclamation" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
                No curriculum found for your program. Please contact the registrar.
            </div>
        <?php else: ?>

        <!-- Year tabs -->
        <div class="curric-nav">
            <div class="curric-year-tabs">
                <?php $yi = 0; foreach ($curriculum as $yr => $sems): ?>
                <button class="year-tab <?php echo $yi === 0 ? 'active' : ''; ?>"
                        data-year="<?php echo $yr; ?>">
                    <?php echo $year_labels[$yr] ?? "Year $yr"; ?>
                </button>
                <?php $yi++; endforeach; ?>
            </div>
            <div class="curric-sem-tabs">
                <?php
                $first_yr = array_key_first($curriculum);
                foreach ($curriculum as $yr => $sems):
                    foreach ($sems as $sem => $subjects):
                ?>
                <button class="sem-tab <?php echo ($yr == $first_yr && array_key_first($curriculum[$yr]) == $sem) ? 'active' : ''; ?>"
                        data-year="<?php echo $yr; ?>"
                        data-sem="<?php echo $sem; ?>"
                        data-target="panel_<?php echo "{$yr}_{$sem}"; ?>"
                        style="<?php echo $yr == $first_yr ? '' : 'display:none;'; ?>">
                    <?php echo $sem_labels[$sem] ?? $sem; ?>
                </button>
                <?php endforeach; endforeach; ?>
            </div>
        </div>

        <!-- Curriculum Panels -->
        <?php
        $pi = 0;
        foreach ($curriculum as $yr => $semesters):
            foreach ($semesters as $sem => $subjects):
        ?>
        <div class="curric-panel <?php echo $pi === 0 ? 'active' : ''; ?>" id="panel_<?php echo "{$yr}_{$sem}"; ?>">
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th class="center">Units</th>
                            <th class="center">Hrs</th>
                            <th>Prerequisite</th>
                            <th class="center">Grade</th>
                            <th class="center">Status</th>
                            <th class="center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($subjects as $subj):
                        $grade_info   = $grades[$subj['subject_id']] ?? null;
                        $enroll_info  = $active_enrollments[$subj['subject_id']] ?? null;
                        $classes_list = $available_classes[$subj['subject_id']] ?? [];
                        $grade_val    = $grade_info['grade'] ?? null;
                        $passed       = $grade_val && $grade_val !== '5.00' && strtoupper($grade_val) !== 'INC' && strtoupper($grade_val) !== 'DRP';
                        $failed       = $grade_val && ($grade_val === '5.00' || strtoupper($grade_val) === 'INC');
                        $hours        = $subj['lecture_hours'] + $subj['lab_hours'];
                        $lock         = is_prereq_locked($subj['prerequisite'], $failed_codes);
                        if ($enroll_info)       $row_class = 'row-enrolled';
                        elseif ($passed)        $row_class = 'row-passed';
                        elseif ($failed)        $row_class = 'row-failed';
                        elseif ($lock['locked'])$row_class = 'row-locked';
                        else                    $row_class = '';
                    ?>
                    <tr class="<?php echo $row_class; ?>">
                        <td><span class="subj-code"><?php echo htmlspecialchars($subj['subject_code']); ?></span></td>
                        <td><?php echo htmlspecialchars($subj['subject_name']); ?></td>
                        <td class="center"><?php echo $subj['units']; ?></td>
                        <td class="center"><?php echo $hours; ?></td>
                        <td class="prereq-cell"><?php echo htmlspecialchars($subj['prerequisite'] ?: '—'); ?></td>
                        <td class="center">
                            <?php if ($grade_val): ?>
                                <span class="grade-badge <?php echo $passed ? 'grade-pass' : 'grade-fail'; ?>"><?php echo htmlspecialchars($grade_val); ?></span>
                            <?php else: ?>
                                <span style="color:var(--text-label);">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="center">
                            <?php
                            $es_map = ['reserved'=>['Pending','#f59e0b'],'confirmed'=>['Confirmed','#16a34a'],'ongoing'=>['Not Submitted','#2563eb'],'drop_requested'=>['Drop Requested','#dc2626']];
                            if ($enroll_info): [$elabel,$ecolor] = $es_map[$enroll_info['status']] ?? [ucfirst($enroll_info['status']),'#888']; ?>
                                <span class="status-badge" style="background:<?php echo $ecolor; ?>1a;color:<?php echo $ecolor; ?>;"><?php echo $elabel; ?></span>
                            <?php elseif ($passed): ?>
                                <span class="status-badge" style="background:#16a34a1a;color:#16a34a;">Passed</span>
                            <?php elseif ($failed): ?>
                                <span class="status-badge" style="background:#dc26261a;color:#dc2626;">Failed</span>
                            <?php elseif ($lock['locked']): ?>
                                <span class="status-badge" style="background:#f974161a;color:#f97416;">Locked</span>
                            <?php else: ?>
                                <span class="status-badge" style="background:#6b72801a;color:#6b7280;">Not Taken</span>
                            <?php endif; ?>
                        </td>
                        <td class="center">
                            <?php if ($enroll_info): ?>
                                <span style="font-size:.8rem;color:var(--text-label);">Enrolled</span>
                            <?php elseif ($passed): ?>
                                <span style="font-size:.8rem;color:var(--text-label);">—</span>
                            <?php elseif ($lock['locked']): ?>
                                <span class="lock-reason"><i class="fa-solid fa-lock"></i> <?php echo htmlspecialchars($lock['reason']); ?></span>
                            <?php elseif (!empty($classes_list)): ?>
                                <button class="btn-select-sched"
                                        data-subject-id="<?php echo $subj['subject_id']; ?>"
                                        data-subject-code="<?php echo htmlspecialchars($subj['subject_code']); ?>"
                                        data-subject-name="<?php echo htmlspecialchars($subj['subject_name']); ?>"
                                        data-classes="<?php echo htmlspecialchars(json_encode($classes_list)); ?>">
                                    <i class="fa-solid fa-calendar-plus"></i> Select Schedule
                                </button>
                            <?php else: ?>
                                <span style="font-size:.8rem;color:var(--text-label);">No class available</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php $pi++; endforeach; endforeach; ?>

        <?php endif; ?>
    </div>

    <!-- SUBJECTS FOR RETAKE -->
    <?php if (!empty($retake_subjects)): ?>
    <div class="card" style="border-left:4px solid #dc2626;">
        <div class="card-header" style="background:#dc2626;">
            <h2><i class="fa-solid fa-rotate-right"></i> Subjects Needed for Retake</h2>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th class="center">Units</th>
                        <th>Section</th>
                        <th>Schedule</th>
                        <th>Professor</th>
                        <th>Grade</th>
                        <th class="center">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($retake_subjects as $rt): ?>
                <tr>
                    <td><span class="subj-code"><?php echo htmlspecialchars($rt['subject_code']); ?></span></td>
                    <td><?php echo htmlspecialchars($rt['subject_name']); ?></td>
                    <td class="center"><?php echo $rt['units']; ?></td>
                    <td><?php echo htmlspecialchars($rt['section'] ?? 'TBA'); ?></td>
                    <td><?php echo htmlspecialchars(($rt['schedule_day'] ?? '') . ' ' . ($rt['schedule_time'] ?? '')); ?></td>
                    <td class="faculty-name"><?php echo htmlspecialchars($rt['faculty_name'] ?? 'TBA'); ?></td>
                    <td><span class="status-badge" style="background:#dc26261a;color:#dc2626;"><?php echo htmlspecialchars($rt['grade']); ?></span></td>
                    <td class="center">
                        <form method="POST" action="../../php/student_enrollment_action.php">
                            <input type="hidden" name="action" value="self_enroll">
                            <input type="hidden" name="class_id" value="<?php echo $rt['class_id']; ?>">
                            <button type="submit" class="action-btn" style="background:#dc2626;color:#fff;border:none;padding:.4rem .8rem;border-radius:6px;cursor:pointer;font-size:.82rem;"
                                onclick="return confirm('Enroll in this retake class?')">
                                <i class="fa-solid fa-rotate-right"></i> Enroll
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- IRREGULAR STUDENT: ADD SUBJECT -->
    <?php if ($is_irregular): ?>
    <div class="card" style="border-left:4px solid #f97416;">
        <div class="card-header" style="background:#f97416;">
            <h2><i class="fa-solid fa-triangle-exclamation"></i> Irregular Student — Add Subject</h2>
        </div>
        <div style="padding:.75rem 1.5rem;background:#f974161a;font-size:.85rem;color:#c2410c;border-bottom:1px solid #fed7aa;">
            <i class="fa-solid fa-circle-info"></i>
            You are marked as <strong>Irregular</strong> due to failed or incomplete subjects.
            Subjects with a <i class="fa-solid fa-lock" style="font-size:.75rem;"></i> are locked until the failed prerequisite is retaken and passed.
        </div>
        <div class="search-bar-wrap">
            <label>Search available subjects:</label>
            <div class="search-bar">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="irregularSearch" placeholder="Subject code or name…">
            </div>
        </div>
        <div class="table-wrapper">
            <table id="irregularTable">
                <thead>
                    <tr>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th class="center">Units</th>
                        <th class="center">Yr</th>
                        <th class="center">Sem</th>
                        <th>Prerequisite</th>
                        <th class="center">Grade</th>
                        <th class="center">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($irregular_classes as $cls):
                    $grade_info  = $grades[$cls['subject_id']] ?? null;
                    $grade_val   = $grade_info['grade'] ?? null;
                    $lock        = is_prereq_locked($cls['prerequisite'], $failed_codes);
                    $failed_subj = $grade_val && ($grade_val === '5.00' || strtoupper($grade_val) === 'INC');
                    $slots_left  = $cls['max_slots'] - $cls['enrolled_count'];
                ?>
                <tr class="<?php echo $lock['locked'] ? 'row-locked' : ($failed_subj ? 'row-failed' : ''); ?>">
                    <td><span class="subj-code"><?php echo htmlspecialchars($cls['subject_code']); ?></span></td>
                    <td><?php echo htmlspecialchars($cls['subject_name']); ?></td>
                    <td class="center"><?php echo $cls['units']; ?></td>
                    <td class="center"><?php echo $cls['year_level'] ?? '—'; ?></td>
                    <td class="center" style="font-size:.78rem;"><?php echo $sem_labels[$cls['subj_sem'] ?? ''] ?? ($cls['subj_sem'] ?? '—'); ?></td>
                    <td class="prereq-cell"><?php echo htmlspecialchars($cls['prerequisite'] ?: '—'); ?></td>
                    <td class="center">
                        <?php if ($grade_val): ?>
                            <span class="grade-badge <?php echo $failed_subj ? 'grade-fail' : 'grade-pass'; ?>"><?php echo htmlspecialchars($grade_val); ?></span>
                        <?php else: ?>
                            <span style="color:var(--text-label);">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="center">
                        <?php if ($lock['locked']): ?>
                            <span class="lock-reason"><i class="fa-solid fa-lock"></i> <?php echo htmlspecialchars($lock['reason']); ?></span>
                        <?php elseif ($slots_left <= 0): ?>
                            <span style="font-size:.8rem;color:#dc2626;">Class Full</span>
                        <?php else: ?>
                            <button class="btn-select-sched"
                                    data-subject-id="<?php echo $cls['subject_id']; ?>"
                                    data-subject-code="<?php echo htmlspecialchars($cls['subject_code']); ?>"
                                    data-subject-name="<?php echo htmlspecialchars($cls['subject_name']); ?>"
                                    data-classes="<?php echo htmlspecialchars(json_encode([[
                                        'class_id'       => $cls['class_id'],
                                        'section'        => $cls['section'],
                                        'schedule_day'   => $cls['schedule_day'],
                                        'schedule_time'  => $cls['schedule_time'],
                                        'room'           => $cls['room'],
                                        'faculty_name'   => $cls['faculty_name'],
                                        'max_slots'      => $cls['max_slots'],
                                        'enrolled_count' => $cls['enrolled_count'],
                                    ]])); ?>">
                                <i class="fa-solid fa-calendar-plus"></i> Enroll
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($irregular_classes)): ?>
                <tr><td colspan="8" class="center" style="padding:2rem;color:var(--text-label);">No additional subjects available.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</main>
</div>

<!-- SCHEDULE SELECTION MODAL -->
<div id="schedModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <div>
                <div class="modal-subject-code" id="modalSubjectCode"></div>
                <div class="modal-subject-name" id="modalSubjectName"></div>
            </div>
            <button class="modal-close" id="modalClose"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <p style="font-size:.85rem;color:var(--text-label);margin-bottom:1rem;">Select a schedule to enroll in:</p>
            <div id="modalClassList"></div>
        </div>
    </div>
</div>

<script src="../../js/student/student_enrollment.js"></script>
<script src="../../js/student/student_main.js"></script>
</body>
</html>
