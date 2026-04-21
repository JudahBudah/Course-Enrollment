<?php
session_start();
include("connection.php");
include("admin_functions.php");

check_admin_login($con);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/admin/admin_blocks.php");
    exit;
}

$block_id     = (int)$_POST['block_id'];
$block_name   = trim($_POST['block_name']);
$course       = trim($_POST['course']);
$year_level   = (int)$_POST['year_level'];
$max_students = (int)$_POST['max_students'];
$status       = trim($_POST['status']);
// Always use system settings for semester and school year
$semester    = get_setting($con, 'current_semester', '1st');
$school_year = get_setting($con, 'current_school_year', date('Y') . '-' . (date('Y') + 1));

if (!$block_id || !$block_name || !$course || !$year_level || !$semester || !$school_year || !$max_students) {
    header("Location: ../pages/admin/admin_blocks.php?error=missing_fields");
    exit;
}

$stmt = mysqli_prepare($con, "UPDATE blocks SET block_name = ?, course = ?, year_level = ?, semester = ?, school_year = ?, max_students = ?, status = ? WHERE block_id = ?");
mysqli_stmt_bind_param($stmt, "ssissssi", $block_name, $course, $year_level, $semester, $school_year, $max_students, $status, $block_id);

if (mysqli_stmt_execute($stmt)) {
    log_activity($con, 'Updated block', 'block', $block_name . ' — ' . $course);
    header("Location: ../pages/admin/admin_blocks.php?success=updated");
} else {
    header("Location: ../pages/admin/admin_blocks.php?error=update_failed");
}
exit;
