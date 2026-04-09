<?php
session_start();
include("connection.php");
include("admin_functions.php");
include("enrollment_status_helper.php");

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

    if (!$row) {
        error_log("Drop enrollment failed: enrollment_id $enrollment_id not found");
        header("Location: ../pages/admin/admin_manual_enroll.php?student_id=$student_id&error=not_found");
        exit;
    }

    // Update enrollment status to dropped
    $stmt = mysqli_prepare($con, "UPDATE enrollments SET status = 'dropped' WHERE enrollment_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $enrollment_id);

    if (mysqli_stmt_execute($stmt)) {
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
        
        error_log("Drop enrollment: enrollment_id=$enrollment_id, student_id=$student_id, previous_status={$row['status']}, affected_rows=$affected");
        
        // Only decrement if was confirmed/ongoing (reserved never took a slot)
        if ($row && in_array($row['status'], ['confirmed', 'ongoing'])) {
            $class_update = mysqli_query($con, "UPDATE classes SET enrolled_count = GREATEST(0, enrolled_count - 1) WHERE class_id = $class_id");
            error_log("Class count decremented for class_id=$class_id, success=" . ($class_update ? 'yes' : 'no'));
        }

        // Update student status based on remaining active enrollments
        $remaining = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM enrollments WHERE student_id = $student_id AND status IN ('reserved','confirmed','ongoing')"));
        $new_status = $remaining['c'] > 0 ? 'Enrolled' : 'Dropped';
        mysqli_query($con, "UPDATE students SET status = '$new_status' WHERE student_id = $student_id");
        
        header("Location: ../pages/admin/admin_manual_enroll.php?student_id=$student_id&success=dropped&t=" . time());
    } else {
        error_log("Drop enrollment failed: " . mysqli_error($con));
        mysqli_stmt_close($stmt);
        header("Location: ../pages/admin/admin_manual_enroll.php?student_id=$student_id&error=failed");
    }
} else {
    header("Location: ../pages/admin/admin_students.php");
}
?>
