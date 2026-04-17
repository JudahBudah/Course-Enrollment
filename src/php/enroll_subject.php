<?php
session_start();
require_once 'connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$student_id = $_SESSION['student_id'];
$class_id = $_POST['class_id'] ?? null;

if (!$class_id) {
    echo json_encode(['success' => false, 'message' => 'Class ID is required']);
    exit();
}

// Check if already enrolled
$check_query = "SELECT * FROM enrollments WHERE student_id = ? AND class_id = ? AND status IN ('reserved', 'confirmed', 'ongoing')";
$stmt = mysqli_prepare($con, $check_query);
mysqli_stmt_bind_param($stmt, 'ii', $student_id, $class_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    echo json_encode(['success' => false, 'message' => 'Already enrolled in this subject']);
    exit();
}

// Check class capacity
$capacity_query = "SELECT c.max_slots, c.enrolled_count, s.units FROM classes c JOIN subjects s ON c.subject_id = s.subject_id WHERE c.class_id = ?";
$stmt = mysqli_prepare($con, $capacity_query);
mysqli_stmt_bind_param($stmt, 'i', $class_id);
mysqli_stmt_execute($stmt);
$class_info = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if ($class_info['enrolled_count'] >= $class_info['max_slots']) {
    echo json_encode(['success' => false, 'message' => 'Class is full']);
    exit();
}

// Get school year and semester from class
$class_query = "SELECT school_year, semester FROM classes WHERE class_id = ?";
$stmt = mysqli_prepare($con, $class_query);
mysqli_stmt_bind_param($stmt, 'i', $class_id);
mysqli_stmt_execute($stmt);
$class_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Enroll student
$enroll_query = "INSERT INTO enrollments (student_id, class_id, school_year, semester, status) VALUES (?, ?, ?, ?, 'confirmed')";
$stmt = mysqli_prepare($con, $enroll_query);
$semester_num = $class_data['semester'] === '1st' ? 1 : 2;
mysqli_stmt_bind_param($stmt, 'iisi', $student_id, $class_id, $class_data['school_year'], $semester_num);

if (mysqli_stmt_execute($stmt)) {
    // Update enrolled count
    $update_query = "UPDATE classes SET enrolled_count = enrolled_count + 1 WHERE class_id = ?";
    $stmt = mysqli_prepare($con, $update_query);
    mysqli_stmt_bind_param($stmt, 'i', $class_id);
    mysqli_stmt_execute($stmt);

    // Update student status to Enrolled
    mysqli_query($con, "UPDATE students SET status = 'Enrolled' WHERE student_id = $student_id");
    
    echo json_encode(['success' => true, 'message' => 'Successfully enrolled']);
} else {
    echo json_encode(['success' => false, 'message' => 'Enrollment failed']);
}
?>
