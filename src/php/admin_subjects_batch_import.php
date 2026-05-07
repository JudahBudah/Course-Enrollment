<?php
session_start();
include("connection.php");
include("admin_functions.php");

check_admin_login($con);

// ── CSV Template Download ─────────────────────────────────────
if (isset($_GET['download_template'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="subjects_template.csv"');
    echo "course_code,subject_code,subject_name,units,lecture_hours,lab_hours,department,year_level,semester,prerequisite\r\n";
    echo "BSIT,ICC 0101,Introduction to Computing,3,2,1,Information Technology,1,1st,\r\n";
    echo "BSIT,ICC 0102,Computer Programming 1,3,2,1,Information Technology,1,1st,ICC 0101\r\n";
    echo "BSCpE,CPE 0111,Computer Engineering as a Discipline,1,1,0,Computer Engineering,1,1st,\r\n";
    die;
}

$mode            = $_POST['mode']            ?? 'paste';
$skip_duplicates = isset($_POST['skip_duplicates']);
$set_active      = isset($_POST['set_active']);

// ── Parse rows depending on mode ─────────────────────────────
$rows = [];

if ($mode === 'csv') {
    if (empty($_FILES['csv_file']['tmp_name']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        header("Location: ../pages/admin/admin_subjects_batch_import.php?tab=csv&error=no_data"); die;
    }
    $ext = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
    if ($ext !== 'csv') {
        header("Location: ../pages/admin/admin_subjects_batch_import.php?tab=csv&error=csv_invalid"); die;
    }

    $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
    $header = fgetcsv($handle); // skip header row
    if (!$header) {
        header("Location: ../pages/admin/admin_subjects_batch_import.php?tab=csv&error=csv_invalid"); die;
    }
    while (($cols = fgetcsv($handle)) !== false) {
        if (count($cols) < 9) continue;
        $rows[] = array_map('trim', $cols);
    }
    fclose($handle);

} else {
    // paste mode — pipe-delimited
    $subjects_data = trim($_POST['subjects_data'] ?? '');
    if (empty($subjects_data)) {
        header("Location: ../pages/admin/admin_subjects_batch_import.php?tab=paste&error=no_data"); die;
    }
    foreach (explode("\n", $subjects_data) as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        $parts = explode("|", $line);
        if (count($parts) < 9) continue;
        $rows[] = array_map('trim', $parts);
    }
}

if (empty($rows)) {
    $tab = $mode === 'csv' ? 'csv' : 'paste';
    header("Location: ../pages/admin/admin_subjects_batch_import.php?tab=$tab&error=no_data"); die;
}

// ── Insert rows ───────────────────────────────────────────────
$imported = 0;
$skipped  = 0;

foreach ($rows as $parts) {
    $course_code   = $parts[0];
    $subject_code  = $parts[1];
    $subject_name  = $parts[2];
    $units         = (int)$parts[3];
    $lecture_hours = (float)$parts[4];
    $lab_hours     = (float)$parts[5];
    $department    = $parts[6];
    $year_level    = $parts[7];
    $semester      = $parts[8];
    $prerequisite  = $parts[9] ?? '';

    if (empty($subject_code) || empty($subject_name)) continue;

    // Resolve course_id
    $course_id = null;
    if (!empty($course_code)) {
        $cq = mysqli_prepare($con, "SELECT course_id FROM courses WHERE course_code = ? LIMIT 1");
        mysqli_stmt_bind_param($cq, "s", $course_code);
        mysqli_stmt_execute($cq);
        $cr = mysqli_fetch_assoc(mysqli_stmt_get_result($cq));
        if ($cr) $course_id = $cr['course_id'];
    }

    // Duplicate check
    if ($course_id !== null) {
        $chk = mysqli_prepare($con, "SELECT subject_id FROM subjects WHERE subject_code = ? AND course_id = ?");
        mysqli_stmt_bind_param($chk, "si", $subject_code, $course_id);
    } else {
        $chk = mysqli_prepare($con, "SELECT subject_id FROM subjects WHERE subject_code = ? AND course_id IS NULL");
        mysqli_stmt_bind_param($chk, "s", $subject_code);
    }
    mysqli_stmt_execute($chk);
    mysqli_stmt_store_result($chk);
    if (mysqli_stmt_num_rows($chk) > 0) { $skipped++; continue; }

    $status        = $set_active ? 'active' : 'inactive';
    $year_level_val = ($year_level !== '' && is_numeric($year_level)) ? $year_level : null;
    $semester_val   = !empty($semester) ? $semester : null;

    $stmt = mysqli_prepare($con, "INSERT INTO subjects (course_id, subject_code, subject_name, units, lecture_hours, lab_hours, department, year_level, semester, prerequisite, status) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt, "issiddsssss", $course_id, $subject_code, $subject_name, $units, $lecture_hours, $lab_hours, $department, $year_level_val, $semester_val, $prerequisite, $status);
    if (mysqli_stmt_execute($stmt)) $imported++;
}

$tab = $mode === 'csv' ? 'csv' : 'paste';

if ($imported > 0) {
    log_activity($con, 'Batch created subjects', 'subject', $imported . ' created' . ($skipped > 0 ? ', ' . $skipped . ' skipped' : ''));
    $qs = "success=1&count=$imported&tab=$tab";
    if ($skipped > 0) $qs .= "&skipped=$skipped";
    header("Location: ../pages/admin/admin_subjects_batch_import.php?$qs"); die;
}

if ($skipped > 0) {
    header("Location: ../pages/admin/admin_subjects_batch_import.php?tab=$tab&error=all_skipped&skipped=$skipped"); die;
}
header("Location: ../pages/admin/admin_subjects_batch_import.php?tab=$tab&error=import_failed"); die;
?>
