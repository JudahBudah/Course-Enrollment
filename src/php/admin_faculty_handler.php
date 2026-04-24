<?php
session_start();
include("connection.php");
include("admin_functions.php");
include("mailer.php");

check_admin_login($con);

// Ensure column exists (compatible with MySQL 5.x)
$_cols = mysqli_query($con, "SHOW COLUMNS FROM faculty LIKE 'must_change_password'");
if (mysqli_num_rows($_cols) === 0) {
    mysqli_query($con, "ALTER TABLE faculty ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0");
}

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
        // Check for duplicate employee_id or email
        $chk = mysqli_prepare($con, "SELECT faculty_id FROM faculty WHERE employee_id = ? OR email = ? LIMIT 1");
        mysqli_stmt_bind_param($chk, "ss", $employee_id, $email);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);
        if (mysqli_stmt_num_rows($chk) > 0) {
            mysqli_stmt_close($chk);
            header("Location: ../pages/admin/admin_faculty.php?error=duplicate");
            die;
        }
        mysqli_stmt_close($chk);

        $password = password_hash($employee_id, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($con, "INSERT INTO faculty (employee_id, first_name, middle_name, last_name, email, password, department, position, employment_status, status, must_change_password) VALUES (?,?,?,?,?,?,?,?,?,?,1)");
        mysqli_stmt_bind_param($stmt, "ssssssssss", $employee_id, $first_name, $middle_name, $last_name, $email, $password, $department, $position, $employment_status, $status);
        if (!mysqli_stmt_execute($stmt)) {
            header("Location: ../pages/admin/admin_faculty.php?error=insert_failed");
            die;
        }
        log_activity($con, 'Added faculty', 'faculty', $first_name . ' ' . $last_name);

        $display_name = htmlspecialchars($first_name . ' ' . $last_name);
        $subject = 'PLM Faculty Portal - Your Account Credentials';
        $body = "<div style='font-family:DM Sans,sans-serif;max-width:480px;margin:0 auto;background:#0d0a07;color:#F2F3F2;padding:2rem;border-radius:8px;'>
            <div style='text-align:center;margin-bottom:1.5rem;'>
                <img src='https://upload.wikimedia.org/wikipedia/en/thumb/6/6b/Pamantasan_ng_Lungsod_ng_Maynila_logo.png/200px-Pamantasan_ng_Lungsod_ng_Maynila_logo.png' width='60' style='border-radius:50%;'>
                <h2 style='font-family:Georgia,serif;color:#D4AF37;margin:0.5rem 0 0;'>PLM Faculty Portal</h2>
            </div>
            <p style='margin-bottom:0.5rem;'>Hello, <strong>{$display_name}</strong>,</p>
            <p style='margin-bottom:1.5rem;color:rgba(242,243,242,0.7);'>Your faculty account has been created. Use the credentials below to sign in.</p>
            <div style='background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:6px;padding:1.25rem;margin-bottom:1.5rem;'>
                <p style='margin:0 0 0.5rem;'><span style='color:rgba(242,243,242,0.5);'>Email:</span> <strong>{$email}</strong></p>
                <p style='margin:0;'><span style='color:rgba(242,243,242,0.5);'>Password:</span> <strong>{$employee_id}</strong></p>
            </div>
            <p style='font-size:0.8rem;color:rgba(242,243,242,0.35);text-align:center;'>Please change your password after your first login.</p>
        </div>";
        mailer_send($email, $subject, $body, ['is_html' => true]);

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
