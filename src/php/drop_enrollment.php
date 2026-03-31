<?php
session_start();
include("connection.php");
include("admin_functions.php");

check_admin_login($con);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id   = (int)$_POST['student_id'];
    $enrollment_id = (int)$_POST['enrollment_id'];
    $class_id     = (int)$_POST['class_id'];

    // Get current status to decide whether to decrement slot count
    $chk = mysqli_prepare($con, "SELECT status FROM enrollments WHERE enrollment_id = ?");
    mysqli_stmt_bind_param($chk, "i", $enrollment_id);
    mysqli_stmt_execute($chk);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($chk));
    mysqli_stmt_close($chk);

    $stmt = mysqli_prepare($con, "UPDATE enrollments SET status = 'dropped' WHERE enrollment_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $enrollment_id);

    if (mysqli_stmt_execute($stmt)) {
        // Only decrement if was confirmed/ongoing (reserved never took a slot)
        if ($row && in_array($row['status'], ['confirmed', 'ongoing'])) {
            mysqli_query($con, "UPDATE classes SET enrolled_count = enrolled_count - 1 WHERE class_id = $class_id");
        }
        header("Location: ../pages/admin/admin_manual_enroll.php?student_id=$student_id&success=dropped");
    } else {
        header("Location: ../pages/admin/admin_manual_enroll.php?student_id=$student_id&error=failed");
    }
} else {
    header("Location: ../pages/admin/admin_students.php");
}
?>
