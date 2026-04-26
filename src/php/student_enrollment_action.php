<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once "connection.php";
include_once "functions.php";

$user_data  = check_login($con);
$student_id = $user_data['student_id'];
$action     = $_POST['action'] ?? '';

// Requires a passing grade in every prereq subject
// Bypass applies only to Year 1, 1st Semester subjects (fresh students have no prior grades)
function check_prerequisites($con, $student_id, $subject_id) {
    $stmt = mysqli_prepare($con, "SELECT prerequisite, year_level, semester, course_id FROM subjects WHERE subject_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $subject_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    $prereq_str = trim($row['prerequisite'] ?? '');
    if (empty($prereq_str)) return ['passed' => true, 'missing' => []];

    $subj_year = $row['year_level'] ?? null;
    $subj_sem  = $row['semester']   ?? null;
    $course_id = $row['course_id']  ?? null;

    // Bypass prereqs only for true Year 1, 1st semester subjects (fresh students have no prior grades)
    // Must check BOTH the subject's year AND semester — not just the semester
    if ((int)$subj_year === 1 && $subj_sem === '1st') {
        return ['passed' => true, 'missing' => []];
    }

    $missing = [];
    foreach (array_map('trim', explode(',', $prereq_str)) as $code) {
        if (empty($code)) continue;
        if (!preg_match('/^[A-Z]{2,}\s+\d/', $code)) continue;

        if ($course_id && $subj_year && $subj_sem) {
            // Strip dot-suffixes to find the base subject code (e.g. CET 0211.1.1 -> CET 0211)
            $base_code = preg_replace('/^([A-Z]{2,}\s+\d+)(\..*)?$/', '$1', $code);
            $escaped = mysqli_real_escape_string($con, $base_code);
            $db_row = mysqli_fetch_assoc(mysqli_query($con,
                "SELECT year_level as yl, semester as sem FROM subjects
                 WHERE subject_code = '$escaped' AND course_id = $course_id LIMIT 1"
            ));
            if ($db_row) {
                if ($db_row['yl'] == $subj_year && $db_row['sem'] === $subj_sem) continue;
                // Use base_code for the actual grade check below
                $code = $base_code;
            }
            // If not found even after stripping, fall through to grade check with original code
        }

        $chk = mysqli_prepare($con, "
            SELECT g.grade FROM grades g
            JOIN subjects s ON g.subject_id = s.subject_id
            WHERE g.student_id = ? AND s.subject_code = ?
            ORDER BY g.grade_id DESC LIMIT 1
        ");
        mysqli_stmt_bind_param($chk, "is", $student_id, $code);
        mysqli_stmt_execute($chk);
        $grade_row = mysqli_fetch_assoc(mysqli_stmt_get_result($chk));
        mysqli_stmt_close($chk);
        $grade = $grade_row['grade'] ?? null;

        $is_passing = $grade !== null && $grade !== '5.00' && strtoupper($grade) !== 'INC';
        if (!$is_passing) {
            $missing[] = $code . ($grade === null ? ' (Not taken)' : ' (Failed)');
        }
    }
    return ['passed' => empty($missing), 'missing' => $missing];
}

// Get student's block semester
function get_block_semester($con, $student_id) {
    $row = mysqli_fetch_assoc(mysqli_query($con,
        "SELECT b.semester FROM students s
         LEFT JOIN blocks b ON s.block_id = b.block_id
         WHERE s.student_id = $student_id LIMIT 1"
    ));
    return $row['semester'] ?? null;
}

// Check if student is already enrolled in a subject (any class, any active status)
function already_enrolled_subject($con, $student_id, $subject_id) {
    $chk = mysqli_prepare($con, "
        SELECT e.enrollment_id FROM enrollments e
        JOIN classes c ON e.class_id = c.class_id
        WHERE e.student_id = ? AND c.subject_id = ?
        AND e.status IN ('reserved','confirmed','ongoing','drop_requested')
    ");
    mysqli_stmt_bind_param($chk, "ii", $student_id, $subject_id);
    mysqli_stmt_execute($chk);
    mysqli_stmt_store_result($chk);
    $found = mysqli_stmt_num_rows($chk) > 0;
    mysqli_stmt_close($chk);
    return $found;
}

// ── CONFIRM reserved enrollment ──────────────────────────────
if ($action === 'confirm') {
    $enrollment_id = (int)($_POST['enrollment_id'] ?? 0);

    $stmt = mysqli_prepare($con, "SELECT e.*, c.max_slots, c.enrolled_count, c.subject_id, s.units FROM enrollments e JOIN classes c ON e.class_id = c.class_id JOIN subjects s ON c.subject_id = s.subject_id WHERE e.enrollment_id = ? AND e.student_id = ? AND e.status = 'reserved'");
    mysqli_stmt_bind_param($stmt, "ii", $enrollment_id, $student_id);
    mysqli_stmt_execute($stmt);
    $enroll = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$enroll) { header("Location: ../pages/student/student_enrollment.php?error=invalid"); die; }

    $prereq = check_prerequisites($con, $student_id, $enroll['subject_id']);
    if (!$prereq['passed']) {
        $missing = urlencode(implode(', ', $prereq['missing']));
        header("Location: ../pages/student/student_enrollment.php?error=prereq&missing=$missing"); die;
    }

    if ($enroll['enrolled_count'] >= $enroll['max_slots']) {
        header("Location: ../pages/student/student_enrollment.php?error=full"); die;
    }

    $stmt = mysqli_prepare($con, "UPDATE enrollments SET status = 'confirmed' WHERE enrollment_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $enrollment_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    mysqli_query($con, "UPDATE classes SET enrolled_count = enrolled_count + 1 WHERE class_id = {$enroll['class_id']}");
    mysqli_query($con, "UPDATE students SET status = 'Enrolled' WHERE student_id = $student_id");

    header("Location: ../pages/student/student_enrollment.php?success=confirmed"); die;
}

// ── SELF-ENROLL in an available class ────────────────────────
if ($action === 'self_enroll') {
    // Check enrollment period
    require_once __DIR__ . '/admin_functions.php';
    if (get_setting($con, 'enrollment_open', '1') !== '1') {
        header("Location: ../pages/student/student_enrollment.php?error=enrollment_closed"); die;
    }

    $class_id = (int)($_POST['class_id'] ?? 0);

    $stmt = mysqli_prepare($con, "SELECT c.*, s.subject_id, s.subject_code, s.prerequisite, s.units, s.year_level FROM classes c JOIN subjects s ON c.subject_id = s.subject_id WHERE c.class_id = ? AND c.status = 'open'");
    mysqli_stmt_bind_param($stmt, "i", $class_id);
    mysqli_stmt_execute($stmt);
    $class = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$class) { header("Location: ../pages/student/student_enrollment.php?error=invalid"); die; }

    require_once __DIR__ . '/admin_functions.php';
    $cur_semester = get_setting($con, 'current_semester', '');

    // Get student info once
    $student_row = mysqli_fetch_assoc(mysqli_query($con,
        "SELECT year_level, registration_status FROM students WHERE student_id = $student_id LIMIT 1"
    ));
    $is_irregular = ($student_row['registration_status'] ?? '') === 'Irregular';

    // Check if this is a retake (student previously had a completed OR confirmed enrollment for this subject)
    $is_retake_enroll = (bool)mysqli_fetch_assoc(mysqli_query($con,
        "SELECT enrollment_id FROM enrollments
         WHERE student_id = $student_id AND class_id IN (
             SELECT class_id FROM classes WHERE subject_id = {$class['subject_id']}
         ) AND status = 'completed' LIMIT 1"
    ));

    // Check if this is a deficient subject (student has a failed/INC grade for it)
    $is_deficient = (bool)mysqli_fetch_assoc(mysqli_query($con,
        "SELECT g.grade_id FROM grades g
         WHERE g.student_id = $student_id
           AND g.subject_id = {$class['subject_id']}
           AND (g.grade = '5.00' OR UPPER(g.grade) = 'INC')
         LIMIT 1"
    ));

    // Also bypass if subject year_level differs from student's current year (retaking from a previous year)
    $subj_year = (int)($class['year_level'] ?? 0);
    $student_year = (int)($student_row['year_level'] ?? 0);
    $is_prior_year_subject = $subj_year > 0 && $subj_year < $student_year;

    // Build touched years: years the student has grades or enrollments in
    $touched_years = [$student_year];
    $ty = mysqli_query($con, "SELECT DISTINCT s.year_level FROM grades g JOIN subjects s ON g.subject_id = s.subject_id WHERE g.student_id = $student_id AND s.year_level IS NOT NULL");
    while ($r = mysqli_fetch_assoc($ty)) $touched_years[] = (int)$r['year_level'];
    $te = mysqli_query($con, "SELECT DISTINCT s.year_level FROM enrollments e JOIN classes c ON e.class_id = c.class_id JOIN subjects s ON c.subject_id = s.subject_id WHERE e.student_id = $student_id AND e.status IN ('confirmed','ongoing','drop_requested','completed') AND s.year_level IS NOT NULL");
    while ($r = mysqli_fetch_assoc($te)) $touched_years[] = (int)$r['year_level'];
    $touched_years = array_unique($touched_years);

    // Block only if subject is in a future year the student has never touched
    $is_untouched_future = $subj_year > 0 && $subj_year > $student_year && !in_array($subj_year, $touched_years);
    if ($is_untouched_future) {
        header("Location: ../pages/student/student_enrollment.php?error=wrong_year"); die;
    }

    // Irregular students bypass year/semester restrictions entirely.
    // Retake/deficient/prior-year bypass year restriction but NOT semester —
    // a failed subject must still be retaken in the correct semester when it's offered.
    $bypass_year = $is_irregular || $is_retake_enroll || $is_deficient || $is_prior_year_subject;
    $bypass_semester = $is_irregular;

    if (!$bypass_semester && !empty($cur_semester) && $class['semester'] !== $cur_semester) {
        header("Location: ../pages/student/student_enrollment.php?error=wrong_semester"); die;
    }
    if (!$bypass_year && !empty($subj_year) && $subj_year !== $student_year) {
        header("Location: ../pages/student/student_enrollment.php?error=wrong_year"); die;
    }

    // Block same subject (any schedule) including during drop_requested
    if (already_enrolled_subject($con, $student_id, $class['subject_id'])) {
        header("Location: ../pages/student/student_enrollment.php?error=already_enrolled"); die;
    }

    // Enforce prerequisites
    $prereq = check_prerequisites($con, $student_id, $class['subject_id']);
    if (!$prereq['passed']) {
        $missing = urlencode(implode(', ', $prereq['missing']));
        header("Location: ../pages/student/student_enrollment.php?error=prereq&missing=$missing"); die;
    }

    // Check min/max units
    require_once __DIR__ . '/admin_functions.php';
    $min_units = (int)get_setting($con, 'min_units', '0');
    $max_units = (int)get_setting($con, 'max_units', '0');
    if ($min_units > 0 || $max_units > 0) {
        $units_row = mysqli_fetch_assoc(mysqli_query($con,
            "SELECT COALESCE(SUM(s.units), 0) as total
             FROM enrollments e
             JOIN classes c ON e.class_id = c.class_id
             JOIN subjects s ON c.subject_id = s.subject_id
             WHERE e.student_id = $student_id
               AND e.status IN ('confirmed','ongoing')
               AND c.semester = '" . mysqli_real_escape_string($con, $cur_semester) . "'"
        ));
        $current_units = (int)($units_row['total'] ?? 0);
        $adding_units  = (int)($class['units'] ?? 0);
        if ($max_units > 0 && ($current_units + $adding_units) > $max_units) {
            header("Location: ../pages/student/student_enrollment.php?error=max_units&max=$max_units"); die;
        }
    }

    if ($class['enrolled_count'] >= $class['max_slots']) {
        header("Location: ../pages/student/student_enrollment.php?error=full"); die;
    }

    $semester_num = ($class['semester'] === '1st') ? 1 : (($class['semester'] === '2nd') ? 2 : 0);
    $stmt = mysqli_prepare($con, "INSERT INTO enrollments (student_id, class_id, school_year, semester, status) VALUES (?, ?, ?, ?, 'confirmed')");
    mysqli_stmt_bind_param($stmt, "iisi", $student_id, $class_id, $class['school_year'], $semester_num);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    mysqli_query($con, "UPDATE classes SET enrolled_count = enrolled_count + 1 WHERE class_id = $class_id");
    mysqli_query($con, "UPDATE students SET status = 'Enrolled' WHERE student_id = $student_id");

    header("Location: ../pages/student/student_enrollment.php?success=enrolled"); die;
}

// ── REQUEST DROP ─────────────────────────────────────────────
if ($action === 'drop') {
    $enrollment_id = (int)($_POST['enrollment_id'] ?? 0);

    $stmt = mysqli_prepare($con, "SELECT class_id, status FROM enrollments WHERE enrollment_id = ? AND student_id = ? AND status IN ('confirmed','ongoing')");
    mysqli_stmt_bind_param($stmt, "ii", $enrollment_id, $student_id);
    mysqli_stmt_execute($stmt);
    $enroll = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$enroll) { header("Location: ../pages/student/student_enrollment.php?error=invalid"); die; }

    $upd = mysqli_prepare($con, "UPDATE enrollments SET status = 'drop_requested' WHERE enrollment_id = ?");
    mysqli_stmt_bind_param($upd, "i", $enrollment_id);
    mysqli_stmt_execute($upd);
    mysqli_stmt_close($upd);

    header("Location: ../pages/student/student_enrollment.php?success=drop_requested"); die;
}

// ── CANCEL reserved enrollment ───────────────────────────────
if ($action === 'cancel_reserved') {
    $enrollment_id = (int)($_POST['enrollment_id'] ?? 0);

    $stmt = mysqli_prepare($con, "SELECT class_id FROM enrollments WHERE enrollment_id = ? AND student_id = ? AND status = 'reserved'");
    mysqli_stmt_bind_param($stmt, "ii", $enrollment_id, $student_id);
    mysqli_stmt_execute($stmt);
    $enroll = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$enroll) { header("Location: ../pages/student/student_enrollment.php?error=invalid"); die; }

    $upd = mysqli_prepare($con, "UPDATE enrollments SET status = 'dropped' WHERE enrollment_id = ?");
    mysqli_stmt_bind_param($upd, "i", $enrollment_id);
    mysqli_stmt_execute($upd);
    mysqli_stmt_close($upd);

    header("Location: ../pages/student/student_enrollment.php?success=dropped"); die;
}

// ── CANCEL CONFIRMED ENROLLMENT (confirmed -> dropped) ───────
if ($action === 'cancel_self_enroll') {
    $enrollment_id = (int)($_POST['enrollment_id'] ?? 0);
    $stmt = mysqli_prepare($con, "SELECT class_id FROM enrollments WHERE enrollment_id = ? AND student_id = ? AND status = 'confirmed'");
    mysqli_stmt_bind_param($stmt, "ii", $enrollment_id, $student_id);
    mysqli_stmt_execute($stmt);
    $enroll = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$enroll) { header("Location: ../pages/student/student_enrollment.php?error=invalid"); die; }

    $upd = mysqli_prepare($con, "UPDATE enrollments SET status = 'dropped' WHERE enrollment_id = ?");
    mysqli_stmt_bind_param($upd, "i", $enrollment_id);
    mysqli_stmt_execute($upd);
    mysqli_stmt_close($upd);
    mysqli_query($con, "UPDATE classes SET enrolled_count = GREATEST(0, enrolled_count - 1) WHERE class_id = {$enroll['class_id']}");

    header("Location: ../pages/student/student_enrollment.php?success=removed"); die;
}

// ── CANCEL DROP REQUEST ──────────────────────────────────────
if ($action === 'cancel_drop_request') {
    $enrollment_id = (int)($_POST['enrollment_id'] ?? 0);

    $stmt = mysqli_prepare($con, "SELECT class_id FROM enrollments WHERE enrollment_id = ? AND student_id = ? AND status = 'drop_requested'");
    mysqli_stmt_bind_param($stmt, "ii", $enrollment_id, $student_id);
    mysqli_stmt_execute($stmt);
    $enroll = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$enroll) { header("Location: ../pages/student/student_enrollment.php?error=invalid"); die; }

    $upd = mysqli_prepare($con, "UPDATE enrollments SET status = 'confirmed' WHERE enrollment_id = ?");
    mysqli_stmt_bind_param($upd, "i", $enrollment_id);
    mysqli_stmt_execute($upd);
    mysqli_stmt_close($upd);

    header("Location: ../pages/student/student_enrollment.php?success=drop_cancelled"); die;
}

header("Location: ../pages/student/student_enrollment.php");
die;
?>

// Bypass applies only to Year 1, 1st Semester subjects (fresh students have no prior grades)
function check_prerequisites($con, $student_id, $subject_id) {
    $stmt = mysqli_prepare($con, "SELECT prerequisite, year_level, semester, course_id FROM subjects WHERE subject_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $subject_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    $prereq_str = trim($row['prerequisite'] ?? '');
    if (empty($prereq_str)) return ['passed' => true, 'missing' => []];

    $subj_year = $row['year_level'] ?? null;
    $subj_sem  = $row['semester']   ?? null;
    $course_id = $row['course_id']  ?? null;

    // Bypass prereqs only for true Year 1, 1st semester subjects (fresh students have no prior grades)
    // Must check BOTH the subject's year AND semester — not just the semester
    if ((int)$subj_year === 1 && $subj_sem === '1st') {
        return ['passed' => true, 'missing' => []];
    }

    $missing = [];
    foreach (array_map('trim', explode(',', $prereq_str)) as $code) {
        if (empty($code)) continue;
        if (!preg_match('/^[A-Z]{2,}\s+\d/', $code)) continue;

        if ($course_id && $subj_year && $subj_sem) {
            $escaped = mysqli_real_escape_string($con, $code);
            $db_row = mysqli_fetch_assoc(mysqli_query($con,
                "SELECT year_level as yl, semester as sem FROM subjects
                 WHERE subject_code = '$escaped' AND course_id = $course_id LIMIT 1"
            ));
            if ($db_row) {
                if ($db_row['yl'] == $subj_year && $db_row['sem'] === $subj_sem) continue;
            } else {
                // Only skip dot-suffix codes that truly don't exist in the DB
                continue;
            }
        }

        $chk = mysqli_prepare($con, "
            SELECT g.grade FROM grades g
            JOIN subjects s ON g.subject_id = s.subject_id
            WHERE g.student_id = ? AND s.subject_code = ?
            ORDER BY g.grade_id DESC LIMIT 1
        ");
        mysqli_stmt_bind_param($chk, "is", $student_id, $code);
        mysqli_stmt_execute($chk);
        $grade_row = mysqli_fetch_assoc(mysqli_stmt_get_result($chk));
        mysqli_stmt_close($chk);
        $grade = $grade_row['grade'] ?? null;

        $is_passing = $grade !== null && $grade !== '5.00' && strtoupper($grade) !== 'INC';
        if (!$is_passing) {
            $missing[] = $code . ($grade === null ? ' (Not taken)' : ' (Failed)');
        }
    }
    return ['passed' => empty($missing), 'missing' => $missing];
}

// Get student's block semester
function get_block_semester($con, $student_id) {
    $row = mysqli_fetch_assoc(mysqli_query($con,
        "SELECT b.semester FROM students s
         LEFT JOIN blocks b ON s.block_id = b.block_id
         WHERE s.student_id = $student_id LIMIT 1"
    ));
    return $row['semester'] ?? null;
}

// Check if student is already enrolled in a subject (any class, any active status)
function already_enrolled_subject($con, $student_id, $subject_id) {
    $chk = mysqli_prepare($con, "
        SELECT e.enrollment_id FROM enrollments e
        JOIN classes c ON e.class_id = c.class_id
        WHERE e.student_id = ? AND c.subject_id = ?
        AND e.status IN ('reserved','confirmed','ongoing','drop_requested')
    ");
    mysqli_stmt_bind_param($chk, "ii", $student_id, $subject_id);
    mysqli_stmt_execute($chk);
    mysqli_stmt_store_result($chk);
    $found = mysqli_stmt_num_rows($chk) > 0;
    mysqli_stmt_close($chk);
    return $found;
}

// ── CONFIRM reserved enrollment ──────────────────────────────
if ($action === 'confirm') {
    $enrollment_id = (int)($_POST['enrollment_id'] ?? 0);

    $stmt = mysqli_prepare($con, "SELECT e.*, c.max_slots, c.enrolled_count, c.subject_id, s.units FROM enrollments e JOIN classes c ON e.class_id = c.class_id JOIN subjects s ON c.subject_id = s.subject_id WHERE e.enrollment_id = ? AND e.student_id = ? AND e.status = 'reserved'");
    mysqli_stmt_bind_param($stmt, "ii", $enrollment_id, $student_id);
    mysqli_stmt_execute($stmt);
    $enroll = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$enroll) { header("Location: ../pages/student/student_enrollment.php?error=invalid"); die; }

    $prereq = check_prerequisites($con, $student_id, $enroll['subject_id']);
    if (!$prereq['passed']) {
        $missing = urlencode(implode(', ', $prereq['missing']));
        header("Location: ../pages/student/student_enrollment.php?error=prereq&missing=$missing"); die;
    }

    if ($enroll['enrolled_count'] >= $enroll['max_slots']) {
        header("Location: ../pages/student/student_enrollment.php?error=full"); die;
    }

    $stmt = mysqli_prepare($con, "UPDATE enrollments SET status = 'confirmed' WHERE enrollment_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $enrollment_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    mysqli_query($con, "UPDATE classes SET enrolled_count = enrolled_count + 1 WHERE class_id = {$enroll['class_id']}");
    mysqli_query($con, "UPDATE students SET status = 'Enrolled' WHERE student_id = $student_id");

    header("Location: ../pages/student/student_enrollment.php?success=confirmed"); die;
}

// ── SELF-ENROLL in an available class ────────────────────────
if ($action === 'self_enroll') {
    // Check enrollment period
    require_once __DIR__ . '/admin_functions.php';
    if (get_setting($con, 'enrollment_open', '1') !== '1') {
        header("Location: ../pages/student/student_enrollment.php?error=enrollment_closed"); die;
    }

    $class_id = (int)($_POST['class_id'] ?? 0);

    $stmt = mysqli_prepare($con, "SELECT c.*, s.subject_id, s.subject_code, s.prerequisite, s.units, s.year_level FROM classes c JOIN subjects s ON c.subject_id = s.subject_id WHERE c.class_id = ? AND c.status = 'open'");
    mysqli_stmt_bind_param($stmt, "i", $class_id);
    mysqli_stmt_execute($stmt);
    $class = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$class) { header("Location: ../pages/student/student_enrollment.php?error=invalid"); die; }

    require_once __DIR__ . '/admin_functions.php';
    $cur_semester = get_setting($con, 'current_semester', '');

    // Get student info once
    $student_row = mysqli_fetch_assoc(mysqli_query($con,
        "SELECT year_level, registration_status FROM students WHERE student_id = $student_id LIMIT 1"
    ));
    $is_irregular = ($student_row['registration_status'] ?? '') === 'Irregular';

    // Check if this is a retake (student previously had a completed OR confirmed enrollment for this subject)
    $is_retake_enroll = (bool)mysqli_fetch_assoc(mysqli_query($con,
        "SELECT enrollment_id FROM enrollments
         WHERE student_id = $student_id AND class_id IN (
             SELECT class_id FROM classes WHERE subject_id = {$class['subject_id']}
         ) AND status = 'completed' LIMIT 1"
    ));

    // Check if this is a deficient subject (student has a failed/INC grade for it)
    $is_deficient = (bool)mysqli_fetch_assoc(mysqli_query($con,
        "SELECT g.grade_id FROM grades g
         WHERE g.student_id = $student_id
           AND g.subject_id = {$class['subject_id']}
           AND (g.grade = '5.00' OR UPPER(g.grade) = 'INC')
         LIMIT 1"
    ));

    // Also bypass if subject year_level differs from student's current year (retaking from a previous year)
    $subj_year = (int)($class['year_level'] ?? 0);
    $student_year = (int)($student_row['year_level'] ?? 0);
    $is_prior_year_subject = $subj_year > 0 && $subj_year < $student_year;

    // Build touched years: years the student has grades or enrollments in
    $touched_years = [$student_year];
    $ty = mysqli_query($con, "SELECT DISTINCT s.year_level FROM grades g JOIN subjects s ON g.subject_id = s.subject_id WHERE g.student_id = $student_id AND s.year_level IS NOT NULL");
    while ($r = mysqli_fetch_assoc($ty)) $touched_years[] = (int)$r['year_level'];
    $te = mysqli_query($con, "SELECT DISTINCT s.year_level FROM enrollments e JOIN classes c ON e.class_id = c.class_id JOIN subjects s ON c.subject_id = s.subject_id WHERE e.student_id = $student_id AND e.status IN ('confirmed','ongoing','drop_requested','completed') AND s.year_level IS NOT NULL");
    while ($r = mysqli_fetch_assoc($te)) $touched_years[] = (int)$r['year_level'];
    $touched_years = array_unique($touched_years);

    // Block only if subject is in a future year the student has never touched
    $is_untouched_future = $subj_year > 0 && $subj_year > $student_year && !in_array($subj_year, $touched_years);
    if ($is_untouched_future) {
        header("Location: ../pages/student/student_enrollment.php?error=wrong_year"); die;
    }

    // Irregular students bypass year/semester restrictions entirely.
    // Retake/deficient/prior-year bypass year restriction but NOT semester —
    // a failed subject must still be retaken in the correct semester when it's offered.
    $bypass_year = $is_irregular || $is_retake_enroll || $is_deficient || $is_prior_year_subject;
    $bypass_semester = $is_irregular;

    if (!$bypass_semester && !empty($cur_semester) && $class['semester'] !== $cur_semester) {
        header("Location: ../pages/student/student_enrollment.php?error=wrong_semester"); die;
    }
    if (!$bypass_year && !empty($subj_year) && $subj_year !== $student_year) {
        header("Location: ../pages/student/student_enrollment.php?error=wrong_year"); die;
    }

    // Block same subject (any schedule) including during drop_requested
    if (already_enrolled_subject($con, $student_id, $class['subject_id'])) {
        header("Location: ../pages/student/student_enrollment.php?error=already_enrolled"); die;
    }

    // Enforce prerequisites
    $prereq = check_prerequisites($con, $student_id, $class['subject_id']);
    if (!$prereq['passed']) {
        $missing = urlencode(implode(', ', $prereq['missing']));
        header("Location: ../pages/student/student_enrollment.php?error=prereq&missing=$missing"); die;
    }

    // Check min/max units
    require_once __DIR__ . '/admin_functions.php';
    $min_units = (int)get_setting($con, 'min_units', '0');
    $max_units = (int)get_setting($con, 'max_units', '0');
    if ($min_units > 0 || $max_units > 0) {
        $units_row = mysqli_fetch_assoc(mysqli_query($con,
            "SELECT COALESCE(SUM(s.units), 0) as total
             FROM enrollments e
             JOIN classes c ON e.class_id = c.class_id
             JOIN subjects s ON c.subject_id = s.subject_id
             WHERE e.student_id = $student_id
               AND e.status IN ('confirmed','ongoing')
               AND c.semester = '" . mysqli_real_escape_string($con, $cur_semester) . "'"
        ));
        $current_units = (int)($units_row['total'] ?? 0);
        $adding_units  = (int)($class['units'] ?? 0);
        if ($max_units > 0 && ($current_units + $adding_units) > $max_units) {
            header("Location: ../pages/student/student_enrollment.php?error=max_units&max=$max_units"); die;
        }
    }

    if ($class['enrolled_count'] >= $class['max_slots']) {
        header("Location: ../pages/student/student_enrollment.php?error=full"); die;
    }

    $semester_num = ($class['semester'] === '1st') ? 1 : (($class['semester'] === '2nd') ? 2 : 0);
    $stmt = mysqli_prepare($con, "INSERT INTO enrollments (student_id, class_id, school_year, semester, status) VALUES (?, ?, ?, ?, 'confirmed')");
    mysqli_stmt_bind_param($stmt, "iisi", $student_id, $class_id, $class['school_year'], $semester_num);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    mysqli_query($con, "UPDATE classes SET enrolled_count = enrolled_count + 1 WHERE class_id = $class_id");
    mysqli_query($con, "UPDATE students SET status = 'Enrolled' WHERE student_id = $student_id");

    header("Location: ../pages/student/student_enrollment.php?success=enrolled"); die;
}

// ── REQUEST DROP ─────────────────────────────────────────────
if ($action === 'drop') {
    $enrollment_id = (int)($_POST['enrollment_id'] ?? 0);

    $stmt = mysqli_prepare($con, "SELECT class_id, status FROM enrollments WHERE enrollment_id = ? AND student_id = ? AND status IN ('confirmed','ongoing')");
    mysqli_stmt_bind_param($stmt, "ii", $enrollment_id, $student_id);
    mysqli_stmt_execute($stmt);
    $enroll = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$enroll) { header("Location: ../pages/student/student_enrollment.php?error=invalid"); die; }

    $upd = mysqli_prepare($con, "UPDATE enrollments SET status = 'drop_requested' WHERE enrollment_id = ?");
    mysqli_stmt_bind_param($upd, "i", $enrollment_id);
    mysqli_stmt_execute($upd);
    mysqli_stmt_close($upd);

    header("Location: ../pages/student/student_enrollment.php?success=drop_requested"); die;
}

// ── CANCEL reserved enrollment ───────────────────────────────
if ($action === 'cancel_reserved') {
    $enrollment_id = (int)($_POST['enrollment_id'] ?? 0);

    $stmt = mysqli_prepare($con, "SELECT class_id FROM enrollments WHERE enrollment_id = ? AND student_id = ? AND status = 'reserved'");
    mysqli_stmt_bind_param($stmt, "ii", $enrollment_id, $student_id);
    mysqli_stmt_execute($stmt);
    $enroll = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$enroll) { header("Location: ../pages/student/student_enrollment.php?error=invalid"); die; }

    $upd = mysqli_prepare($con, "UPDATE enrollments SET status = 'dropped' WHERE enrollment_id = ?");
    mysqli_stmt_bind_param($upd, "i", $enrollment_id);
    mysqli_stmt_execute($upd);
    mysqli_stmt_close($upd);

    header("Location: ../pages/student/student_enrollment.php?success=dropped"); die;
}

// ── CANCEL CONFIRMED ENROLLMENT (confirmed -> dropped) ───────
if ($action === 'cancel_self_enroll') {
    $enrollment_id = (int)($_POST['enrollment_id'] ?? 0);
    $stmt = mysqli_prepare($con, "SELECT class_id FROM enrollments WHERE enrollment_id = ? AND student_id = ? AND status = 'confirmed'");
    mysqli_stmt_bind_param($stmt, "ii", $enrollment_id, $student_id);
    mysqli_stmt_execute($stmt);
    $enroll = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$enroll) { header("Location: ../pages/student/student_enrollment.php?error=invalid"); die; }

    $upd = mysqli_prepare($con, "UPDATE enrollments SET status = 'dropped' WHERE enrollment_id = ?");
    mysqli_stmt_bind_param($upd, "i", $enrollment_id);
    mysqli_stmt_execute($upd);
    mysqli_stmt_close($upd);
    mysqli_query($con, "UPDATE classes SET enrolled_count = GREATEST(0, enrolled_count - 1) WHERE class_id = {$enroll['class_id']}");

    header("Location: ../pages/student/student_enrollment.php?success=removed"); die;
}

// ── CANCEL DROP REQUEST ──────────────────────────────────────
if ($action === 'cancel_drop_request') {
    $enrollment_id = (int)($_POST['enrollment_id'] ?? 0);

    $stmt = mysqli_prepare($con, "SELECT class_id FROM enrollments WHERE enrollment_id = ? AND student_id = ? AND status = 'drop_requested'");
    mysqli_stmt_bind_param($stmt, "ii", $enrollment_id, $student_id);
    mysqli_stmt_execute($stmt);
    $enroll = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$enroll) { header("Location: ../pages/student/student_enrollment.php?error=invalid"); die; }

    $upd = mysqli_prepare($con, "UPDATE enrollments SET status = 'confirmed' WHERE enrollment_id = ?");
    mysqli_stmt_bind_param($upd, "i", $enrollment_id);
    mysqli_stmt_execute($upd);
    mysqli_stmt_close($upd);

    header("Location: ../pages/student/student_enrollment.php?success=drop_cancelled"); die;
}

header("Location: ../pages/student/student_enrollment.php");
die;
?>
