<?php
session_start();
include("connection.php");
include("admin_functions.php");

check_admin_login($con);

$action = $_POST['action'] ?? '';

if ($action === 'add' || $action === 'edit') {
    $employee_id  = trim($_POST['employee_id']);
    $first_name   = trim($_POST['first_name']);
    $last_name    = trim($_POST['last_name']);
    $middle_name  = trim($_POST['middle_name'] ?? '');
    $email        = trim($_POST['email']);
    $department   = trim($_POST['department'] ?? '');
    $position     = trim($_POST['position'] ?? '');
    $employment_status = $_POST['employment_status'] ?? 'full-time';
    $status       = $_POST['status'] ?? 'active';

    if (!$employee_id || !$first_name || !$last_name || !$email) {
        header("Location: ../pages/admin/admin_faculty.php?error=missing_fields");
        die;
    }

    if ($action === 'add') {
        $password = password_hash($employee_id, PASSWORD_DEFAULT); // default password = employee_id
        $stmt = mysqli_prepare($con, "INSERT INTO faculty (employee_id, first_name, middle_name, last_name, email, password, department, position, employment_status, status) VALUES (?,?,?,?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt, "ssssssssss", $employee_id, $first_name, $middle_name, $last_name, $email, $password, $department, $position, $employment_status, $status);
        if (!mysqli_stmt_execute($stmt)) {
            header("Location: ../pages/admin/admin_faculty.php?error=insert_failed");
            die;
        }
        header("Location: ../pages/admin/admin_faculty.php?success=added");
    } else {
        $faculty_id = (int) $_POST['faculty_id'];
        $stmt = mysqli_prepare($con, "UPDATE faculty SET employee_id=?, first_name=?, middle_name=?, last_name=?, email=?, department=?, position=?, employment_status=?, status=? WHERE faculty_id=?");
        mysqli_stmt_bind_param($stmt, "sssssssssi", $employee_id, $first_name, $middle_name, $last_name, $email, $department, $position, $employment_status, $status, $faculty_id);
        if (!mysqli_stmt_execute($stmt)) {
            header("Location: ../pages/admin/admin_faculty.php?error=update_failed");
            die;
        }
        header("Location: ../pages/admin/admin_faculty.php?success=updated");
    }
    die;
}

if ($action === 'delete') {
    $faculty_id = (int) $_POST['faculty_id'];
    // Unassign from classes first
    mysqli_query($con, "UPDATE classes SET faculty_id = NULL WHERE faculty_id = $faculty_id");
    $stmt = mysqli_prepare($con, "DELETE FROM faculty WHERE faculty_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $faculty_id);
    mysqli_stmt_execute($stmt);
    header("Location: ../pages/admin/admin_faculty.php?success=deleted");
    die;
}

if ($action === 'assign_class') {
    $faculty_id = (int) $_POST['faculty_id'];
    $class_id   = (int) $_POST['class_id'];
    if (!$faculty_id || !$class_id) {
        header("Location: ../pages/admin/admin_faculty.php?error=missing_fields");
        die;
    }
    $stmt = mysqli_prepare($con, "UPDATE classes SET faculty_id = ? WHERE class_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $faculty_id, $class_id);
    mysqli_stmt_execute($stmt);
    header("Location: ../pages/admin/admin_faculty.php?success=assigned&faculty_id=$faculty_id");
    die;
}

if ($action === 'unassign_class') {
    $class_id = (int) $_POST['class_id'];
    $faculty_id = (int) $_POST['faculty_id'];
    $stmt = mysqli_prepare($con, "UPDATE classes SET faculty_id = NULL WHERE class_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $class_id);
    mysqli_stmt_execute($stmt);
    header("Location: ../pages/admin/admin_faculty.php?success=unassigned&faculty_id=$faculty_id");
    die;
}

header("Location: ../pages/admin/admin_faculty.php");
die;
?>
