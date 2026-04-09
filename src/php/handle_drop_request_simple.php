<?php
session_start();
include("connection.php");
include("admin_functions.php");

check_admin_login($con);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id    = (int)$_POST['student_id'];
    $enrollment_id = (int)$_POST['enrollment_id'];
    $class_id      = (int)($_POST['class_id'] ?? 0);
    $action        = $_POST['action'] ?? '';

    // Get current enrollment info
    $stmt = mysqli_prepare($con, "SELECT status FROM enrollments WHERE enrollment_id = ? AND student_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $enrollment_id, $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $enroll = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$enroll) {
        error_log("Invalid enrollment: enrollment_id=$enrollment_id, student_id=$student_id");
        header("Location: ../pages/admin/admin_manual_enroll.php?student_id=$student_id&error=invalid&t=" . time());
        exit;
    }

    error_log("Current enrollment status: " . $enroll['status']);

    if ($action === 'accept') {
        // Only accept if status is drop_requested
        if ($enroll['status'] !== 'drop_requested') {
            error_log("Cannot accept - status is not drop_requested: " . $enroll['status']);
            header("Location: ../pages/admin/admin_manual_enroll.php?student_id=$student_id&error=invalid_status&t=" . time());
            exit;
        }

        // Accept drop request - change status to dropped
        $stmt = mysqli_prepare($con, "UPDATE enrollments SET status = 'dropped' WHERE enrollment_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $enrollment_id);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            
            // Decrement class enrolled count (only if was confirmed/ongoing)
            if ($class_id > 0) {
                mysqli_query($con, "UPDATE classes SET enrolled_count = GREATEST(0, enrolled_count - 1) WHERE class_id = $class_id");
            }
            
            // Check if student has any remaining active enrollments
            $check_stmt = mysqli_prepare($con, "SELECT COUNT(*) as active_count FROM enrollments WHERE student_id = ? AND status IN ('reserved','confirmed','ongoing')");
            mysqli_stmt_bind_param($check_stmt, "i", $student_id);
            mysqli_stmt_execute($check_stmt);
            $result = mysqli_fetch_assoc(mysqli_stmt_get_result($check_stmt));
            mysqli_stmt_close($check_stmt);
            
            // If no active enrollments, update student status
            if ($result['active_count'] == 0) {
                mysqli_query($con, "UPDATE students SET status = 'Not Enrolled', block_id = NULL WHERE student_id = $student_id");
            }
            
            header("Location: ../pages/admin/admin_manual_enroll.php?student_id=$student_id&success=drop_accepted&t=" . time());
        } else {
            mysqli_stmt_close($stmt);
            header("Location: ../pages/admin/admin_manual_enroll.php?student_id=$student_id&error=failed&t=" . time());
        }
    } elseif ($action === 'reject') {
        // Only reject if status is drop_requested
        if ($enroll['status'] !== 'drop_requested') {
            error_log("Cannot reject - status is not drop_requested: " . $enroll['status']);
            header("Location: ../pages/admin/admin_manual_enroll.php?student_id=$student_id&error=invalid_status&t=" . time());
            exit;
        }

        // Reject drop request - revert to confirmed (default safe status)
        $stmt = mysqli_prepare($con, "UPDATE enrollments SET status = 'confirmed' WHERE enrollment_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $enrollment_id);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            error_log("Drop rejected: enrollment_id=$enrollment_id, reverted to confirmed");
            header("Location: ../pages/admin/admin_manual_enroll.php?student_id=$student_id&success=drop_rejected&t=" . time());
        } else {
            mysqli_stmt_close($stmt);
            error_log("Failed to reject drop: " . mysqli_error($con));
            header("Location: ../pages/admin/admin_manual_enroll.php?student_id=$student_id&error=failed&t=" . time());
        }
    } else {
        error_log("Invalid action: $action");
        header("Location: ../pages/admin/admin_manual_enroll.php?student_id=$student_id&error=invalid_action&t=" . time());
    }
} else {
    header("Location: ../pages/admin/admin_students.php");
}
?>
