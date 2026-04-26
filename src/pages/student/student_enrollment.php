<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

require_once '../../php/connection.php';
require_once '../../php/functions.php';
require_once '../../php/admin_functions.php';

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
$block_semester = $student['semester'] ?? null;

// System current semester — used to restrict enrollment
require_once '../../php/admin_functions.php';
$cur_semester       = get_setting($con, 'current_semester', '');
$effective_semester = $cur_semester;

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

// Get student's grades keyed by subject_id (latest grade)
$grades = [];
$all_grades_by_subject = []; // all grades per subject for retake display
$stmt = mysqli_prepare($con, "SELECT subject_id, grade, status FROM grades WHERE student_id = ? ORDER BY grade_id DESC");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    if (!isset($grades[$row['subject_id']])) $grades[$row['subject_id']] = $row;
    $all_grades_by_subject[$row['subject_id']][] = $row;
}

// Build set of failed and passed subject codes
// Check both finalized grades table AND pending grade_entries
$failed_codes = [];
$passed_codes = [];
foreach ($grades as $g) {
    $sc = mysqli_fetch_assoc(mysqli_query($con, "SELECT subject_code FROM subjects WHERE subject_id = {$g['subject_id']}"));
    if (!$sc) continue;
    if ($g['grade'] === '5.00' || strtoupper($g['grade']) === 'INC') {
        $failed_codes[] = $sc['subject_code'];
    } elseif ($g['grade'] !== null && $g['grade'] !== '') {
        $passed_codes[] = $sc['subject_code'];
    }
}

// Also check grade_entries (submitted but not yet finalized)
$ge_stmt = mysqli_prepare($con, "
    SELECT ge.computed_grade, s.subject_code
    FROM grade_entries ge
    JOIN enrollments e ON ge.enrollment_id = e.enrollment_id
    JOIN classes c ON e.class_id = c.class_id
    JOIN subjects s ON c.subject_id = s.subject_id
    WHERE ge.student_id = ?
");
mysqli_stmt_bind_param($ge_stmt, 'i', $student_id);
mysqli_stmt_execute($ge_stmt);
$ge_result = mysqli_stmt_get_result($ge_stmt);
while ($ge = mysqli_fetch_assoc($ge_result)) {
    $code = $ge['subject_code'];
    // Only use grade_entries if not already in finalized grades
    if (in_array($code, $failed_codes) || in_array($code, $passed_codes)) continue;
    // computed_grade < 70 transmutes to 5.00 (failing)
    if ($ge['computed_grade'] !== null) {
        if ((float)$ge['computed_grade'] < 70) {
            $failed_codes[] = $code;
        } else {
            $passed_codes[] = $code;
        }
    }
}
mysqli_stmt_close($ge_stmt);

// If a subject was failed then retaken and passed, remove it from failed_codes
$failed_codes = array_values(array_diff($failed_codes, $passed_codes));
$is_irregular = !empty($failed_codes) || ($student['registration_status'] ?? 'Regular') === 'Irregular';
if (!empty($failed_codes) && ($student['registration_status'] ?? 'Regular') !== 'Irregular') {
    mysqli_query($con, "UPDATE students SET registration_status = 'Irregular' WHERE student_id = $student_id");
    $student['registration_status'] = 'Irregular';
}

$cur_school_year = get_setting($con, 'current_school_year', '');

// Build set of year levels the student has touched (has grades or active enrollments in)
$student_year = (int)($student['year_level'] ?? 1);
$touched_years = [$student_year];
if ($is_irregular) {
    // Include years where the student has any grade
    $ty_res = mysqli_query($con, "
        SELECT DISTINCT s.year_level FROM grades g
        JOIN subjects s ON g.subject_id = s.subject_id
        WHERE g.student_id = $student_id AND s.year_level IS NOT NULL
    ");
    while ($ty = mysqli_fetch_assoc($ty_res)) {
        $touched_years[] = (int)$ty['year_level'];
    }
    // Include years where the student has any active enrollment
    $te_res = mysqli_query($con, "
        SELECT DISTINCT s.year_level FROM enrollments e
        JOIN classes c ON e.class_id = c.class_id
        JOIN subjects s ON c.subject_id = s.subject_id
        WHERE e.student_id = $student_id
          AND e.status IN ('confirmed','ongoing','drop_requested','completed')
          AND s.year_level IS NOT NULL
    ");
    while ($te = mysqli_fetch_assoc($te_res)) {
        $touched_years[] = (int)$te['year_level'];
    }
    $touched_years = array_unique($touched_years);
}
$active_enrollments = [];
if ($is_irregular) {
    $ae_stmt = mysqli_prepare($con,
        "SELECT e.enrollment_id, e.status, e.class_id, c.subject_id
         FROM enrollments e
         JOIN classes c ON e.class_id = c.class_id
         WHERE e.student_id = ? AND e.status IN ('confirmed','drop_requested')"
    );
    mysqli_stmt_bind_param($ae_stmt, "i", $student_id);
} else {
    $ae_stmt = mysqli_prepare($con,
        "SELECT e.enrollment_id, e.status, e.class_id, c.subject_id
         FROM enrollments e
         JOIN classes c ON e.class_id = c.class_id
         WHERE e.student_id = ? AND e.status IN ('confirmed','drop_requested')
           AND c.semester = ? AND c.school_year = ?"
    );
    mysqli_stmt_bind_param($ae_stmt, "iss", $student_id, $cur_semester, $cur_school_year);
}
mysqli_stmt_execute($ae_stmt);
$ae_result = mysqli_stmt_get_result($ae_stmt);
while ($row = mysqli_fetch_assoc($ae_result)) {
    $active_enrollments[$row['subject_id']] = $row;
}
mysqli_stmt_close($ae_stmt);

// Get available classes per subject for the modal (keyed by subject_id)
$available_classes = [];
if ($course_id) {
    $stmt = mysqli_prepare($con, "
        SELECT c.class_id, c.subject_id, c.section, c.schedule_day, c.schedule_time, c.room, c.max_slots, c.enrolled_count, c.semester,
               CONCAT(f.first_name, ' ', f.last_name) as faculty_name
        FROM classes c
        JOIN subjects s ON c.subject_id = s.subject_id
        LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
        WHERE c.status = 'open' AND s.course_id = ?
        ORDER BY c.subject_id, c.section
    ");
    mysqli_stmt_bind_param($stmt, "i", $course_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        // For irregular students, show all open classes regardless of semester
        // For regular students, only show classes matching the current semester
        if (!$is_irregular && !empty($effective_semester) && $row['semester'] !== $effective_semester) continue;
        $available_classes[$row['subject_id']][] = $row;
    }
}

// Fetch current enrolled subjects — for irregular: all semesters/years; for regular: current only
$enroll_query = $is_irregular
    ? "SELECT e.enrollment_id, e.status, s.subject_code, s.subject_name, s.lecture_hours, s.lab_hours, s.units,
              c.class_id, c.schedule_day, c.schedule_time, c.room, c.section, c.semester as class_semester, c.school_year as class_school_year,
              CONCAT(f.first_name, ' ', f.last_name) as faculty_name
       FROM enrollments e
       JOIN classes c ON e.class_id = c.class_id
       JOIN subjects s ON c.subject_id = s.subject_id
       LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
       WHERE e.student_id = ? AND e.status = 'confirmed'
       ORDER BY s.subject_code"
    : "SELECT e.enrollment_id, e.status, s.subject_code, s.subject_name, s.lecture_hours, s.lab_hours, s.units,
              c.class_id, c.schedule_day, c.schedule_time, c.room, c.section, c.semester as class_semester, c.school_year as class_school_year,
              CONCAT(f.first_name, ' ', f.last_name) as faculty_name
       FROM enrollments e
       JOIN classes c ON e.class_id = c.class_id
       JOIN subjects s ON c.subject_id = s.subject_id
       LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
       WHERE e.student_id = ? AND e.status = 'confirmed'
         AND c.semester = ? AND c.school_year = ?
       ORDER BY s.subject_code";

$stmt = mysqli_prepare($con, $enroll_query);
if ($is_irregular) {
    mysqli_stmt_bind_param($stmt, "i", $student_id);
} else {
    mysqli_stmt_bind_param($stmt, "iss", $student_id, $cur_semester, $cur_school_year);
}
mysqli_stmt_execute($stmt);
$enrolled_subjects = mysqli_stmt_get_result($stmt);

// Helper: check if a subject is locked due to missing/failed prerequisites
function is_prereq_locked($prereq_str, $failed_codes, $passed_codes, $year_level = 0, $subj_semester = '', $con = null, $course_id = null) {
    if (empty($prereq_str)) return ['locked' => false, 'reason' => ''];
    if ((int)$year_level === 1 && $subj_semester === '1st') return ['locked' => false, 'reason' => ''];
    $codes = array_map('trim', explode(',', $prereq_str));
    foreach ($codes as $code) {
        if (empty($code)) continue;
        if (!preg_match('/^[A-Z]{2,}\s+\d/', $code)) continue;

        if ($con && $course_id) {
            // Strip dot-suffixes to find the base subject code (e.g. CET 0211.1.1 -> CET 0211)
            $base_code = preg_replace('/^([A-Z]{2,}\s+\d+)(\..*)?$/', '$1', $code);
            $escaped = mysqli_real_escape_string($con, $base_code);
            $db_row = mysqli_fetch_assoc(mysqli_query($con,
                "SELECT year_level as yl, semester as sem FROM subjects
                 WHERE subject_code = '$escaped' AND course_id = $course_id LIMIT 1"
            ));
            if ($db_row) {
                if ($db_row['yl'] == $year_level && $db_row['sem'] === $subj_semester) continue;
                // Use base_code for the actual grade check below
                $code = $base_code;
            }
            // If not found even after stripping, fall through to grade check with original code
        }

        if (!in_array($code, $passed_codes)) {
            $reason = in_array($code, $failed_codes)
                ? "Prerequisite $code was failed"
                : "Prerequisite $code not yet taken";
            return ['locked' => true, 'reason' => $reason];
        }
    }
    return ['locked' => false, 'reason' => ''];
}

// For irregular students: all open classes for unpassed subjects across ALL years and semesters
// Grouped by subject_id — multiple classes per subject handled via modal
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
        if ($passed) continue;
        // Only show subjects from years the student has touched or their current year
        if (!in_array((int)$row['year_level'], $touched_years) && (int)$row['year_level'] > $student_year) continue;
        $sid = $row['subject_id'];
        if (!isset($irregular_classes[$sid])) {
            // Store subject meta on first encounter
            $irregular_classes[$sid] = [
                'subject_id'  => $sid,
                'subject_code'=> $row['subject_code'],
                'subject_name'=> $row['subject_name'],
                'units'       => $row['units'],
                'year_level'  => $row['year_level'],
                'subj_sem'    => $row['subj_sem'],
                'prerequisite'=> $row['prerequisite'],
                'classes'     => [],
            ];
        }
        $irregular_classes[$sid]['classes'][] = [
            'class_id'      => $row['class_id'],
            'section'       => $row['section'],
            'schedule_day'  => $row['schedule_day'],
            'schedule_time' => $row['schedule_time'],
            'room'          => $row['room'],
            'faculty_name'  => $row['faculty_name'],
            'max_slots'     => $row['max_slots'],
            'enrolled_count'=> $row['enrolled_count'],
            'semester'      => $row['semester'],
            'school_year'   => $row['school_year'],
        ];
    }
}

// Fetch drop-requested enrollments — filtered to current semester and school year
$stmt = mysqli_prepare($con, "
    SELECT e.enrollment_id, e.status, s.subject_code, s.subject_name, s.units,
           c.class_id, c.schedule_day, c.schedule_time, c.room, c.section,
           CONCAT(f.first_name, ' ', f.last_name) as faculty_name
    FROM enrollments e
    JOIN classes c ON e.class_id = c.class_id
    JOIN subjects s ON c.subject_id = s.subject_id
    LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
    WHERE e.student_id = ? AND e.status = 'drop_requested'
      AND c.semester = ? AND c.school_year = ?
    ORDER BY s.subject_code
");
mysqli_stmt_bind_param($stmt, "iss", $student_id, $cur_semester, $cur_school_year);
mysqli_stmt_execute($stmt);
$drop_requests = mysqli_stmt_get_result($stmt);

// Check enrollment period status
$enrollment_open = get_setting($con, 'enrollment_open', '1') === '1';

$year_labels = ['1' => '1st Year', '2' => '2nd Year', '3' => '3rd Year', '4' => '4th Year', '5' => '5th Year', '6' => '6th Year', '0' => 'Unassigned'];
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
    <script>
        (function() {
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark-mode');
        }
        })();
    </script>
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
                <img src="<?php echo $student['profile_photo'] ? '../../' . $student['profile_photo'] : '../../uploads/default.jpg'; ?>">
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
                <li><a href="student_my_subjects.php"><i class="fa-solid fa-layer-group"></i><div class="li-name">My Subjects</div></a></li>
                <li class="course-dropdown">
                    <a href="#" id="acad-dropdown">
                        <i class="fa-solid fa-school"></i>
                        <div class="li-name chev-space">Academics <i class="fa-solid fa-chevron-down"></i></div>
                    </a>
                    <div class="acad-dropdown-menu" id="acad-dropdown-menu">
                        <ul>
                            <li><a href="student_info-program.php">Program</a></li>
                            <li><a href="student_info-college.php">College</a></li>
                            <?php
                            $course_info = get_course_info($con, $student_course);
                            $curriculum_url = $course_info['curriculum_url'] ?? '';
                            if ($curriculum_url): ?>
                            <li><a href="<?php echo htmlspecialchars($curriculum_url); ?>" target="_blank">Curriculum</a></li>
                            <?php endif; ?>
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
    <?php if (!$enrollment_open): ?>
        <div class="flash flash-error" style="background:#f974161a;color:#c2410c;border-left-color:#f97416;">
            <i class="fa-solid fa-lock"></i>
            <strong>Enrollment is currently closed.</strong> The enrollment period has not started or has ended. Please check with the registrar.
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['success'])): ?>
        <div class="flash flash-success">
            <i class="fa-solid fa-check-circle"></i>
            <?php
            $msgs = [
                'enrolled'       => 'Successfully enrolled in subject.',
                'drop_requested' => 'Drop request submitted. Awaiting admin approval.',
                'drop_cancelled' => 'Drop request cancelled.',
                'removed'        => 'Subject removed from enrollment.',
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
                'wrong_year'       => 'This subject is not available for your current year level.',
                'prereq'           => 'Missing prerequisites: ' . htmlspecialchars(urldecode($_GET['missing'] ?? '')),
                'no_class'         => 'No class selected.',
                'enrollment_closed'=> 'Enrollment is currently closed. Please wait for the enrollment period to open.',
                'max_units'        => 'You have reached the maximum of ' . htmlspecialchars($_GET['max'] ?? '') . ' units allowed this semester.',
            ];
            echo $errs[$_GET['error']] ?? 'An error occurred.';
            ?>
        </div>
    <?php endif; ?>

    <!-- DROP REQUESTS -->
    <?php $drop_rows = mysqli_fetch_all($drop_requests, MYSQLI_ASSOC); if (!empty($drop_rows)): ?>
    <div class="card" style="border-left:4px solid #dc2626;">
        <div class="card-header" style="background:#dc2626;">
            <h2><i class="fa-solid fa-right-from-bracket"></i> Pending Drop Requests</h2>
            <span style="font-size:.8rem;color:rgba(255,255,255,.8);"><?php echo count($drop_rows); ?> awaiting admin approval</span>
        </div>
        <div style="padding:.6rem 1.25rem;background:rgba(220,38,38,.06);border-bottom:1px solid var(--off);font-size:.82rem;color:#dc2626;">
            <i class="fa-solid fa-circle-info"></i>
            Your drop request has been submitted. You will remain enrolled until an admin approves it. You may cancel the request below.
        </div>
    <div class="drop-table-wrapper">
        <div class="drop-table">

            <div class="drop-table-header">
                <div>Subject Code</div>
                <div class="drop-col-left">Subject Name</div>
                <div>Units</div>
                <div>Section</div>
                <div class="drop-col-left">Schedule</div>
                <div class="drop-col-left">Professor</div>
                <div>Action</div>
            </div>

            <div class="drop-table-body">
                <?php foreach ($drop_rows as $dr): ?>
                <div class="drop-row">
                    <div><span class="subj-code"><?php echo htmlspecialchars($dr['subject_code']); ?></span></div>
                    <div class="drop-col-left"><?php echo htmlspecialchars($dr['subject_name']); ?></div>
                    <div><?php echo $dr['units']; ?></div>
                    <div><?php echo htmlspecialchars($dr['section'] ?? 'TBA'); ?></div>
                    <div class="drop-col-left"><?php echo htmlspecialchars(($dr['schedule_day'] ?? '') . ' ' . ($dr['schedule_time'] ?? '') . ($dr['room'] ? ' · ' . $dr['room'] : '')); ?></div>
                    <div class="drop-col-left faculty-name"><?php echo htmlspecialchars($dr['faculty_name'] ?? 'TBA'); ?></div>
                    <div>
                        <form method="POST" action="../../php/student_enrollment_action.php" style="display:inline;">
                            <input type="hidden" name="action" value="cancel_drop_request">
                            <input type="hidden" name="enrollment_id" value="<?php echo $dr['enrollment_id']; ?>">
                            <button type="submit" class="action-btn cancel-btn"
                                    onclick="return confirm('Cancel your drop request for <?php echo htmlspecialchars(addslashes($dr['subject_code'])); ?>?')">
                                <i class="fa-solid fa-rotate-left"></i> Cancel Request
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>
    </div>
    <?php endif; ?>

    <!-- STUDENT INFO -->
    <div class="card">
        <div class="card-header"><h2>Student Information</h2></div>
        <div class="student-body">
            <div class="avatar-wrap">
                <img src="<?php echo $student['profile_photo'] ? '../../' . $student['profile_photo'] : '../../uploads/default.jpg'; ?>">
            </div>
            <div class="student-details">
                <div class="detail-item"><label>Full Name</label><span><?php
                    $parts = array_filter([
                        $student['first_name'] ?? '',
                        $student['middle_name'] ?? '',
                        $student['last_name'] ?? ''
                    ], fn($v) => trim($v) !== '');
                    echo htmlspecialchars(implode(' ', $parts) ?: 'N/A');
                ?></span></div>
                <div class="detail-item"><label>Student Number</label><span><?php echo htmlspecialchars($student['student_number']); ?></span></div>
                <div class="detail-item"><label>Program</label><span><?php echo htmlspecialchars($student['course']); ?></span></div>
                <div class="detail-item"><label>Year Level</label><span><?php echo $year_labels[$student['year_level']] ?? 'N/A'; ?></span></div>
                <div class="detail-item"><label>Status</label><span><?php echo htmlspecialchars(!empty($student['registration_status']) ? $student['registration_status'] : 'Regular'); ?></span></div>
                <div class="detail-item"><label>School Year</label><span><?php echo htmlspecialchars($cur_school_year ?: 'N/A'); ?></span></div>
            </div>
        </div>
    </div>

    <!-- CURRENT ENROLLMENTS -->
    <div class="card">
        <div class="table-section-head">Current Enrollments</div>
        <div class="enroll-table-wrapper">
            <div class="enroll-table">

                <div class="enroll-table-header">
                    <div>Subject Code</div>
                    <div class="enroll-col-left">Subject Name</div>
                    <div>Units</div>
                    <div class="enroll-col-left">Schedule</div>
                    <div class="enroll-col-left">Professor</div>
                    <div>Status</div>
                    <div>Action</div>
                </div>

                <div class="enroll-table-body">
                    <?php
                    $has_enrolled = false;
                    while ($row = mysqli_fetch_assoc($enrolled_subjects)):
                        $has_enrolled = true;
                        $s = ['label' => 'Enrolled', 'color' => '#16a34a'];
                    ?>
                    <div class="enroll-row">
                        <div><span class="subj-code"><?php echo htmlspecialchars($row['subject_code']); ?></span></div>
                        <div class="enroll-col-left"><?php echo htmlspecialchars($row['subject_name']); ?></div>
                        <div><?php echo $row['units']; ?></div>
                        <div class="enroll-col-left">
                            <div class="sched-cell">
                                <span class="sched-tag"><?php echo htmlspecialchars($row['section'] . ' · ' . $row['schedule_day'] . ' ' . $row['schedule_time'] . ($row['room'] ? ' · ' . $row['room'] : '')); ?></span>
                                <?php if ($is_irregular): ?>
                                <span style="font-size:.75rem;color:var(--text-label);display:block;margin-top:.2rem;"><?php echo htmlspecialchars(($sem_labels[$row['class_semester']] ?? $row['class_semester']) . ' ' . $row['class_school_year']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="enroll-col-left faculty-name"><?php echo htmlspecialchars($row['faculty_name'] ?? 'TBA'); ?></div>
                        <div>
                            <span class="status-badge" style="background:<?php echo $s['color']; ?>1a;color:<?php echo $s['color']; ?>;">
                                <?php echo $s['label']; ?>
                            </span>
                        </div>
                        <div>
                            <form method="POST" action="../../php/student_enrollment_action.php" style="display:inline;">
                                <input type="hidden" name="action" value="drop">
                                <input type="hidden" name="enrollment_id" value="<?php echo $row['enrollment_id']; ?>">
                                <button type="submit" class="action-btn cancel-btn"
                                        onclick="return confirm('Request to drop <?php echo htmlspecialchars(addslashes($row['subject_code'])); ?>? An admin must approve it.')">
                                    <i class="fa-solid fa-right-from-bracket"></i> Request Drop
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endwhile; ?>

                    <?php if (!$has_enrolled): ?>
                    <div class="enroll-empty">No current enrollments.</div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
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
                <?php foreach ($curriculum as $yr => $sems): ?>
                <button class="year-tab <?php echo (int)$yr === $student_year ? 'active' : ''; ?>"
                        data-year="<?php echo $yr; ?>">
                    <?php echo $year_labels[$yr] ?? "Year $yr"; ?>
                </button>
                <?php endforeach; ?>
            </div>
            <div class="curric-sem-tabs">
                <?php foreach ($curriculum as $yr => $sems):
                    foreach ($sems as $sem => $subjects):
                        $is_active_sem = ((int)$yr === $student_year && $sem === $effective_semester);
                ?>
                <button class="sem-tab <?php echo $is_active_sem ? 'active' : ''; ?>"
                        data-year="<?php echo $yr; ?>"
                        data-sem="<?php echo $sem; ?>"
                        data-target="panel_<?php echo "{$yr}_{$sem}"; ?>"
                        style="<?php echo (int)$yr === $student_year ? '' : 'display:none;'; ?>">
                    <?php echo $sem_labels[$sem] ?? $sem; ?>
                </button>
                <?php endforeach; endforeach; ?>
            </div>
        </div>

        <!-- Curriculum Panels -->
        <?php foreach ($curriculum as $yr => $semesters):
            foreach ($semesters as $sem => $subjects):
                $is_current_panel = ((int)$yr === $student_year && $sem === $effective_semester);
        ?>
        <div class="curric-panel <?php echo $is_current_panel ? 'active' : ''; ?>" id="panel_<?php echo "{$yr}_{$sem}"; ?>">

            <?php if (!$is_current_panel && !$is_irregular): ?>
                <div style="padding:.6rem 1.25rem;background:rgba(107,114,128,0.08);border-bottom:1px solid var(--off);font-size:.82rem;color:var(--text-label);display:flex;align-items:center;gap:.5rem;">
                    <i class="fa-solid fa-lock"></i>
                    Enrollment is only open for <strong><?php echo htmlspecialchars($sem_labels[$effective_semester] ?? $effective_semester); ?></strong> — <strong><?php echo $year_labels[$student_year] ?? 'Year '.$student_year; ?></strong>.
                </div>
            <?php elseif (!$is_current_panel && $is_irregular): ?>
                <div style="padding:.6rem 1.25rem;background:#f974161a;border-bottom:1px solid #fed7aa;font-size:.82rem;color:#c2410c;display:flex;align-items:center;gap:.5rem;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    You are <strong>Irregular</strong> — you may enroll in any unpassed subject from this panel alongside your current year's load.
                </div>
            <?php endif; ?>

            <div class="curric-table-wrapper">
                <div class="curric-table">

                    <div class="curric-table-header">
                        <div>Subject Code</div>
                        <div class="curric-col-left">Subject Name</div>
                        <div>Units</div>
                        <div class="curric-col-left">Prerequisite</div>
                        <div>Grade</div>
                        <div>Status</div>
                        <div>Action</div>
                    </div>

                    <div class="curric-table-body">
                    <?php foreach ($subjects as $subj):
                        $grade_info   = $grades[$subj['subject_id']] ?? null;
                        $enroll_info  = $active_enrollments[$subj['subject_id']] ?? null;
                        $classes_list = $available_classes[$subj['subject_id']] ?? [];
                        $grade_val    = $grade_info['grade'] ?? null;
                        $passed       = $grade_val && $grade_val !== '5.00' && strtoupper($grade_val) !== 'INC' && strtoupper($grade_val) !== 'DRP';
                        $failed       = $grade_val && ($grade_val === '5.00' || strtoupper($grade_val) === 'INC');
                        $is_future_year = !in_array((int)$yr, $touched_years) && (int)$yr > $student_year;
                        $lock         = is_prereq_locked($subj['prerequisite'], $failed_codes, $passed_codes, $subj['year_level'], $subj['semester'], $con, $course_id);
                        if ($enroll_info)        $row_class = 'row-enrolled';
                        elseif ($passed)         $row_class = 'row-passed';
                        elseif ($failed)         $row_class = 'row-failed';
                        elseif ($lock['locked']) $row_class = 'row-locked';
                        else                     $row_class = '';
                    ?>
                    <div class="curric-row <?php echo $row_class; ?>">
                        <div><span class="subj-code"><?php echo htmlspecialchars($subj['subject_code']); ?></span></div>
                        <div class="curric-col-left"><?php echo htmlspecialchars($subj['subject_name']); ?></div>
                        <div><?php echo $subj['units']; ?></div>
                        <div class="curric-col-left prereq-cell"><?php echo htmlspecialchars($subj['prerequisite'] ?: '—'); ?></div>
                        <div>
                            <?php
                            $subject_all_grades = $all_grades_by_subject[$subj['subject_id']] ?? [];
                            if (count($subject_all_grades) > 1):
                                foreach ($subject_all_grades as $idx => $sg):
                                    $is_latest = $idx === 0;
                            ?>
                                <span class="grade-badge <?php echo ($sg['grade'] !== '5.00' && strtoupper($sg['grade']) !== 'INC') ? 'grade-pass' : 'grade-fail'; ?>"
                                    style="<?php echo !$is_latest ? 'opacity:0.5;text-decoration:line-through;font-size:.7rem;' : ''; ?>">
                                    <?php echo htmlspecialchars($sg['grade']); ?>
                                </span>
                            <?php endforeach; ?>
                            <?php elseif ($grade_val): ?>
                                <span class="grade-badge <?php echo $passed ? 'grade-pass' : 'grade-fail'; ?>"><?php echo htmlspecialchars($grade_val); ?></span>
                            <?php else: ?>
                                <span style="color:var(--text-label);">—</span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php
                            $es_map = ['confirmed' => ['Enrolled','#16a34a'], 'drop_requested' => ['Drop Requested','#dc2626']];
                            if ($enroll_info): [$elabel,$ecolor] = $es_map[$enroll_info['status']] ?? [ucfirst($enroll_info['status']),'#888']; ?>
                                <span class="status-badge" style="background:<?php echo $ecolor; ?>1a;color:<?php echo $ecolor; ?>;"><?php echo $elabel; ?></span>
                            <?php elseif ($passed): ?>
                                <span class="status-badge" style="background:#16a34a1a;color:#16a34a;">Passed</span>
                            <?php elseif ($failed): ?>
                                <span class="status-badge" style="background:#dc26261a;color:#dc2626;">Failed</span>
                            <?php elseif ($lock['locked']): ?>
                                <span class="status-badge" style="background:#f974161a;color:#f97416;">Locked</span>
                            <?php else: ?>
                                <span class="status-badge" style="background:#6b72801a;color:var(--text);">Not Taken</span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php if ($enroll_info): ?>
                                <span style="font-size:.8rem;color:var(--text-label);">Enrolled</span>
                            <?php elseif ($passed): ?>
                                <span style="font-size:.8rem;color:var(--text-label);">—</span>
                            <?php elseif ($lock['locked']): ?>
                                <span class="lock-reason"><i class="fa-solid fa-lock"></i> <?php echo htmlspecialchars($lock['reason']); ?></span>
                            <?php elseif ($is_future_year): ?>
                                <span style="font-size:.8rem;color:var(--text-label);"><i class="fa-solid fa-lock"></i> Not current</span>
                            <?php elseif (!empty($classes_list)): ?>
                                <button class="btn-select-sched"
                                        data-subject-id="<?php echo $subj['subject_id']; ?>"
                                        data-subject-code="<?php echo htmlspecialchars($subj['subject_code']); ?>"
                                        data-subject-name="<?php echo htmlspecialchars($subj['subject_name']); ?>"
                                        data-classes="<?php echo htmlspecialchars(json_encode($classes_list)); ?>">
                                    <i class="fa-solid fa-calendar-plus"></i> Select Schedule
                                </button>
                            <?php else: ?>
                                <span style="font-size:.8rem;color:var(--text);">No class available</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    </div>

                </div>
            </div>
        </div>
        <?php endforeach; endforeach; ?>

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

        <div class="irreg-table-wrapper">
            <div class="irreg-table" id="irregularTable">

                <div class="irreg-table-header">
                    <div class="irreg-col-left">Subject Code</div>
                    <div class="irreg-col-left">Subject Name</div>
                    <div>Units</div>
                    <div>Yr</div>
                    <div>Sem</div>
                    <div class="irreg-col-left">Prerequisite</div>
                    <div>Grade</div>
                    <div>Action</div>
                </div>

                <div class="irreg-table-body">
                <?php foreach ($irregular_classes as $subj):
                    $grade_info  = $grades[$subj['subject_id']] ?? null;
                    $grade_val   = $grade_info['grade'] ?? null;
                    $lock        = is_prereq_locked($subj['prerequisite'], $failed_codes, $passed_codes, $subj['year_level'], $subj['subj_sem'] ?? '', $con, $course_id);
                    $failed_subj = $grade_val && ($grade_val === '5.00' || strtoupper($grade_val) === 'INC');
                    $slots_total = array_sum(array_map(fn($c) => max(0, $c['max_slots'] - $c['enrolled_count']), $subj['classes']));
                    $row_class   = $lock['locked'] ? 'row-locked' : ($failed_subj ? 'row-failed' : '');
                ?>
                <div class="irreg-row <?php echo $row_class; ?>">
                    <div class="irreg-col-left"><span class="subj-code"><?php echo htmlspecialchars($subj['subject_code']); ?></span></div>
                    <div class="irreg-col-left"><?php echo htmlspecialchars($subj['subject_name']); ?></div>
                    <div><?php echo $subj['units']; ?></div>
                    <div><?php echo $subj['year_level'] ?? '—'; ?></div>
                    <div style="font-size:.78rem;"><?php echo $sem_labels[$subj['subj_sem'] ?? ''] ?? ($subj['subj_sem'] ?? '—'); ?></div>
                    <div class="irreg-col-left prereq-cell"><?php echo htmlspecialchars($subj['prerequisite'] ?: '—'); ?></div>
                    <div>
                        <?php if ($grade_val): ?>
                            <span class="grade-badge <?php echo $failed_subj ? 'grade-fail' : 'grade-pass'; ?>"><?php echo htmlspecialchars($grade_val); ?></span>
                        <?php else: ?>
                            <span style="color:var(--text-label);">—</span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php if ($lock['locked']): ?>
                            <span class="lock-reason"><i class="fa-solid fa-lock"></i> <?php echo htmlspecialchars($lock['reason']); ?></span>
                        <?php elseif ($slots_total <= 0): ?>
                            <span style="font-size:.8rem;color:#dc2626;">All classes full</span>
                        <?php else: ?>
                            <button class="btn-select-sched"
                                    data-subject-id="<?php echo $subj['subject_id']; ?>"
                                    data-subject-code="<?php echo htmlspecialchars($subj['subject_code']); ?>"
                                    data-subject-name="<?php echo htmlspecialchars($subj['subject_name']); ?>"
                                    data-classes="<?php echo htmlspecialchars(json_encode($subj['classes'])); ?>">
                                <i class="fa-solid fa-calendar-plus"></i> Enroll
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if (empty($irregular_classes)): ?>
                <div class="irreg-empty">No additional subjects available.</div>
                <?php endif; ?>
                </div>

            </div>
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
