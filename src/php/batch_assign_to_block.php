<?php
session_start();
include("connection.php");
include("admin_functions.php");

check_admin_login($con);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $block_id = (int)$_POST['block_id'];
    $course = mysqli_real_escape_string($con, $_POST['course']);
    $year_level = (int)$_POST['year_level'];
    $regular_only = isset($_POST['regular_only']);

    // Get block info
    $block_query = mysqli_query($con, "SELECT * FROM blocks WHERE block_id = $block_id");
    $block = mysqli_fetch_assoc($block_query);

    if (!$block) {
        header("Location: ../pages/admin/admin_blocks.php?error=block_not_found");
        exit;
    }

    // Build query for unassigned students
    $where = "WHERE (block_id IS NULL OR block_id = 0) AND course = '$course' AND year_level = $year_level";
    if ($regular_only) {
        $where .= " AND registration_status = 'Regular'";
    }

    $students_query = mysqli_query($con, "SELECT student_id FROM students $where");
    
    $assigned_count = 0;
    $skipped_count = 0;
    $semester = ($block['semester'] === '1st') ? 1 : (($block['semester'] === '2nd') ? 2 : 0);

    while ($student = mysqli_fetch_assoc($students_query)) {
        $student_id = $student['student_id'];
        
        // Check capacity
        if ($block['current_students'] + $assigned_count >= $block['max_students']) {
            $skipped_count++;
            continue;
        }

        // Assign student to block
        $update_student = "UPDATE students SET block_id = $block_id WHERE student_id = $student_id";
        
        if (mysqli_query($con, $update_student)) {
            $assigned_count++;
            
            // Reserve in block subjects (student must confirm)
            $block_subjects = mysqli_query($con, "SELECT class_id FROM block_subjects WHERE block_id = $block_id");
            while ($subject = mysqli_fetch_assoc($block_subjects)) {
                $class_id = $subject['class_id'];
                mysqli_query($con, "INSERT IGNORE INTO enrollments (student_id, class_id, school_year, semester, status) 
                                   VALUES ($student_id, $class_id, '{$block['school_year']}', $semester, 'reserved')");
            }
        }
    }

    // Update block student count
    if ($assigned_count > 0) {
        mysqli_query($con, "UPDATE blocks SET current_students = current_students + $assigned_count WHERE block_id = $block_id");
    }

    if ($assigned_count > 0) {
        $message = "Assigned $assigned_count student" . ($assigned_count > 1 ? 's' : '') . " to block";
        if ($skipped_count > 0) {
            $message .= " ($skipped_count skipped due to capacity)";
        }
        header("Location: ../pages/admin/admin_block_students.php?block_id=$block_id&success=batch&count=$assigned_count");
    } else {
        header("Location: ../pages/admin/admin_block_students.php?block_id=$block_id&error=no_students");
    }
} else {
    header("Location: ../pages/admin/admin_blocks.php");
}
?>
