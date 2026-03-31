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
    if (count($parts) < 8) {
        $errors[] = "Invalid format: $line";
        continue;
    }
    
    $subject_code = trim($parts[0]);
    $subject_name = trim($parts[1]);
    $units = (int) trim($parts[2]);
    $lecture_hours = (float) trim($parts[3]);
    $lab_hours = (float) trim($parts[4]);
    $department = trim($parts[5]);
    $year_level = trim($parts[6]);
    $semester = trim($parts[7]);
    $prerequisite = isset($parts[8]) ? trim($parts[8]) : '';
    
    if (empty($subject_code) || empty($subject_name) || $units <= 0) {
        $errors[] = "Missing required fields: $line";
        continue;
    }
    
    // Check for duplicates
    $chk = mysqli_prepare($con, "SELECT subject_id FROM subjects WHERE subject_code = ?");
    mysqli_stmt_bind_param($chk, "s", $subject_code);
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
    
    $stmt = mysqli_prepare($con, "INSERT INTO subjects (subject_code, subject_name, units, lecture_hours, lab_hours, department, year_level, semester, prerequisite, status) VALUES (?,?,?,?,?,?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt, "ssiddsssss", $subject_code, $subject_name, $units, $lecture_hours, $lab_hours, $department, $year_level_val, $semester_val, $prerequisite, $status);
    
    if (mysqli_stmt_execute($stmt)) {
        $imported++;
    } else {
        $errors[] = "Failed to insert: $subject_code";
    }
}

if ($imported > 0) {
    header("Location: ../pages/admin/admin_subjects_batch_import.php?success=1&count=$imported");
} else {
    header("Location: ../pages/admin/admin_subjects_batch_import.php?error=import_failed");
}
die;
?>
