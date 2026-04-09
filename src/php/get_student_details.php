<?php
session_start();
include("connection.php");
include("admin_functions.php");

check_admin_login($con);
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Student ID required']);
    exit;
}

$id = (int)$_GET['id'];
$stmt = mysqli_prepare($con, "
    SELECT s.*, b.block_name
    FROM students s
    LEFT JOIN blocks b ON s.block_id = b.block_id
    WHERE s.student_id = ?
");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($result);

if (!$student) {
    echo json_encode(['error' => 'Student not found']);
    exit;
}

// Remove password from response
unset($student['password']);

echo json_encode($student);
