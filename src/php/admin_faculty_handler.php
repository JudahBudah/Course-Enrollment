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
        header("Location: /SoftDes/kevin/src/pages/admin/admin_faculty.php?error=missing_fields");
        die;
    }

    if ($action === 'add') {
        $plain_password = $employee_id;
        $password = password_hash($plain_password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($con, "INSERT INTO faculty (employee_id, first_name, middle_name, last_name, email, password, department, position, employment_status, status) VALUES (?,?,?,?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt, "ssssssssss", $employee_id, $first_name, $middle_name, $last_name, $email, $password, $department, $position, $employment_status, $status);
        if (!mysqli_stmt_execute($stmt)) {
            header("Location: /SoftDes/kevin/src/pages/admin/admin_faculty.php?error=insert_failed");
            die;
        }

        include_once __DIR__ . '/mailer.php';
        $full_name = trim("$first_name $last_name");
        $emp_label = ucfirst(str_replace('-', ' ', $employment_status));
        $subject = 'PLM Faculty Portal - Your Account Credentials';
        $body = "
        <div style='font-family:DM Sans,sans-serif;max-width:520px;margin:0 auto;background:#0d0a07;color:#F2F3F2;padding:2rem;border-radius:8px;'>
            <div style='text-align:center;margin-bottom:1.5rem;'>
                <img src='https://upload.wikimedia.org/wikipedia/en/thumb/6/6b/Pamantasan_ng_Lungsod_ng_Maynila_logo.png/200px-Pamantasan_ng_Lungsod_ng_Maynila_logo.png' width='60' style='border-radius:50%;'>
                <h2 style='font-family:Georgia,serif;color:#D4AF37;margin:0.5rem 0 0;'>PLM Faculty Portal</h2>
            </div>
            <p>Dear <strong>{$full_name}</strong>,</p>
            <p style='color:rgba(242,243,242,0.7);'>Your faculty account has been created. Below are your login credentials:</p>
            <div style='background:rgba(212,175,55,0.08);border:1px solid rgba(212,175,55,0.25);border-radius:6px;padding:1.25rem 1.5rem;margin:1.25rem 0;'>
                <table style='width:100%;border-collapse:collapse;font-size:0.95rem;'>
                    <tr><td style='padding:0.4rem 0;color:rgba(242,243,242,0.5);width:40%;'>Email</td><td style='color:#F2F3F2;'>{$email}</td></tr>
                    <tr><td style='padding:0.4rem 0;color:rgba(242,243,242,0.5);'>Password</td><td style='color:#D4AF37;font-weight:700;'>{$plain_password}</td></tr>
                    <tr><td style='padding:0.4rem 0;color:rgba(242,243,242,0.5);'>Position</td><td style='color:#F2F3F2;'>{$position}</td></tr>
                    <tr><td style='padding:0.4rem 0;color:rgba(242,243,242,0.5);'>Department</td><td style='color:#F2F3F2;'>{$department}</td></tr>
                    <tr><td style='padding:0.4rem 0;color:rgba(242,243,242,0.5);'>Employment</td><td style='color:#F2F3F2;'>{$emp_label}</td></tr>
                </table>
            </div>
            <p style='color:rgba(242,243,242,0.5);font-size:0.82rem;'>Please change your password after your first login.</p>
        </div>";
        mailer_send($email, $subject, $body, ['is_html' => true]);

        header("Location: /SoftDes/kevin/src/pages/admin/admin_faculty.php?success=added");
    } else {
        $faculty_id = (int) $_POST['faculty_id'];
        $stmt = mysqli_prepare($con, "UPDATE faculty SET employee_id=?, first_name=?, middle_name=?, last_name=?, email=?, department=?, position=?, employment_status=?, status=? WHERE faculty_id=?");
        mysqli_stmt_bind_param($stmt, "sssssssssi", $employee_id, $first_name, $middle_name, $last_name, $email, $department, $position, $employment_status, $status, $faculty_id);
        if (!mysqli_stmt_execute($stmt)) {
            header("Location: /SoftDes/kevin/src/pages/admin/admin_faculty.php?error=update_failed");
            die;
        }
        header("Location: /SoftDes/kevin/src/pages/admin/admin_faculty.php?success=updated");
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
    header("Location: /SoftDes/kevin/src/pages/admin/admin_faculty.php?success=deleted");
    die;
}

if ($action === 'assign_class') {
    $faculty_id = (int) $_POST['faculty_id'];
    $class_id   = (int) $_POST['class_id'];
    if (!$faculty_id || !$class_id) {
        header("Location: /SoftDes/kevin/src/pages/admin/admin_faculty.php?error=missing_fields");
        die;
    }
    $stmt = mysqli_prepare($con, "UPDATE classes SET faculty_id = ? WHERE class_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $faculty_id, $class_id);
    mysqli_stmt_execute($stmt);
    header("Location: /SoftDes/kevin/src/pages/admin/admin_faculty.php?success=assigned&faculty_id=$faculty_id");
    die;
}

if ($action === 'unassign_class') {
    $class_id = (int) $_POST['class_id'];
    $faculty_id = (int) $_POST['faculty_id'];
    $stmt = mysqli_prepare($con, "UPDATE classes SET faculty_id = NULL WHERE class_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $class_id);
    mysqli_stmt_execute($stmt);
    header("Location: /SoftDes/kevin/src/pages/admin/admin_faculty.php?success=unassigned&faculty_id=$faculty_id");
    die;
}

header("Location: /SoftDes/kevin/src/pages/admin/admin_faculty.php");
die;
?>
