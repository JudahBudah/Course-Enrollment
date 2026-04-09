<?php
session_start();
include("connection.php");
include("admin_functions.php");

$admin_data = check_admin_login($con);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $block_id = (int)$_POST['block_id'];
    $student_id = (int)$_POST['student_id'];

    // Get block info
    $block_query = mysqli_query($con, "SELECT * FROM blocks WHERE block_id = $block_id");
    $block = mysqli_fetch_assoc($block_query);

    // Verify student is actually in this block
    $student_check = mysqli_query($con, "SELECT block_id FROM students WHERE student_id = $student_id");
    $student = mysqli_fetch_assoc($student_check);
    
    if (!$student || $student['block_id'] != $block_id) {
        header("Location: ../pages/admin/admin_block_students.php?block_id=$block_id&error=not_in_block");
        exit;
    }

    // Remove student from block
    $update_student = "UPDATE students SET block_id = NULL WHERE student_id = $student_id";
    
    if (mysqli_query($con, $update_student)) {
        // Update block student count (prevent negative)
        $update_block = "UPDATE blocks SET current_students = GREATEST(0, current_students - 1) WHERE block_id = $block_id";
        mysqli_query($con, $update_block);

        // Remove student enrollments from block subjects
        $block_subjects = mysqli_query($con, "SELECT class_id FROM block_subjects WHERE block_id = $block_id");
        $school_year = $block['school_year'];
        $semester = ($block['semester'] === '1st') ? 1 : (($block['semester'] === '2nd') ? 2 : 0);

        while ($subject = mysqli_fetch_assoc($block_subjects)) {
            $class_id = $subject['class_id'];
            mysqli_query($con, "DELETE FROM enrollments 
                               WHERE student_id = $student_id 
                               AND class_id = $class_id 
                               AND school_year = '$school_year' 
                               AND semester = $semester");
            
            // Update class enrolled count (prevent negative)
            mysqli_query($con, "UPDATE classes SET enrolled_count = GREATEST(0, enrolled_count - 1) WHERE class_id = $class_id");
        }

        header("Location: ../pages/admin/admin_block_students.php?block_id=$block_id&success=removed");
    } else {
        header("Location: ../pages/admin/admin_block_students.php?block_id=$block_id&error=failed");
    }
} else {
    header("Location: ../pages/admin/admin_blocks.php");
}
?>
