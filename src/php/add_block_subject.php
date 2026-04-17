<?php
session_start();
include("connection.php");
include("admin_functions.php");

$admin_data = check_admin_login($con);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $block_id = (int)$_POST['block_id'];
    $class_id = (int)$_POST['class_id'];

    // Get block course so we can restrict the class to it
    $block = mysqli_fetch_assoc(mysqli_query($con, "SELECT course FROM blocks WHERE block_id = $block_id"));
    if (!$block) {
        header("Location: ../pages/admin/admin_block_subjects.php?block_id=$block_id&error=failed");
        exit;
    }

    $stmt = mysqli_prepare($con, "INSERT IGNORE INTO block_subjects (block_id, class_id) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, 'ii', $block_id, $class_id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($ok && mysqli_affected_rows($con) > 0) {
        // Restrict this class to the block's course department
        $course = mysqli_real_escape_string($con, $block['course']);
        mysqli_query($con, "UPDATE classes SET specific_department = '$course' WHERE class_id = $class_id");
        header("Location: ../pages/admin/admin_block_subjects.php?block_id=$block_id&success=added");
    } else {
        header("Location: ../pages/admin/admin_block_subjects.php?block_id=$block_id&error=failed");
    }
} else {
    header("Location: ../pages/admin/admin_blocks.php");
}
?>
