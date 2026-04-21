<?php
session_start();
include("connection.php");
include("admin_functions.php");

check_admin_login($con);

function check_prerequisites_admin($con, $student_id, $subject_id) {
    $stmt = mysqli_prepare($con, "SELECT prerequisite, year_level, semester FROM subjects WHERE subject_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $subject_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    $prereq_str = trim($row['prerequisite'] ?? '');
    if (empty($prereq_str)) return ['passed' => true, 'missing' => []];

    // Year 1, 1st semester subjects — bypass for fresh students
    $yr = mysqli_fetch_assoc(mysqli_query($con, "SELECT year_level FROM students WHERE student_id = $student_id LIMIT 1"));
    if ($yr && (int)$yr['year_level'] === 1 && ($row['semester'] ?? '') === '1st') {
        return ['passed' => true, 'missing' => []];
    }
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
        $is_passing = $grade !== null && $grade !== '5.00' && strtoupper($grade) !== 'INC';
        if (!$is_passing) $missing[] = $code . ($grade === null ? ' (Not taken)' : ' (Failed)');
    }
    return ['passed' => empty($missing), 'missing' => $missing];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = (int)$_POST['student_id'];
    $class_id   = (int)$_POST['class_id'];

    $class_query = mysqli_query($con, "SELECT * FROM classes WHERE class_id = $class_id");
    $class = mysqli_fetch_assoc($class_query);

    if (!$class) {
        header("Location: ../pages/admin/admin_manual_enroll.php?student_id=$student_id&error=class_not_found");
        exit;
    }

    if ($class['enrolled_count'] >= $class['max_slots']) {
        header("Location: ../pages/admin/admin_manual_enroll.php?student_id=$student_id&error=class_full");
        exit;
    }

    $chk = mysqli_query($con, "SELECT enrollment_id FROM enrollments WHERE student_id = $student_id AND class_id = $class_id AND status IN ('reserved','confirmed','ongoing')");
    if (mysqli_num_rows($chk) > 0) {
        header("Location: ../pages/admin/admin_manual_enroll.php?student_id=$student_id&error=already_enrolled");
        exit;
    }

    // Prerequisite check
    $subject_id = (int)$class['subject_id'];
    $prereq = check_prerequisites_admin($con, $student_id, $subject_id);
    if (!$prereq['passed']) {
        $missing = urlencode(implode(', ', $prereq['missing']));
        header("Location: ../pages/admin/admin_manual_enroll.php?student_id=$student_id&error=prereq&missing=$missing");
        exit;
    }

    $school_year = $class['school_year'];
    $semester    = ($class['semester'] === '1st') ? 1 : (($class['semester'] === '2nd') ? 2 : 0);

    $stmt = mysqli_prepare($con, "INSERT INTO enrollments (student_id, class_id, school_year, semester, status) VALUES (?, ?, ?, ?, 'reserved')");
    mysqli_stmt_bind_param($stmt, "iisi", $student_id, $class_id, $school_year, $semester);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_query($con, "UPDATE students SET status = 'Enrolled' WHERE student_id = $student_id");
        $subj_row = mysqli_fetch_assoc(mysqli_query($con, "SELECT s.subject_code FROM classes c JOIN subjects s ON c.subject_id=s.subject_id WHERE c.class_id=$class_id"));
        log_activity($con, 'Manually enrolled student', 'enrollment', 'Student ID ' . $student_id . ' → ' . ($subj_row['subject_code'] ?? 'Class ' . $class_id));
        header("Location: ../pages/admin/admin_manual_enroll.php?student_id=$student_id&success=reserved");
    } else {
        mysqli_stmt_close($stmt);
        header("Location: ../pages/admin/admin_manual_enroll.php?student_id=$student_id&error=failed");
    }
} else {
    header("Location: ../pages/admin/admin_students.php");
}
?>
