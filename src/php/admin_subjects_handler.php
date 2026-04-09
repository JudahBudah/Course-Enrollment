<?php
session_start();
include("connection.php");
include("admin_functions.php");

check_admin_login($con);

$action = $_POST['action'] ?? '';

if ($action === 'add' || $action === 'edit') {
    $subject_code   = trim($_POST['subject_code']);
    $subject_name   = trim($_POST['subject_name']);
    $description    = trim($_POST['description']);
    $units          = (int) $_POST['units'];
    $lecture_hours  = (float) $_POST['lecture_hours'];
    $lab_hours      = (float) $_POST['lab_hours'];
    $course_id      = $_POST['course_id'] !== '' ? (int) $_POST['course_id'] : null;
    $department     = trim($_POST['department']);
    $year_level     = $_POST['year_level'] !== '' ? $_POST['year_level'] : null;
    $semester       = $_POST['semester'] !== '' ? $_POST['semester'] : null;
    $prerequisite   = trim($_POST['prerequisite']);
    $status         = $_POST['status'];

    if (!$subject_code || !$subject_name || !$units) {
        header("Location: ../pages/admin/admin_subjects.php?error=missing_fields");
        die;
    }

    if ($action === 'add') {
        // Check duplicate code + course combination
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
            header("Location: ../pages/admin/admin_subjects.php?error=duplicate_code");
            die;
        }

        $stmt = mysqli_prepare($con, "INSERT INTO subjects (subject_code, subject_name, description, units, lecture_hours, lab_hours, course_id, department, year_level, semester, prerequisite, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt, "sssiddisssss", $subject_code, $subject_name, $description, $units, $lecture_hours, $lab_hours, $course_id, $department, $year_level, $semester, $prerequisite, $status);

        if (!mysqli_stmt_execute($stmt)) {
            header("Location: ../pages/admin/admin_subjects.php?error=insert_failed");
            die;
        }
        header("Location: ../pages/admin/admin_subjects.php?success=added");

    } else {
        $subject_id = (int) $_POST['subject_id'];

        // Check duplicate code + course combination excluding self
        if ($course_id !== null) {
            $chk = mysqli_prepare($con, "SELECT subject_id FROM subjects WHERE subject_code = ? AND course_id = ? AND subject_id != ?");
            mysqli_stmt_bind_param($chk, "sii", $subject_code, $course_id, $subject_id);
        } else {
            $chk = mysqli_prepare($con, "SELECT subject_id FROM subjects WHERE subject_code = ? AND course_id IS NULL AND subject_id != ?");
            mysqli_stmt_bind_param($chk, "si", $subject_code, $subject_id);
        }
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);
        if (mysqli_stmt_num_rows($chk) > 0) {
            header("Location: ../pages/admin/admin_subjects.php?error=duplicate_code");
            die;
        }

        $stmt = mysqli_prepare($con, "UPDATE subjects SET subject_code=?, subject_name=?, description=?, units=?, lecture_hours=?, lab_hours=?, course_id=?, department=?, year_level=?, semester=?, prerequisite=?, status=? WHERE subject_id=?");
        mysqli_stmt_bind_param($stmt, "sssiddisssssi", $subject_code, $subject_name, $description, $units, $lecture_hours, $lab_hours, $course_id, $department, $year_level, $semester, $prerequisite, $status, $subject_id);

        if (!mysqli_stmt_execute($stmt)) {
            header("Location: ../pages/admin/admin_subjects.php?error=update_failed");
            die;
        }
        header("Location: ../pages/admin/admin_subjects.php?success=updated");
    }
    die;
}

if ($action === 'delete') {
    $subject_id = (int) $_POST['subject_id'];

    // Check if subject is used in any class
    $chk = mysqli_prepare($con, "SELECT class_id FROM classes WHERE subject_id = ? LIMIT 1");
    mysqli_stmt_bind_param($chk, "i", $subject_id);
    mysqli_stmt_execute($chk);
    mysqli_stmt_store_result($chk);
    if (mysqli_stmt_num_rows($chk) > 0) {
        header("Location: ../pages/admin/admin_subjects.php?error=in_use");
        die;
    }

    $stmt = mysqli_prepare($con, "DELETE FROM subjects WHERE subject_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $subject_id);
    mysqli_stmt_execute($stmt);
    header("Location: ../pages/admin/admin_subjects.php?success=deleted");
    die;
}

if ($action === 'toggle_status') {
    $subject_id = (int) $_POST['subject_id'];
    $new_status = $_POST['new_status'];
    $stmt = mysqli_prepare($con, "UPDATE subjects SET status = ? WHERE subject_id = ?");
    mysqli_stmt_bind_param($stmt, "si", $new_status, $subject_id);
    mysqli_stmt_execute($stmt);
    header("Location: ../pages/admin/admin_subjects.php?success=updated");
    die;
}

header("Location: ../pages/admin/admin_subjects.php");
die;
