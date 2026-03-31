<?php
session_start();
include("connection.php");
include("admin_functions.php");

check_admin_login($con);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/admin/admin_students.php");
    die;
}

$student_id         = (int) $_POST['student_id'];
$student_number     = trim($_POST['student_number']);
$first_name         = trim($_POST['first_name']);
$last_name          = trim($_POST['last_name']);
$middle_name        = trim($_POST['middle_name']);
$suffix_name        = trim($_POST['suffix_name']);
$gender             = trim($_POST['gender']);
$birthdate          = trim($_POST['birthdate']);
$email              = trim($_POST['email']);
$contact_number     = trim($_POST['contact_number']);
$college            = trim($_POST['college']);
$course             = trim($_POST['course']);
$year_level         = (int) $_POST['year_level'];
$block_id           = $_POST['block_id'] !== '' ? (int) $_POST['block_id'] : null;
$registration_status = trim($_POST['registration_status']);
$account_status     = trim($_POST['account_status']);
$status             = trim($_POST['status']);

if (!$student_id || !$first_name || !$last_name || !$email) {
    header("Location: ../pages/admin/admin_students.php?error=missing_fields");
    die;
}

// Check email uniqueness (exclude current student)
$check = mysqli_prepare($con, "SELECT student_id FROM students WHERE email = ? AND student_id != ?");
mysqli_stmt_bind_param($check, "si", $email, $student_id);
mysqli_stmt_execute($check);
mysqli_stmt_store_result($check);
if (mysqli_stmt_num_rows($check) > 0) {
    header("Location: ../pages/admin/admin_students.php?error=duplicate_email");
    die;
}

// Check student number uniqueness (exclude current student)
$check2 = mysqli_prepare($con, "SELECT student_id FROM students WHERE student_number = ? AND student_id != ?");
mysqli_stmt_bind_param($check2, "si", $student_number, $student_id);
mysqli_stmt_execute($check2);
mysqli_stmt_store_result($check2);
if (mysqli_stmt_num_rows($check2) > 0) {
    header("Location: ../pages/admin/admin_students.php?error=duplicate_student_number");
    die;
}

$stmt = mysqli_prepare($con, "UPDATE students SET
    student_number = ?,
    first_name = ?,
    last_name = ?,
    middle_name = ?,
    suffix_name = ?,
    gender = ?,
    birthdate = ?,
    email = ?,
    contact_number = ?,
    college = ?,
    course = ?,
    year_level = ?,
    block_id = ?,
    registration_status = ?,
    account_status = ?,
    status = ?
    WHERE student_id = ?");

mysqli_stmt_bind_param($stmt, "sssssssssssisissi",
    $student_number,
    $first_name,
    $last_name,
    $middle_name,
    $suffix_name,
    $gender,
    $birthdate,
    $email,
    $contact_number,
    $college,
    $course,
    $year_level,
    $block_id,
    $registration_status,
    $account_status,
    $status,
    $student_id
);

if (!mysqli_stmt_execute($stmt)) {
    header("Location: ../pages/admin/admin_students.php?error=update_failed");
    die;
}

header("Location: ../pages/admin/admin_students.php?success=updated");
die;
