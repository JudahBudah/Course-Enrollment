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
    $redirect      = $_POST['redirect'] ?? 'manual'; // 'manual' or 'enrollments'

    // Start transaction for data consistency
    mysqli_begin_transaction($con);
    
    try {
        // Get current enrollment info
        $stmt = mysqli_prepare($con, "SELECT e.status, e.class_id FROM enrollments e WHERE e.enrollment_id = ? AND e.student_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $enrollment_id, $student_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $enroll = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$enroll) {
            throw new Exception("Invalid enrollment");
        }

        if ($action === 'accept') {
            // Only accept if status is drop_requested
            if ($enroll['status'] !== 'drop_requested') {
                throw new Exception("Cannot accept - status is not drop_requested");
            }

            // 1. Update enrollment status to 'dropped'
            $stmt = mysqli_prepare($con, "UPDATE enrollments SET status = 'dropped' WHERE enrollment_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $enrollment_id);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to update enrollment status");
            }
            mysqli_stmt_close($stmt);

            // 2. Decrement class enrolled count
            $actual_class_id = $class_id > 0 ? $class_id : $enroll['class_id'];
            if ($actual_class_id > 0) {
                $update_class = mysqli_query($con, "UPDATE classes SET enrolled_count = GREATEST(0, enrolled_count - 1) WHERE class_id = $actual_class_id");
                if (!$update_class) {
                    throw new Exception("Failed to update class count");
                }
            }

            // 3. Check if student has any remaining active enrollments
            $check_stmt = mysqli_prepare($con, "SELECT COUNT(*) as active_count FROM enrollments WHERE student_id = ? AND status IN ('reserved','confirmed','ongoing')");
            mysqli_stmt_bind_param($check_stmt, "i", $student_id);
            mysqli_stmt_execute($check_stmt);
            $result = mysqli_fetch_assoc(mysqli_stmt_get_result($check_stmt));
            mysqli_stmt_close($check_stmt);

            // 4. Update student status if no active enrollments
            if ($result['active_count'] == 0) {
                $update_student = mysqli_query($con, "UPDATE students SET status = 'Not Enrolled', block_id = NULL WHERE student_id = $student_id");
                if (!$update_student) {
                    throw new Exception("Failed to update student status");
                }
            }

            // Commit transaction
            mysqli_commit($con);
            log_activity($con, 'Approved drop request', 'enrollment', 'Enrollment ID ' . $enrollment_id . ' — Student ID ' . $student_id);
            $back = $redirect === 'enrollments'
                ? '../pages/admin/admin_enrollments.php?success=drop_accepted'
                : ($redirect === 'drop_requests'
                    ? '../pages/admin/admin_drop_requests.php?success=drop_accepted'
                    : '../pages/admin/admin_manual_enroll.php?student_id=' . $student_id . '&success=drop_accepted&t=' . time());
            header("Location: $back");
            exit;

        } elseif ($action === 'reject') {
            // Only reject if status is drop_requested
            if ($enroll['status'] !== 'drop_requested') {
                throw new Exception("Cannot reject - status is not drop_requested");
            }

            // Revert status back to confirmed
            $stmt = mysqli_prepare($con, "UPDATE enrollments SET status = 'confirmed' WHERE enrollment_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $enrollment_id);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to update enrollment status");
            }
            mysqli_stmt_close($stmt);

            // Commit transaction
            mysqli_commit($con);
            log_activity($con, 'Rejected drop request', 'enrollment', 'Enrollment ID ' . $enrollment_id . ' — Student ID ' . $student_id);
            $back = $redirect === 'enrollments'
                ? '../pages/admin/admin_enrollments.php?success=drop_rejected'
                : ($redirect === 'drop_requests'
                    ? '../pages/admin/admin_drop_requests.php?success=drop_rejected'
                    : '../pages/admin/admin_manual_enroll.php?student_id=' . $student_id . '&success=drop_rejected&t=' . time());
            header("Location: $back");
            exit;

        } else {
            throw new Exception("Invalid action");
        }

    } catch (Exception $e) {
        // Rollback on error
        mysqli_rollback($con);
        error_log("Drop request error: " . $e->getMessage());
        header("Location: ../pages/admin/admin_manual_enroll.php?student_id=$student_id&error=failed&msg=" . urlencode($e->getMessage()) . "&t=" . time());
        exit;
    }
} else {
    header("Location: ../pages/admin/admin_students.php");
    exit;
}
?>
