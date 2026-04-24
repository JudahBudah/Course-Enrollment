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

// Build a course_name => "CODE — Name" map
$course_map = [];
$cr = mysqli_query($con, "SELECT course_name, course_code FROM courses");
while ($row = mysqli_fetch_assoc($cr)) {
    $course_map[$row['course_name']] = $row['course_code'] . ' — ' . $row['course_name'];
}

foreach (['first_choice', 'second_choice', 'third_choice'] as $field) {
    if (!empty($applicant[$field]) && isset($course_map[$applicant[$field]])) {
        $applicant[$field] = $course_map[$applicant[$field]];
    }
}

echo json_encode($applicant);
