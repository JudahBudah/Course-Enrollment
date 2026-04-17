<?php
session_start();
include("connection.php");
include("admin_functions.php");

check_admin_login($con);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $block_id     = (int)$_POST['block_id'];
    $course       = mysqli_real_escape_string($con, $_POST['course']);
    $year_level   = (int)$_POST['year_level'];
    $regular_only = isset($_POST['regular_only']);

    $block = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM blocks WHERE block_id = $block_id"));
    if (!$block) {
        header("Location: ../pages/admin/admin_blocks.php?error=block_not_found");
        exit;
    }

    // Resolve both course_code and course_name so students stored with either are found
    $course_match = mysqli_fetch_assoc(mysqli_query($con,
        "SELECT course_code, course_name FROM courses
         WHERE course_code = '$course' OR course_name = '$course' LIMIT 1"
    ));
    $code_esc = mysqli_real_escape_string($con, $course_match['course_code'] ?? $course);
    $name_esc = mysqli_real_escape_string($con, $course_match['course_name'] ?? $course);

    $where = "(block_id IS NULL OR block_id = 0)
              AND (course = '$code_esc' OR course = '$name_esc')
              AND year_level = $year_level";
    if ($regular_only) {
        $where .= " AND (registration_status = 'Regular' OR registration_status IS NULL OR registration_status = '')";
    }

    $students_query = mysqli_query($con, "SELECT student_id FROM students WHERE $where");
    if (!$students_query || mysqli_num_rows($students_query) === 0) {
        header("Location: ../pages/admin/admin_block_students.php?block_id=$block_id&error=no_students");
        exit;
    }

    $assigned_count = 0;
    $skipped_count  = 0;
    $sem_num        = ($block['semester'] === '1st') ? 1 : (($block['semester'] === '2nd') ? 2 : 0);
    $school_year    = mysqli_real_escape_string($con, $block['school_year']);

    while ($student = mysqli_fetch_assoc($students_query)) {
        $student_id = (int)$student['student_id'];

        // Re-check capacity each iteration
        $cap = mysqli_fetch_assoc(mysqli_query($con, "SELECT current_students, max_students FROM blocks WHERE block_id = $block_id"));
        if ($cap['current_students'] >= $cap['max_students']) {
            $skipped_count++;
            continue;
        }

        if (!mysqli_query($con, "UPDATE students SET block_id = $block_id WHERE student_id = $student_id AND (block_id IS NULL OR block_id = 0)")) {
            $skipped_count++;
            continue;
        }
        if (mysqli_affected_rows($con) === 0) {
            $skipped_count++;
            continue;
        }

        mysqli_query($con, "UPDATE blocks SET current_students = current_students + 1 WHERE block_id = $block_id");
        $assigned_count++;

        // Enroll in block subjects
        $block_subjects = mysqli_query($con, "SELECT class_id FROM block_subjects WHERE block_id = $block_id");
        while ($subject = mysqli_fetch_assoc($block_subjects)) {
            $class_id = (int)$subject['class_id'];
            $ins = mysqli_query($con, "INSERT IGNORE INTO enrollments (student_id, class_id, school_year, semester, status)
                                       VALUES ($student_id, $class_id, '$school_year', $sem_num, 'confirmed')");
            if ($ins && mysqli_affected_rows($con) > 0) {
                mysqli_query($con, "UPDATE classes SET enrolled_count = enrolled_count + 1 WHERE class_id = $class_id");
            }
        }
    }

    if ($assigned_count > 0) {
        header("Location: ../pages/admin/admin_block_students.php?block_id=$block_id&success=batch&count=$assigned_count");
    } else {
        header("Location: ../pages/admin/admin_block_students.php?block_id=$block_id&error=no_students");
    }
} else {
    header("Location: ../pages/admin/admin_blocks.php");
}
?>
