<?php
session_start();
include("connection.php");
include("admin_functions.php");

check_admin_login($con);

$subjects_data = trim($_POST['subjects_data'] ?? '');
$skip_duplicates = isset($_POST['skip_duplicates']);
$set_active = isset($_POST['set_active']);

if (empty($subjects_data)) {
    header("Location: ../pages/admin/admin_subjects_batch_import.php?error=no_data");
    die;
}

$lines = explode("\n", $subjects_data);
$imported = 0;
$skipped = 0;
$errors = [];

foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line)) continue;
    
    $parts = explode("|", $line);
    if (count($parts) < 9) {
        $errors[] = "Invalid format: $line";
        continue;
    }
    
    $course_code = trim($parts[0]);
    $subject_code = trim($parts[1]);
    $subject_name = trim($parts[2]);
    $units = (int) trim($parts[3]);
    $lecture_hours = (float) trim($parts[4]);
    $lab_hours = (float) trim($parts[5]);
    $department = trim($parts[6]);
    $year_level = trim($parts[7]);
    $semester = trim($parts[8]);
    $prerequisite = isset($parts[9]) ? trim($parts[9]) : '';
    
    if (empty($course_code) || empty($subject_code) || empty($subject_name)) {
        $errors[] = "Missing required fields: $line";
        continue;
    }
    
    // Get course_id from course_code
    $course_id = null;
    if (!empty($course_code)) {
        $course_query = mysqli_prepare($con, "SELECT course_id FROM courses WHERE course_code = ? LIMIT 1");
        mysqli_stmt_bind_param($course_query, "s", $course_code);
        mysqli_stmt_execute($course_query);
        $course_result = mysqli_stmt_get_result($course_query);
        if ($course_row = mysqli_fetch_assoc($course_result)) {
            $course_id = $course_row['course_id'];
        }
    }
    
    // Check for duplicates (subject_code + course_id combination)
    if ($course_id !== null) {
        $chk = mysqli_prepare($con, "SELECT subject_id FROM subjects WHERE subject_code = ? AND course_id = ?");
        mysqli_stmt_bind_param($chk, "si", $subject_code, $course_id);
    } else {
        $chk = mysqli_prepare($con, "SELECT subject_id FROM subjects WHERE subject_code = ? AND course_id IS NULL");
        mysqli_stmt_bind_param($chk, "s", $subject_code);
    }
    mysqli_stmt_execute($chk);
    mysqli_stmt_store_result($chk);
    
    if (mysqli_stmt_num_rows($chk) > 0) {
        if ($skip_duplicates) {
            $skipped++;
            continue;
        } else {
            $errors[] = "Duplicate code: $subject_code";
            continue;
        }
    }
    
    $status = $set_active ? 'active' : 'inactive';
    $year_level_val = ($year_level !== '' && is_numeric($year_level)) ? $year_level : null;
    $semester_val = !empty($semester) ? $semester : null;
    
    $stmt = mysqli_prepare($con, "INSERT INTO subjects (course_id, subject_code, subject_name, units, lecture_hours, lab_hours, department, year_level, semester, prerequisite, status) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt, "issiddsssss", $course_id, $subject_code, $subject_name, $units, $lecture_hours, $lab_hours, $department, $year_level_val, $semester_val, $prerequisite, $status);
    
    if (mysqli_stmt_execute($stmt)) {
        $imported++;
    } else {
        $errors[] = "Failed to insert: $subject_code";
    }
}

if ($imported > 0) {
    log_activity($con, 'Batch imported subjects', 'subject', $imported . ' imported' . ($skipped > 0 ? ', ' . $skipped . ' skipped' : ''));
    $msg = "success=1&count=$imported";
    if ($skipped > 0) $msg .= "&skipped=$skipped";
    header("Location: ../pages/admin/admin_subjects_batch_import.php?$msg");
} else {
    if ($skipped > 0) {
        header("Location: ../pages/admin/admin_subjects_batch_import.php?error=all_skipped&skipped=$skipped");
    } else {
        header("Location: ../pages/admin/admin_subjects_batch_import.php?error=import_failed");
    }
}
die;
?>
