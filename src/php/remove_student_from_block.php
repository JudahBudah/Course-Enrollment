<?php
session_start();
include("connection.php");
include("admin_functions.php");

$admin_data = check_admin_login($con);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $block_id   = (int)$_POST['block_id'];
    $student_id = (int)$_POST['student_id'];

    $block   = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM blocks WHERE block_id = $block_id"));
    $student = mysqli_fetch_assoc(mysqli_query($con, "SELECT block_id FROM students WHERE student_id = $student_id"));

    if (!$student || $student['block_id'] != $block_id) {
        header("Location: ../pages/admin/admin_block_students.php?block_id=$block_id&error=not_in_block");
        exit;
    }

    if (mysqli_query($con, "UPDATE students SET block_id = NULL WHERE student_id = $student_id")) {
        mysqli_query($con, "UPDATE blocks SET current_students = (SELECT COUNT(*) FROM students WHERE block_id = $block_id) WHERE block_id = $block_id");
        log_activity($con, 'Removed student from block', 'block',
            ($student ? 'Student ' . $student_id : 'Student ' . $student_id) . ' from Block ID ' . $block_id);

        $semester    = $block['school_year'];
        $sem_num     = ($block['semester'] === '1st') ? 1 : (($block['semester'] === '2nd') ? 2 : 0);
        $school_year = mysqli_real_escape_string($con, $block['school_year']);

        $block_subjects = mysqli_query($con, "SELECT class_id FROM block_subjects WHERE block_id = $block_id");
        while ($subject = mysqli_fetch_assoc($block_subjects)) {
            $class_id = (int)$subject['class_id'];
            mysqli_query($con, "DELETE FROM enrollments
                WHERE student_id = $student_id AND class_id = $class_id
                AND school_year = '$school_year' AND semester = $sem_num");
            // Recalculate class count from actual enrollments
            if (mysqli_affected_rows($con) > 0) {
                mysqli_query($con, "UPDATE classes SET enrolled_count = (
                    SELECT COUNT(*) FROM enrollments
                    WHERE class_id = $class_id AND status IN ('reserved','confirmed','ongoing')
                ) WHERE class_id = $class_id");
            }
        }

        header("Location: ../pages/admin/admin_block_students.php?block_id=$block_id&success=removed");
    } else {
        header("Location: ../pages/admin/admin_block_students.php?block_id=$block_id&error=failed");
    }
} else {
    header("Location: ../pages/admin/admin_blocks.php");
}
?>
