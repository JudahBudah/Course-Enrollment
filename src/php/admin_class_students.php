<?php
session_start();
require_once 'connection.php';
require_once 'admin_functions.php';

header('Content-Type: application/json');

$admin_data = check_admin_login($con);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Get enrolled students for a class
if ($action === 'get_students') {
    $class_id = $_GET['class_id'] ?? null;
    
    if (!$class_id) {
        echo json_encode(['success' => false, 'message' => 'Class ID required']);
        exit();
    }
    
    $query = "SELECT e.enrollment_id, e.status, e.enrollment_date,
              s.student_id, s.student_number, s.first_name, s.middle_name, s.last_name, 
              s.email, s.year_level, s.course
              FROM enrollments e
              JOIN students s ON e.student_id = s.student_id
              WHERE e.class_id = ? AND e.status IN ('reserved', 'confirmed', 'ongoing')
              ORDER BY s.last_name, s.first_name";
    
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, 'i', $class_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $students = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }
    
    echo json_encode(['success' => true, 'students' => $students]);
    exit();
}

// Remove student from class
if ($action === 'remove_student') {
    $enrollment_id = $_POST['enrollment_id'] ?? null;
    $class_id = $_POST['class_id'] ?? null;
    
    if (!$enrollment_id || !$class_id) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }
    
    // Update enrollment status to dropped
    $update_query = "UPDATE enrollments SET status = 'dropped' WHERE enrollment_id = ?";
    $stmt = mysqli_prepare($con, $update_query);
    mysqli_stmt_bind_param($stmt, 'i', $enrollment_id);
    
    if (mysqli_stmt_execute($stmt)) {
        // Decrease enrolled count
        $count_query = "UPDATE classes SET enrolled_count = enrolled_count - 1 WHERE class_id = ? AND enrolled_count > 0";
        $stmt = mysqli_prepare($con, $count_query);
        mysqli_stmt_bind_param($stmt, 'i', $class_id);
        mysqli_stmt_execute($stmt);
        
        echo json_encode(['success' => true, 'message' => 'Student removed successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove student']);
    }
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>
