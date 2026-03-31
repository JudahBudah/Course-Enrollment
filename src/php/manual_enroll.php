<?php
session_start();
include("connection.php");
include("admin_functions.php");

check_admin_login($con);

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

    $chk = mysqli_query($con, "SELECT enrollment_id FROM enrollments WHERE student_id = $student_id AND class_id = $class_id AND status = 'ongoing'");
    if (mysqli_num_rows($chk) > 0) {
        header("Location: ../pages/admin/admin_manual_enroll.php?student_id=$student_id&error=already_enrolled");
        exit;
    }

    $school_year = $class['school_year'];
    $semester    = ($class['semester'] === '1st') ? 1 : (($class['semester'] === '2nd') ? 2 : 0);

    $stmt = mysqli_prepare($con, "INSERT INTO enrollments (student_id, class_id, school_year, semester, status) VALUES (?, ?, ?, ?, 'reserved')");
    mysqli_stmt_bind_param($stmt, "iisi", $student_id, $class_id, $school_year, $semester);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: ../pages/admin/admin_manual_enroll.php?student_id=$student_id&success=reserved");
    } else {
        header("Location: ../pages/admin/admin_manual_enroll.php?student_id=$student_id&error=failed");
    }
} else {
    header("Location: ../pages/admin/admin_students.php");
}
?>
