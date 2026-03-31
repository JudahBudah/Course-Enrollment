<?php
session_start();
include("connection.php");
include("functions.php");

$user_data = check_login($con);
$student_id = $user_data['student_id'];
$action = $_POST['action'] ?? '';

// Helper: check prerequisites for a subject
function check_prerequisites($con, $student_id, $subject_id) {
    // Get prerequisite codes for this subject
    $stmt = mysqli_prepare($con, "SELECT prerequisite FROM subjects WHERE subject_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $subject_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    $prereq_str = trim($row['prerequisite'] ?? '');
    if (empty($prereq_str)) return ['passed' => true, 'missing' => []];

    // Split by comma
    $prereq_codes = array_map('trim', explode(',', $prereq_str));
    $missing = [];

    foreach ($prereq_codes as $code) {
        if (empty($code)) continue;
        // Check if student has passed this subject (grade not 5.00, not INC, not null)
        $chk = mysqli_prepare($con, "
            SELECT g.grade FROM grades g
            JOIN subjects s ON g.subject_id = s.subject_id
            WHERE g.student_id = ? AND s.subject_code = ?
            ORDER BY g.grade_id DESC LIMIT 1
        ");
        mysqli_stmt_bind_param($chk, "is", $student_id, $code);
        mysqli_stmt_execute($chk);
        $res = mysqli_stmt_get_result($chk);
        $grade_row = mysqli_fetch_assoc($res);
        mysqli_stmt_close($chk);

        if (!$grade_row || $grade_row['grade'] === '5.00' || strtoupper($grade_row['grade']) === 'INC') {
            $missing[] = $code;
        }
    }

    return ['passed' => empty($missing), 'missing' => $missing];
}

// ── CONFIRM reserved enrollment ──────────────────────────────
if ($action === 'confirm') {
    $enrollment_id = (int)($_POST['enrollment_id'] ?? 0);

    // Verify this enrollment belongs to this student and is reserved
    $stmt = mysqli_prepare($con, "SELECT e.*, c.max_slots, c.enrolled_count, c.subject_id FROM enrollments e JOIN classes c ON e.class_id = c.class_id WHERE e.enrollment_id = ? AND e.student_id = ? AND e.status = 'reserved'");
    mysqli_stmt_bind_param($stmt, "ii", $enrollment_id, $student_id);
    mysqli_stmt_execute($stmt);
    $enroll = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$enroll) {
        header("Location: ../pages/student/student_enrollment.php?error=invalid");
        die;
    }

    // Check prerequisites
    $prereq = check_prerequisites($con, $student_id, $enroll['subject_id']);
    if (!$prereq['passed']) {
        $missing = urlencode(implode(', ', $prereq['missing']));
        header("Location: ../pages/student/student_enrollment.php?error=prereq&missing=$missing");
        die;
    }

    // Check class still has slots
    if ($enroll['enrolled_count'] >= $enroll['max_slots']) {
        header("Location: ../pages/student/student_enrollment.php?error=full");
        die;
    }

    // Confirm enrollment
    $stmt = mysqli_prepare($con, "UPDATE enrollments SET status = 'confirmed' WHERE enrollment_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $enrollment_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Update class slot count
    mysqli_query($con, "UPDATE classes SET enrolled_count = enrolled_count + 1 WHERE class_id = {$enroll['class_id']}");

    header("Location: ../pages/student/student_enrollment.php?success=confirmed");
    die;
}

// ── CONFIRM ALL reserved enrollments at once ─────────────────
if ($action === 'confirm_all') {
    $stmt = mysqli_prepare($con, "
        SELECT e.enrollment_id, e.class_id, c.max_slots, c.enrolled_count, c.subject_id
        FROM enrollments e
        JOIN classes c ON e.class_id = c.class_id
        WHERE e.student_id = ? AND e.status = 'reserved'
    ");
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    $reserved = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);

    $confirmed = 0;
    $failed_prereq = [];
    $failed_full = [];

    while ($enroll = mysqli_fetch_assoc($reserved)) {
        $prereq = check_prerequisites($con, $student_id, $enroll['subject_id']);
        if (!$prereq['passed']) {
            $failed_prereq[] = implode(', ', $prereq['missing']);
            continue;
        }
        if ($enroll['enrolled_count'] >= $enroll['max_slots']) {
            $failed_full[] = $enroll['class_id'];
            continue;
        }
        $upd = mysqli_prepare($con, "UPDATE enrollments SET status = 'confirmed' WHERE enrollment_id = ?");
        mysqli_stmt_bind_param($upd, "i", $enroll['enrollment_id']);
        mysqli_stmt_execute($upd);
        mysqli_stmt_close($upd);
        mysqli_query($con, "UPDATE classes SET enrolled_count = enrolled_count + 1 WHERE class_id = {$enroll['class_id']}");
        $confirmed++;
    }

    header("Location: ../pages/student/student_enrollment.php?success=confirmed_all&count=$confirmed");
    die;
}

// ── SELF-ENROLL in an available class ────────────────────────
if ($action === 'self_enroll') {
    $class_id = (int)($_POST['class_id'] ?? 0);

    // Get class + subject info
    $stmt = mysqli_prepare($con, "SELECT c.*, s.subject_id, s.subject_code, s.prerequisite FROM classes c JOIN subjects s ON c.subject_id = s.subject_id WHERE c.class_id = ? AND c.status = 'open'");
    mysqli_stmt_bind_param($stmt, "i", $class_id);
    mysqli_stmt_execute($stmt);
    $class = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$class) {
        header("Location: ../pages/student/student_enrollment.php?error=invalid");
        die;
    }

    // Check not already enrolled
    $chk = mysqli_prepare($con, "SELECT enrollment_id FROM enrollments WHERE student_id = ? AND class_id = ? AND status IN ('reserved','confirmed','ongoing')");
    mysqli_stmt_bind_param($chk, "ii", $student_id, $class_id);
    mysqli_stmt_execute($chk);
    mysqli_stmt_store_result($chk);
    if (mysqli_stmt_num_rows($chk) > 0) {
        header("Location: ../pages/student/student_enrollment.php?error=already_enrolled");
        die;
    }
    mysqli_stmt_close($chk);

    // Check prerequisites
    $prereq = check_prerequisites($con, $student_id, $class['subject_id']);
    if (!$prereq['passed']) {
        $missing = urlencode(implode(', ', $prereq['missing']));
        header("Location: ../pages/student/student_enrollment.php?error=prereq&missing=$missing");
        die;
    }

    // Check slots
    if ($class['enrolled_count'] >= $class['max_slots']) {
        header("Location: ../pages/student/student_enrollment.php?error=full");
        die;
    }

    // Insert as 'ongoing' (student self-enrolled, no admin reservation needed)
    $semester_num = ($class['semester'] === '1st') ? 1 : (($class['semester'] === '2nd') ? 2 : 0);
    $stmt = mysqli_prepare($con, "INSERT INTO enrollments (student_id, class_id, school_year, semester, status) VALUES (?, ?, ?, ?, 'ongoing')");
    mysqli_stmt_bind_param($stmt, "iisi", $student_id, $class_id, $class['school_year'], $semester_num);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    mysqli_query($con, "UPDATE classes SET enrolled_count = enrolled_count + 1 WHERE class_id = $class_id");

    header("Location: ../pages/student/student_enrollment.php?success=enrolled");
    die;
}

// ── REQUEST DROP ─────────────────────────────────────────────
if ($action === 'drop') {
    $enrollment_id = (int)($_POST['enrollment_id'] ?? 0);

    // Verify ownership — only confirmed/ongoing can request drop
    $stmt = mysqli_prepare($con, "SELECT class_id, status FROM enrollments WHERE enrollment_id = ? AND student_id = ? AND status IN ('confirmed','ongoing')");
    mysqli_stmt_bind_param($stmt, "ii", $enrollment_id, $student_id);
    mysqli_stmt_execute($stmt);
    $enroll = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$enroll) {
        header("Location: ../pages/student/student_enrollment.php?error=invalid");
        die;
    }

    $upd = mysqli_prepare($con, "UPDATE enrollments SET status = 'drop_requested' WHERE enrollment_id = ?");
    mysqli_stmt_bind_param($upd, "i", $enrollment_id);
    mysqli_stmt_execute($upd);
    mysqli_stmt_close($upd);

    header("Location: ../pages/student/student_enrollment.php?success=drop_requested");
    die;
}

// ── CANCEL reserved enrollment (no admin needed) ─────────────
if ($action === 'cancel_reserved') {
    $enrollment_id = (int)($_POST['enrollment_id'] ?? 0);

    $stmt = mysqli_prepare($con, "SELECT class_id FROM enrollments WHERE enrollment_id = ? AND student_id = ? AND status = 'reserved'");
    mysqli_stmt_bind_param($stmt, "ii", $enrollment_id, $student_id);
    mysqli_stmt_execute($stmt);
    $enroll = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$enroll) {
        header("Location: ../pages/student/student_enrollment.php?error=invalid");
        die;
    }

    $upd = mysqli_prepare($con, "UPDATE enrollments SET status = 'dropped' WHERE enrollment_id = ?");
    mysqli_stmt_bind_param($upd, "i", $enrollment_id);
    mysqli_stmt_execute($upd);
    mysqli_stmt_close($upd);

    header("Location: ../pages/student/student_enrollment.php?success=dropped");
    die;
}

header("Location: ../pages/student/student_enrollment.php");
die;
?>
