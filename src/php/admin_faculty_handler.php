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
        log_activity($con, 'Added faculty', 'faculty', $first_name . ' ' . $last_name);
        header("Location: ../pages/admin/admin_faculty.php?success=added");
    } else {
        $faculty_id = (int) $_POST['faculty_id'];
        $stmt = mysqli_prepare($con, "UPDATE faculty SET employee_id=?, first_name=?, middle_name=?, last_name=?, email=?, department=?, position=?, employment_status=?, status=? WHERE faculty_id=?");
        mysqli_stmt_bind_param($stmt, "sssssssssi", $employee_id, $first_name, $middle_name, $last_name, $email, $department, $position, $employment_status, $status, $faculty_id);
        if (!mysqli_stmt_execute($stmt)) {
            header("Location: ../pages/admin/admin_faculty.php?error=update_failed");
            die;
        }
        log_activity($con, 'Updated faculty', 'faculty', $first_name . ' ' . $last_name);
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
    log_activity($con, 'Deleted faculty', 'faculty', 'Faculty ID ' . $faculty_id);
    header("Location: ../pages/admin/admin_faculty.php?success=deleted");
    die;
}

if ($action === 'assign_class') {
    $faculty_id = (int) $_POST['faculty_id'];
    $class_id   = (int) $_POST['class_id'];
    $force      = (int) ($_POST['force'] ?? 0);

    if (!$faculty_id || !$class_id) {
        header("Location: ../pages/admin/admin_faculty.php?error=missing_fields");
        die;
    }

    // Check if class already has a different faculty assigned
    $existing = mysqli_fetch_assoc(mysqli_query($con,
        "SELECT c.faculty_id, CONCAT(f.first_name,' ',f.last_name) as faculty_name
         FROM classes c LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
         WHERE c.class_id = $class_id"
    ));

    if ($existing && $existing['faculty_id'] && $existing['faculty_id'] != $faculty_id && !$force) {
        $name = urlencode($existing['faculty_name']);
        header("Location: ../pages/admin/admin_faculty.php?error=already_assigned&current_faculty=$name&class_id=$class_id&faculty_id=$faculty_id&view=assign");
        die;
    }

    $stmt = mysqli_prepare($con, "UPDATE classes SET faculty_id = ? WHERE class_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $faculty_id, $class_id);
    mysqli_stmt_execute($stmt);
    log_activity($con, 'Assigned faculty to class', 'faculty', 'Faculty ID ' . $faculty_id . ' → Class ID ' . $class_id);
    header("Location: ../pages/admin/admin_faculty.php?success=assigned&faculty_id=$faculty_id");
    die;
}

if ($action === 'unassign_class') {
    $class_id = (int) $_POST['class_id'];
    $faculty_id = (int) $_POST['faculty_id'];
    $stmt = mysqli_prepare($con, "UPDATE classes SET faculty_id = NULL WHERE class_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $class_id);
    mysqli_stmt_execute($stmt);
    log_activity($con, 'Unassigned faculty from class', 'faculty', 'Faculty ID ' . $faculty_id . ' from Class ID ' . $class_id);
    header("Location: ../pages/admin/admin_faculty.php?success=unassigned&faculty_id=$faculty_id");
    die;
}

header("Location: ../pages/admin/admin_faculty.php");
die;
?>
