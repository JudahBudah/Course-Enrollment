<?php
session_start();
include("connection.php");
include("admin_functions.php");

check_admin_login($con);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/admin/admin_blocks.php");
    exit;
}

$block_id = (int)$_POST['block_id'];
$block_name = trim($_POST['block_name']);
$course = trim($_POST['course']);
$year_level = (int)$_POST['year_level'];
$semester = trim($_POST['semester']);
$school_year = trim($_POST['school_year']);
$max_students = (int)$_POST['max_students'];
$status = trim($_POST['status']);

if (!$block_id || !$block_name || !$course || !$year_level || !$semester || !$school_year || !$max_students) {
    header("Location: ../pages/admin/admin_blocks.php?error=missing_fields");
    exit;
}

$stmt = mysqli_prepare($con, "UPDATE blocks SET block_name = ?, course = ?, year_level = ?, semester = ?, school_year = ?, max_students = ?, status = ? WHERE block_id = ?");
mysqli_stmt_bind_param($stmt, "ssissssi", $block_name, $course, $year_level, $semester, $school_year, $max_students, $status, $block_id);

if (mysqli_stmt_execute($stmt)) {
    header("Location: ../pages/admin/admin_blocks.php?success=updated");
} else {
    header("Location: ../pages/admin/admin_blocks.php?error=update_failed");
}
exit;
