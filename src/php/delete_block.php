<?php
session_start();
include("connection.php");
include("admin_functions.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../pages/admin/admin_login.php");
    exit;
}

if (!isset($_GET['block_id'])) {
    header("Location: ../pages/admin/admin_blocks.php?error=missing_fields");
    exit;
}

$block_id = mysqli_real_escape_string($con, $_GET['block_id']);

// Check if block has students
$check_query = "SELECT current_students FROM blocks WHERE block_id = '$block_id'";
$check_result = mysqli_query($con, $check_query);

if (!$check_result || mysqli_num_rows($check_result) === 0) {
    header("Location: ../pages/admin/admin_blocks.php?error=delete_failed");
    exit;
}

$block = mysqli_fetch_assoc($check_result);

if ($block['current_students'] > 0) {
    header("Location: ../pages/admin/admin_blocks.php?error=has_students");
    exit;
}

// Delete block subjects first
mysqli_query($con, "DELETE FROM block_subjects WHERE block_id = '$block_id'");

// Delete block
$delete_query = "DELETE FROM blocks WHERE block_id = '$block_id'";

if (mysqli_query($con, $delete_query)) {
    log_activity($con, 'Deleted block', 'block', 'Block ID ' . $block_id);
    header("Location: ../pages/admin/admin_blocks.php?success=deleted");
} else {
    header("Location: ../pages/admin/admin_blocks.php?error=delete_failed");
}
exit;
?>
