<?php
session_start();
include("connection.php");
include("admin_functions.php");

check_admin_login($con);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/admin/admin_applicants.php");
    die;
}

$applicant_id   = (int) $_POST['applicant_id'];
$student_number = trim($_POST['student_number']);
$course         = trim($_POST['course']);
$year_level     = (int) $_POST['year_level'];
$college        = trim($_POST['college']);

// Validate
if (!$applicant_id || !$student_number || !$course || !$year_level) {
    header("Location: ../pages/admin/admin_applicants.php?error=missing_fields");
    die;
}

// Get applicant data
$stmt = mysqli_prepare($con, "SELECT * FROM applicants WHERE applicant_id = ? AND application_status = 'approved' LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $applicant_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    header("Location: ../pages/admin/admin_applicants.php?error=not_found");
    die;
}

$applicant = mysqli_fetch_assoc($result);

// Ensure required fields exist
if (empty($applicant['first_name']) || empty($applicant['last_name'])) {
    header("Location: ../pages/admin/admin_applicants.php?error=incomplete_profile");
    die;
}

// Check if student number already exists
$check = mysqli_prepare($con, "SELECT student_id FROM students WHERE student_number = ?");
mysqli_stmt_bind_param($check, "s", $student_number);
mysqli_stmt_execute($check);
mysqli_stmt_store_result($check);
if (mysqli_stmt_num_rows($check) > 0) {
    header("Location: ../pages/admin/admin_applicants.php?error=duplicate_student_number");
    die;
}

// Check if applicant email already exists as student
$check2 = mysqli_prepare($con, "SELECT student_id FROM students WHERE email = ?");
mysqli_stmt_bind_param($check2, "s", $applicant['email']);
mysqli_stmt_execute($check2);
mysqli_stmt_store_result($check2);
if (mysqli_stmt_num_rows($check2) > 0) {
    header("Location: ../pages/admin/admin_applicants.php?error=already_student");
    die;
}

// Insert into students
$insert = mysqli_prepare($con, "INSERT INTO students 
    (student_number, first_name, last_name, middle_name, gender, birthdate, email, contact_number, college, course, year_level, password, account_status, registration_status, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 'Regular', 'Not Enrolled')");

mysqli_stmt_bind_param($insert, "ssssssssssis",
    $student_number,
    $applicant['first_name'],
    $applicant['last_name'],
    $applicant['middle_name'],
    $applicant['gender'],
    $applicant['birthdate'],
    $applicant['email'],
    $applicant['contact_number'],
    $college,
    $course,
    $year_level,
    $applicant['password']
);

if (!mysqli_stmt_execute($insert)) {
    header("Location: ../pages/admin/admin_applicants.php?error=insert_failed");
    die;
}

// Update applicant status to enrolled
$update = mysqli_prepare($con, "UPDATE applicants SET application_status = 'enrolled' WHERE applicant_id = ?");
mysqli_stmt_bind_param($update, "i", $applicant_id);
mysqli_stmt_execute($update);

header("Location: ../pages/admin/admin_applicants.php?success=converted");
die;
