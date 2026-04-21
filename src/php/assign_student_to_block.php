<?php
session_start();
include("connection.php");
include("admin_functions.php");

$admin_data = check_admin_login($con);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $block_id   = (int)$_POST['block_id'];
    $student_id = (int)$_POST['student_id'];

    $block = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM blocks WHERE block_id = $block_id"));

    if (!$block) {
        header("Location: ../pages/admin/admin_block_students.php?block_id=$block_id&error=block_not_found");
        exit;
    }

    if ($block['current_students'] >= $block['max_students']) {
        header("Location: ../pages/admin/admin_block_students.php?block_id=$block_id&error=full");
        exit;
    }

    if (mysqli_query($con, "UPDATE students SET block_id = $block_id WHERE student_id = $student_id AND (block_id IS NULL OR block_id = 0)")) {
        if (mysqli_affected_rows($con) === 0) {
            header("Location: ../pages/admin/admin_block_students.php?block_id=$block_id&error=failed");
            exit;
        }

        mysqli_query($con, "UPDATE blocks SET current_students = (SELECT COUNT(*) FROM students WHERE block_id = $block_id) WHERE block_id = $block_id");

        $school_year = mysqli_real_escape_string($con, $block['school_year']);
        $semester    = mysqli_real_escape_string($con, $block['semester']);
        $sem_num     = ($block['semester'] === '1st') ? 1 : (($block['semester'] === '2nd') ? 2 : 0);

        $block_subjects = mysqli_query($con, "SELECT class_id FROM block_subjects WHERE block_id = $block_id");
        while ($subject = mysqli_fetch_assoc($block_subjects)) {
            $class_id = (int)$subject['class_id'];
            $ins = mysqli_query($con, "INSERT IGNORE INTO enrollments (student_id, class_id, school_year, semester, status)
                               VALUES ($student_id, $class_id, '$school_year', $sem_num, 'confirmed')");
            if ($ins && mysqli_affected_rows($con) > 0) {
                mysqli_query($con, "UPDATE classes SET enrolled_count = enrolled_count + 1 WHERE class_id = $class_id");
            }
        }

        $st = mysqli_fetch_assoc(mysqli_query($con, "SELECT first_name, last_name FROM students WHERE student_id = $student_id"));
        log_activity($con, 'Assigned student to block', 'block',
            ($st ? $st['first_name'] . ' ' . $st['last_name'] : 'Student ' . $student_id) . ' → ' . $block['block_name']);
        header("Location: ../pages/admin/admin_block_students.php?block_id=$block_id&success=assigned");
    } else {
        header("Location: ../pages/admin/admin_block_students.php?block_id=$block_id&error=failed");
    }
} else {
    header("Location: ../pages/admin/admin_blocks.php");
}
?>
