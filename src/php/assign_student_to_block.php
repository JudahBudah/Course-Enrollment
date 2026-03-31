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

        // Reserve student in all block subjects (student must confirm)
        while ($subject = mysqli_fetch_assoc($block_subjects)) {
            $class_id = $subject['class_id'];
            mysqli_query($con, "INSERT IGNORE INTO enrollments (student_id, class_id, school_year, semester, status) 
                               VALUES ($student_id, $class_id, '$school_year', $semester, 'reserved')");
        }

        header("Location: ../pages/admin/admin_block_students.php?block_id=$block_id&success=assigned");
    } else {
        header("Location: ../pages/admin/admin_block_students.php?block_id=$block_id&error=failed");
    }
} else {
    header("Location: ../pages/admin/admin_blocks.php");
}
?>
