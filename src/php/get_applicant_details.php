<?php
session_start();
include("connection.php");
include("admin_functions.php");

check_admin_login($con);

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Applicant ID required']);
    exit;
}

$id = (int)$_GET['id'];
$stmt = mysqli_prepare($con, "SELECT * FROM applicants WHERE applicant_id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$applicant = mysqli_fetch_assoc($result);

if (!$applicant) {
    echo json_encode(['error' => 'Applicant not found']);
    exit;
}

echo json_encode($applicant);
