<?php
session_start();
include("connection.php");
include("admin_functions.php");

check_admin_login($con);

header('Content-Type: application/json');

if (!isset($_GET['block_id'])) {
    echo json_encode(['error' => 'Block ID required']);
    exit;
}

$block_id = (int)$_GET['block_id'];
$stmt = mysqli_prepare($con, "SELECT * FROM blocks WHERE block_id = ?");
mysqli_stmt_bind_param($stmt, "i", $block_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$block = mysqli_fetch_assoc($result);

if (!$block) {
    echo json_encode(['error' => 'Block not found']);
    exit;
}

echo json_encode($block);
