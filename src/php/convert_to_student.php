<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'PHP fatal error: ' . $err['message'] . ' in ' . basename($err['file']) . ':' . $err['line']]);
    }
});
session_start();
include("connection.php");

function json_out($data) {
    ob_end_clean();
    echo json_encode($data);
    exit;
}

// AJAX endpoint — must return JSON, never redirect
if (empty($_SESSION['admin_id'])) {
    json_out(['success' => false, 'error' => 'Session expired. Please refresh and log in again.']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['success' => false, 'error' => 'Invalid request method']);
}

$applicant_id   = (int) $_POST['applicant_id'];
$student_number = trim($_POST['student_number']);
$course         = trim($_POST['course']);
$year_level     = (int) $_POST['year_level'];
$college        = trim($_POST['college']);

// Validate
if (!$applicant_id || !$student_number || !$course || !$year_level) {
    json_out(['success' => false, 'error' => 'Please fill in all required fields.']);
}

if (!preg_match('/^\d{9}$/', $student_number)) {
    json_out(['success' => false, 'error' => 'Student number must be exactly 9 digits.']);
}

// Get applicant data
$stmt = mysqli_prepare($con, "SELECT * FROM applicants WHERE applicant_id = ? AND application_status = 'approved' LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $applicant_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    json_out(['success' => false, 'error' => 'Applicant not found or not yet approved.']);
}

$applicant = mysqli_fetch_assoc($result);

// Ensure required fields exist
if (empty($applicant['first_name']) || empty($applicant['last_name'])) {
    json_out(['success' => false, 'error' => 'Cannot convert — applicant has not completed their profile (missing first name or last name).']);
}

// Check if documents are submitted
if (empty($applicant['documents_submitted']) || $applicant['documents_submitted'] != 1) {
    json_out(['success' => false, 'error' => 'Cannot convert — applicant has not submitted required documents.']);
}

// Validate required personal information
if (empty($applicant['lrn']) || empty($applicant['birthdate']) || empty($applicant['contact_number']) ||
    empty($applicant['first_choice']) || empty($applicant['gender'])) {
    json_out(['success' => false, 'error' => 'Cannot convert — applicant has incomplete information (missing LRN, birthdate, contact number, program choice, or gender).']);
}

// Check if student number already exists
$check = mysqli_prepare($con, "SELECT student_id FROM students WHERE student_number = ?");
mysqli_stmt_bind_param($check, "s", $student_number);
mysqli_stmt_execute($check);
mysqli_stmt_store_result($check);
if (mysqli_stmt_num_rows($check) > 0) {
    json_out(['success' => false, 'error' => 'Student number already exists.']);
}

// Check if applicant email already exists as student
$check2 = mysqli_prepare($con, "SELECT student_id FROM students WHERE email = ?");
mysqli_stmt_bind_param($check2, "s", $applicant['email']);
mysqli_stmt_execute($check2);
mysqli_stmt_store_result($check2);
if (mysqli_stmt_num_rows($check2) > 0) {
    json_out(['success' => false, 'error' => 'This applicant is already a student.']);
}

// Check if LRN already exists as student
if (!empty($applicant['lrn'])) {
    $check3 = mysqli_prepare($con, "SELECT student_id FROM students WHERE lrn = ?");
    mysqli_stmt_bind_param($check3, "s", $applicant['lrn']);
    mysqli_stmt_execute($check3);
    mysqli_stmt_store_result($check3);
    if (mysqli_stmt_num_rows($check3) > 0) {
        json_out(['success' => false, 'error' => 'A student with this LRN already exists.']);
    }
}

// Ensure extended columns exist before inserting
$ensure_cols = [
    "lrn VARCHAR(20)",
    "married_name VARCHAR(100)",
    "place_of_birth VARCHAR(200)",
    "civil_status VARCHAR(20)",
    "religion VARCHAR(100)",
    "nationality VARCHAR(50)",
    "disability VARCHAR(200)",
    "perm_region VARCHAR(100)",
    "perm_province VARCHAR(100)",
    "perm_municipality VARCHAR(100)",
    "perm_barangay VARCHAR(100)",
    "perm_address TEXT",
    "perm_zipcode VARCHAR(10)",
    "mail_region VARCHAR(100)",
    "mail_province VARCHAR(100)",
    "mail_municipality VARCHAR(100)",
    "mail_barangay VARCHAR(100)",
    "mail_address TEXT",
    "mail_zipcode VARCHAR(10)",
    "doc_form138 VARCHAR(255)",
    "doc_birth_cert VARCHAR(255)",
    "doc_good_moral VARCHAR(255)",
    "doc_our_au001 VARCHAR(255)",
    "doc_our_au002 VARCHAR(255)",
    "applicant_id INT",
];
$existing_cols = [];
$col_res = mysqli_query($con, "SHOW COLUMNS FROM students");
while ($c = mysqli_fetch_assoc($col_res)) $existing_cols[] = $c['Field'];
foreach ($ensure_cols as $col_def) {
    $col_name = explode(' ', $col_def)[0];
    if (!in_array($col_name, $existing_cols)) {
        mysqli_query($con, "ALTER TABLE students ADD COLUMN $col_def DEFAULT NULL");
    }
}

// Generate a plain-text password for the new student account
$plain_password = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789'), 0, 10);
$hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

// Insert into students
$insert = mysqli_prepare($con, "INSERT INTO students 
    (student_number, lrn, first_name, last_name, middle_name, suffix_name, married_name, gender, birthdate,
     place_of_birth, civil_status, religion, nationality, disability,
     email, contact_number, college, course, year_level, password,
     perm_region, perm_province, perm_municipality, perm_barangay, perm_address, perm_zipcode,
     mail_region, mail_province, mail_municipality, mail_barangay, mail_address, mail_zipcode,
     doc_form138, doc_birth_cert, doc_good_moral, doc_our_au001, doc_our_au002,
     applicant_id, account_status, registration_status, status, must_change_password)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 'Regular', 'Not Enrolled', 1)");

if (!$insert) {
    json_out(['success' => false, 'error' => 'Query prepare failed: ' . mysqli_error($con)]);
}

$suffix_val = $applicant['suffix'] ?? $applicant['suffix_name'] ?? null;

// types: 18s + i(year_level) + 18s + i(applicant_id) = 38
$types = str_repeat('s', 18) . 'i' . str_repeat('s', 18) . 'i';
mysqli_stmt_bind_param($insert, $types,
    $student_number,          // 1  s
    $applicant['lrn'],        // 2  s
    $applicant['first_name'], // 3  s
    $applicant['last_name'],  // 4  s
    $applicant['middle_name'],// 5  s
    $suffix_val,              // 6  s
    $applicant['married_name'],// 7 s
    $applicant['gender'],     // 8  s
    $applicant['birthdate'],  // 9  s
    $applicant['place_of_birth'], // 10 s
    $applicant['civil_status'],   // 11 s
    $applicant['religion'],       // 12 s
    $applicant['nationality'],    // 13 s
    $applicant['disability'],     // 14 s
    $applicant['email'],          // 15 s
    $applicant['contact_number'], // 16 s
    $college,                     // 17 s
    $course,                      // 18 s
    $year_level,                  // 19 i
    $hashed_password,             // 20 s
    $applicant['perm_region'],    // 21 s
    $applicant['perm_province'],  // 22 s
    $applicant['perm_municipality'], // 23 s
    $applicant['perm_barangay'],  // 24 s
    $applicant['perm_address'],   // 25 s
    $applicant['perm_zipcode'],   // 26 s
    $applicant['mail_region'],    // 27 s
    $applicant['mail_province'],  // 28 s
    $applicant['mail_municipality'], // 29 s
    $applicant['mail_barangay'],  // 30 s
    $applicant['mail_address'],   // 31 s
    $applicant['mail_zipcode'],   // 32 s
    $applicant['doc_form138'],    // 33 s
    $applicant['doc_birth_cert'], // 34 s
    $applicant['doc_good_moral'], // 35 s
    $applicant['doc_our_au001'],  // 36 s
    $applicant['doc_our_au002'],  // 37 s
    $applicant_id                 // 38 i
);

if (!mysqli_stmt_execute($insert)) {
    json_out(['success' => false, 'error' => 'Failed to create student record: ' . mysqli_stmt_error($insert)]);
}

// Update applicant status to enrolled
$update = mysqli_prepare($con, "UPDATE applicants SET application_status = 'enrolled' WHERE applicant_id = ?");
mysqli_stmt_bind_param($update, "i", $applicant_id);
mysqli_stmt_execute($update);

// Send welcome email with credentials
require_once __DIR__ . '/mailer.php';
$first_name = htmlspecialchars($applicant['first_name']);
$email_body = "
<!DOCTYPE html>
<html>
<body style='font-family:Arial,sans-serif;color:#333;max-width:600px;margin:0 auto;padding:20px;'>
    <div style='background:#8c1c24;padding:20px 30px;border-radius:8px 8px 0 0;'>
        <h2 style='color:#fff;margin:0;'>Welcome to PLM Student Portal</h2>
        <p style='color:#f5c6c8;margin:4px 0 0;'>Pamantasan ng Lungsod ng Maynila</p>
    </div>
    <div style='background:#f9f9f9;padding:30px;border:1px solid #e0e0e0;border-top:none;border-radius:0 0 8px 8px;'>
        <p>Dear <strong>{$first_name}</strong>,</p>
        <p>Congratulations! Your application has been approved and your student account has been created. You may now log in to the PLM Student Portal using the credentials below.</p>
        <div style='background:#fff;border:1px solid #ddd;border-radius:6px;padding:20px;margin:20px 0;'>
            <table style='width:100%;border-collapse:collapse;'>
                <tr>
                    <td style='padding:8px 0;color:#666;width:140px;'>Student Number</td>
                    <td style='padding:8px 0;font-weight:bold;'>{$student_number}</td>
                </tr>
                <tr>
                    <td style='padding:8px 0;color:#666;'>Email</td>
                    <td style='padding:8px 0;'>{$applicant['email']}</td>
                </tr>
                <tr>
                    <td style='padding:8px 0;color:#666;'>Password</td>
                    <td style='padding:8px 0;font-weight:bold;letter-spacing:1px;'>{$plain_password}</td>
                </tr>
                <tr>
                    <td style='padding:8px 0;color:#666;'>Course</td>
                    <td style='padding:8px 0;'>{$course}</td>
                </tr>
                <tr>
                    <td style='padding:8px 0;color:#666;'>Year Level</td>
                    <td style='padding:8px 0;'>{$year_level}</td>
                </tr>
            </table>
        </div>
        <p style='color:#c0392b;font-size:0.9em;'>Please change your password after your first login for security.</p>
        <p style='margin-top:24px;'>Best regards,<br><strong>PLM Admissions Office</strong></p>
    </div>
</body>
</html>
";
mailer_send($applicant['email'], 'Your PLM Student Account Credentials', $email_body, ['is_html' => true]);

// Log the conversion
if (function_exists('log_activity')) {
    log_activity($con, 'Converted applicant to student', 'student',
        $applicant['first_name'] . ' ' . $applicant['last_name'] . ' → ' . $student_number);
}

json_out(['success' => true, 'message' => 'Applicant successfully converted to student!']);
