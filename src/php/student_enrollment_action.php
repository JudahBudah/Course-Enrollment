<?php
session_start();
include("connection.php");
include("functions.php");

$user_data  = check_login($con);
$student_id = $user_data['student_id'];
$action     = $_POST['action'] ?? '';

// Requires a passing grade in every prereq subject
function check_prerequisites($con, $student_id, $subject_id) {
    $stmt = mysqli_prepare($con, "SELECT prerequisite FROM subjects WHERE subject_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $subject_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    $prereq_str = trim($row['prerequisite'] ?? '');
    if (empty($prereq_str)) return ['passed' => true, 'missing' => []];

    $missing = [];
    foreach (array_map('trim', explode(',', $prereq_str)) as $code) {
        if (empty($code)) continue;
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
        // Must have a passing grade — null means not yet taken
        if ($grade === null || $grade === '5.00' || strtoupper($grade) === 'INC') {
            $missing[] = $code . ($grade === null ? '' : ' (Failed)');
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
    $class_id = (int)($_POST['class_id'] ?? 0);

    $stmt = mysqli_prepare($con, "SELECT c.*, s.subject_id, s.subject_code, s.prerequisite, s.units FROM classes c JOIN subjects s ON c.subject_id = s.subject_id WHERE c.class_id = ? AND c.status = 'open'");
    mysqli_stmt_bind_param($stmt, "i", $class_id);
    mysqli_stmt_execute($stmt);
    $class = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$class) { header("Location: ../pages/student/student_enrollment.php?error=invalid"); die; }

    // TC004: block wrong semester
    $block_sem = get_block_semester($con, $student_id);
    if ($block_sem && $class['semester'] !== $block_sem) {
        header("Location: ../pages/student/student_enrollment.php?error=wrong_semester"); die;
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

    if ($class['enrolled_count'] >= $class['max_slots']) {
        header("Location: ../pages/student/student_enrollment.php?error=full"); die;
    }

    $semester_num = ($class['semester'] === '1st') ? 1 : (($class['semester'] === '2nd') ? 2 : 0);
    $stmt = mysqli_prepare($con, "INSERT INTO enrollments (student_id, class_id, school_year, semester, status) VALUES (?, ?, ?, ?, 'ongoing')");
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

// ── SUBMIT ENROLLMENT (ongoing -> reserved) ──────────────────
if ($action === 'submit_enrollment') {
    $stmt = mysqli_prepare($con, "UPDATE enrollments SET status = 'reserved' WHERE student_id = ? AND status = 'ongoing'");
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: ../pages/student/student_enrollment.php?success=submitted"); die;
}

// ── CANCEL SELF ENROLL (ongoing -> dropped) ──────────────────
if ($action === 'cancel_self_enroll') {
    $enrollment_id = (int)($_POST['enrollment_id'] ?? 0);
    $stmt = mysqli_prepare($con, "SELECT class_id FROM enrollments WHERE enrollment_id = ? AND student_id = ? AND status = 'ongoing'");
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
