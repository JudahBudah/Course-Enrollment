<?php
session_start();
include("connection.php");
include("admin_functions.php");

check_admin_login($con);

$course = trim($_POST['course'] ?? '');
$year_level = trim($_POST['year_level'] ?? '');
$semester = trim($_POST['semester'] ?? '');
$school_year = trim($_POST['school_year'] ?? '');
$skip_enrolled = isset($_POST['skip_enrolled']);
$regular_only = isset($_POST['regular_only']);

if (empty($course) || empty($year_level) || empty($semester) || empty($school_year)) {
    header("Location: ../pages/admin/admin_batch_enroll.php?error=missing_fields");
    die;
}

// Find students matching criteria
$student_query = "SELECT student_id FROM students WHERE course = ? AND year_level = ?";
if ($regular_only) {
    $student_query .= " AND registration_status = 'Regular'";
}

$stmt = mysqli_prepare($con, $student_query);
mysqli_stmt_bind_param($stmt, "si", $course, $year_level);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$students = [];
while ($row = mysqli_fetch_assoc($result)) {
    $students[] = $row['student_id'];
}

if (empty($students)) {
    header("Location: ../pages/admin/admin_batch_enroll.php?error=no_students");
    die;
}

// Find classes matching criteria
$class_query = "SELECT c.class_id, c.subject_id 
                FROM classes c
                JOIN subjects s ON c.subject_id = s.subject_id
                WHERE c.school_year = ? 
                AND c.semester = ? 
                AND c.status = 'open'
                AND s.year_level = ?";

$stmt = mysqli_prepare($con, $class_query);
mysqli_stmt_bind_param($stmt, "sss", $school_year, $semester, $year_level);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$classes = [];
while ($row = mysqli_fetch_assoc($result)) {
    $classes[] = $row;
}

if (empty($classes)) {
    header("Location: ../pages/admin/admin_batch_enroll.php?error=no_classes");
    die;
}

// Enroll students in classes
$enrolled_count = 0;
$semester_num = ($semester === '1st') ? 1 : (($semester === '2nd') ? 2 : 0);

foreach ($students as $student_id) {
    foreach ($classes as $class) {
        // Check if already enrolled
        if ($skip_enrolled) {
            $chk = mysqli_prepare($con, "SELECT enrollment_id FROM enrollments WHERE student_id = ? AND class_id = ?");
            mysqli_stmt_bind_param($chk, "ii", $student_id, $class['class_id']);
            mysqli_stmt_execute($chk);
            mysqli_stmt_store_result($chk);
            if (mysqli_stmt_num_rows($chk) > 0) {
                continue;
            }
        }
        
        // Insert enrollment as 'reserved' (student must confirm)
        $enroll_stmt = mysqli_prepare($con, "INSERT INTO enrollments (student_id, class_id, school_year, semester, status) VALUES (?, ?, ?, ?, 'reserved')");
        mysqli_stmt_bind_param($enroll_stmt, "iisi", $student_id, $class['class_id'], $school_year, $semester_num);
        
        if (mysqli_stmt_execute($enroll_stmt)) {
            $enrolled_count++;
        }
    }
}

if ($enrolled_count > 0) {
    log_activity($con, 'Batch enrolled students', 'enrollment',
        count($students) . ' students in ' . $course . ' Y' . $year_level . ' ' . $semester . ' ' . $school_year . ' (' . $enrolled_count . ' enrollments)');
    header("Location: ../pages/admin/admin_batch_enroll.php?success=1&enrolled=$enrolled_count");
} else {
    header("Location: ../pages/admin/admin_batch_enroll.php?error=enroll_failed");
}
die;
?>
