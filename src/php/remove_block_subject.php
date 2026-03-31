<?php
session_start();
include("connection.php");
include("admin_functions.php");

$admin_data = check_admin_login($con);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $block_id = (int)$_POST['block_id'];
    $class_id = (int)$_POST['class_id'];

    $query = "DELETE FROM block_subjects WHERE block_id = $block_id AND class_id = $class_id";

    if (mysqli_query($con, $query)) {
        header("Location: ../pages/admin/admin_block_subjects.php?block_id=$block_id&success=removed");
    } else {
        header("Location: ../pages/admin/admin_block_subjects.php?block_id=$block_id&error=failed");
    }
} else {
    header("Location: ../pages/admin/admin_blocks.php");
}
?>
