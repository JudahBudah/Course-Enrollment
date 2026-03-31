<?php
session_start();
include("connection.php");
include("admin_functions.php");

check_admin_login($con);

$action = $_POST['action'] ?? '';

if ($action === 'add' || $action === 'edit') {
    $subject_id = (int) $_POST['subject_id'];
    $faculty_id = !empty($_POST['faculty_id']) ? (int) $_POST['faculty_id'] : null;
    $section = trim($_POST['section']);
    $school_year = trim($_POST['school_year']);
    $semester = $_POST['semester'];
    $schedule_day = trim($_POST['schedule_day']);
    $schedule_time = trim($_POST['schedule_time']);
    $room = trim($_POST['room']);
    $max_slots = (int) $_POST['max_slots'];
    $status = $_POST['status'];

    if (!$subject_id || !$section || !$school_year || !$semester || !$max_slots) {
        header("Location: ../pages/admin/admin_classes.php?error=missing_fields");
        die;
    }

    if ($action === 'add') {
        $stmt = mysqli_prepare($con, "INSERT INTO classes (subject_id, faculty_id, section, school_year, semester, schedule_day, schedule_time, room, max_slots, enrolled_count, status) VALUES (?,?,?,?,?,?,?,?,?,0,?)");
        mysqli_stmt_bind_param($stmt, "iissssssis", $subject_id, $faculty_id, $section, $school_year, $semester, $schedule_day, $schedule_time, $room, $max_slots, $status);

        if (!mysqli_stmt_execute($stmt)) {
            header("Location: ../pages/admin/admin_classes.php?error=insert_failed");
            die;
        }
        header("Location: ../pages/admin/admin_classes.php?success=added");

    } else {
        $class_id = (int) $_POST['class_id'];

        $stmt = mysqli_prepare($con, "UPDATE classes SET subject_id=?, faculty_id=?, section=?, school_year=?, semester=?, schedule_day=?, schedule_time=?, room=?, max_slots=?, status=? WHERE class_id=?");
        mysqli_stmt_bind_param($stmt, "iissssssisi", $subject_id, $faculty_id, $section, $school_year, $semester, $schedule_day, $schedule_time, $room, $max_slots, $status, $class_id);

        if (!mysqli_stmt_execute($stmt)) {
            header("Location: ../pages/admin/admin_classes.php?error=update_failed");
            die;
        }
        header("Location: ../pages/admin/admin_classes.php?success=updated");
    }
    die;
}

if ($action === 'delete') {
    $class_id = (int) $_POST['class_id'];

    // Check if class has enrollments
    $chk = mysqli_prepare($con, "SELECT enrollment_id FROM enrollments WHERE class_id = ? LIMIT 1");
    mysqli_stmt_bind_param($chk, "i", $class_id);
    mysqli_stmt_execute($chk);
    mysqli_stmt_store_result($chk);
    if (mysqli_stmt_num_rows($chk) > 0) {
        header("Location: ../pages/admin/admin_classes.php?error=has_enrollments");
        die;
    }

    $stmt = mysqli_prepare($con, "DELETE FROM classes WHERE class_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $class_id);
    mysqli_stmt_execute($stmt);
    header("Location: ../pages/admin/admin_classes.php?success=deleted");
    die;
}

if ($action === 'toggle_status') {
    $class_id = (int) $_POST['class_id'];
    $new_status = $_POST['new_status'];
    $stmt = mysqli_prepare($con, "UPDATE classes SET status = ? WHERE class_id = ?");
    mysqli_stmt_bind_param($stmt, "si", $new_status, $class_id);
    mysqli_stmt_execute($stmt);
    header("Location: ../pages/admin/admin_classes.php?success=updated");
    die;
}

header("Location: ../pages/admin/admin_classes.php");
die;
?>
