<?php
session_start();
include("connection.php");
include("admin_functions.php");

$admin_data = check_admin_login($con);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $block_id = (int)$_POST['block_id'];
    $student_id = (int)$_POST['student_id'];

    // Get block info to check capacity
    $block_query = mysqli_query($con, "SELECT * FROM blocks WHERE block_id = $block_id");
    $block = mysqli_fetch_assoc($block_query);

    if (!$block) {
        header("Location: ../pages/admin/admin_block_students.php?block_id=$block_id&error=block_not_found");
        exit;
    }

    if ($block['current_students'] >= $block['max_students']) {
        header("Location: ../pages/admin/admin_block_students.php?block_id=$block_id&error=full");
        exit;
    }

    // Assign student to block
    $update_student = "UPDATE students SET block_id = $block_id WHERE student_id = $student_id";
    
    if (mysqli_query($con, $update_student)) {
        // Update block student count
        $update_block = "UPDATE blocks SET current_students = current_students + 1 WHERE block_id = $block_id";
        mysqli_query($con, $update_block);

        // Get block subjects and enroll student
        $school_year = mysqli_real_escape_string($con, $block['school_year']);
        $semester = mysqli_real_escape_string($con, $block['semester']);
        
        $block_subjects = mysqli_query($con, "
            SELECT bs.class_id 
            FROM block_subjects bs
            WHERE bs.block_id = $block_id
        ");

        while ($subject = mysqli_fetch_assoc($block_subjects)) {
            $class_id = (int)$subject['class_id'];
            $insert_result = mysqli_query($con, "INSERT IGNORE INTO enrollments (student_id, class_id, school_year, semester, status) 
                               VALUES ($student_id, $class_id, '$school_year', '$semester', 'confirmed')");
            
            // Update class enrolled count only if enrollment was actually inserted
            if ($insert_result && mysqli_affected_rows($con) > 0) {
                mysqli_query($con, "UPDATE classes SET enrolled_count = enrolled_count + 1 WHERE class_id = $class_id");
            }
        }

        header("Location: ../pages/admin/admin_block_students.php?block_id=$block_id&success=assigned");
    } else {
        header("Location: ../pages/admin/admin_block_students.php?block_id=$block_id&error=failed");
    }
} else {
    header("Location: ../pages/admin/admin_blocks.php");
}
?>
