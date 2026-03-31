<?php
session_start();
include("connection.php");
include("admin_functions.php");

check_admin_login($con);

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $role     = $_POST['role'] ?? 'admin';

    if (!$username || !$email || !$password) {
        header("Location: ../pages/admin/admin_accounts.php?error=missing"); die;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($con, "INSERT INTO admins (username, email, password, role) VALUES (?,?,?,?)");
    mysqli_stmt_bind_param($stmt, 'ssss', $username, $email, $hash, $role);
    if (!mysqli_stmt_execute($stmt)) {
        header("Location: ../pages/admin/admin_accounts.php?error=duplicate"); die;
    }
    header("Location: ../pages/admin/admin_accounts.php?success=added"); die;
}

if ($action === 'edit') {
    $id       = (int)$_POST['admin_id'];
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $role     = $_POST['role'] ?? 'admin';

    if (!$username || !$email) {
        header("Location: ../pages/admin/admin_accounts.php?error=missing"); die;
    }

    // Prevent editing own role to non-admin accidentally
    $stmt = mysqli_prepare($con, "UPDATE admins SET username=?, email=?, role=? WHERE admin_id=?");
    mysqli_stmt_bind_param($stmt, 'sssi', $username, $email, $role, $id);
    if (!mysqli_stmt_execute($stmt)) {
        header("Location: ../pages/admin/admin_accounts.php?error=duplicate"); die;
    }

    // Change password only if provided
    if (!empty($_POST['password'])) {
        $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $ps = mysqli_prepare($con, "UPDATE admins SET password=? WHERE admin_id=?");
        mysqli_stmt_bind_param($ps, 'si', $hash, $id);
        mysqli_stmt_execute($ps);
    }

    header("Location: ../pages/admin/admin_accounts.php?success=updated"); die;
}

if ($action === 'delete') {
    $id       = (int)$_POST['admin_id'];
    $self_id  = (int)$_SESSION['admin_id'];

    if ($id === $self_id) {
        header("Location: ../pages/admin/admin_accounts.php?error=self_delete"); die;
    }
    mysqli_query($con, "DELETE FROM admins WHERE admin_id = $id");
    header("Location: ../pages/admin/admin_accounts.php?success=deleted"); die;
}

header("Location: ../pages/admin/admin_accounts.php"); die;
?>
