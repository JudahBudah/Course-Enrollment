<?php
session_start();
include("connection.php");
include("admin_functions.php");

check_admin_login($con);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    die;
}

$applicant_id   = (int) $_POST['applicant_id'];
$student_number = trim($_POST['student_number']);
$course         = trim($_POST['course']);
$year_level     = (int) $_POST['year_level'];
$college        = trim($_POST['college']);

// Validate
if (!$applicant_id || !$student_number || !$course || !$year_level) {
    echo json_encode(['success' => false, 'error' => 'Please fill in all required fields.']);
    die;
}

// Get applicant data
$stmt = mysqli_prepare($con, "SELECT * FROM applicants WHERE applicant_id = ? AND application_status = 'approved' LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $applicant_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    echo json_encode(['success' => false, 'error' => 'Applicant not found or not yet approved.']);
    die;
}

$applicant = mysqli_fetch_assoc($result);

// Ensure required fields exist
if (empty($applicant['first_name']) || empty($applicant['last_name'])) {
    echo json_encode(['success' => false, 'error' => 'Cannot convert — applicant has not completed their profile (missing first name or last name).']);
    die;
}

// Check if documents are submitted
if (empty($applicant['documents_submitted']) || $applicant['documents_submitted'] != 1) {
    echo json_encode(['success' => false, 'error' => 'Cannot convert — applicant has not submitted required documents.']);
    die;
}

// Validate required personal information
if (empty($applicant['lrn']) || empty($applicant['birthdate']) || empty($applicant['contact_number']) ||
    empty($applicant['first_choice']) || empty($applicant['gender'])) {
    echo json_encode(['success' => false, 'error' => 'Cannot convert — applicant has incomplete information (missing LRN, birthdate, contact number, program choice, or gender).']);
    die;
}

// Check if student number already exists
$check = mysqli_prepare($con, "SELECT student_id FROM students WHERE student_number = ?");
mysqli_stmt_bind_param($check, "s", $student_number);
mysqli_stmt_execute($check);
mysqli_stmt_store_result($check);
if (mysqli_stmt_num_rows($check) > 0) {
    echo json_encode(['success' => false, 'error' => 'Student number already exists.']);
    die;
}

// Check if applicant email already exists as student
$check2 = mysqli_prepare($con, "SELECT student_id FROM students WHERE email = ?");
mysqli_stmt_bind_param($check2, "s", $applicant['email']);
mysqli_stmt_execute($check2);
mysqli_stmt_store_result($check2);
if (mysqli_stmt_num_rows($check2) > 0) {
    echo json_encode(['success' => false, 'error' => 'This applicant is already a student.']);
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
    echo json_encode(['success' => false, 'error' => 'Failed to create student record. Please try again.']);
    die;
}

// Update applicant status to enrolled
$update = mysqli_prepare($con, "UPDATE applicants SET application_status = 'enrolled' WHERE applicant_id = ?");
mysqli_stmt_bind_param($update, "i", $applicant_id);
mysqli_stmt_execute($update);

echo json_encode(['success' => true, 'message' => 'Applicant successfully converted to student!']);
die;
