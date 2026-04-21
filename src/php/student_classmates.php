<?php
session_start();
include("connection.php");
include("functions.php");

$user_data  = check_login($con);
$student_id = $user_data['student_id'];

header('Content-Type: application/json');

$action   = $_GET['action'] ?? '';
$class_id = (int)($_GET['class_id'] ?? 0);

// Verify the requesting student is actually enrolled in this class
$chk = mysqli_prepare($con, "SELECT enrollment_id FROM enrollments WHERE student_id = ? AND class_id = ? AND status IN ('confirmed','reserved','ongoing') LIMIT 1");
mysqli_stmt_bind_param($chk, "ii", $student_id, $class_id);
mysqli_stmt_execute($chk);
mysqli_stmt_store_result($chk);
if (!mysqli_stmt_num_rows($chk)) {
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}
mysqli_stmt_close($chk);

if ($action === 'classmates') {
    $stmt = mysqli_prepare($con, "
        SELECT s.student_id, s.student_number, s.first_name, s.last_name, s.middle_name,
               s.course, s.year_level, s.profile_photo
        FROM enrollments e
        JOIN students s ON e.student_id = s.student_id
        WHERE e.class_id = ? AND e.status IN ('confirmed','reserved','ongoing') AND e.student_id != ?
        ORDER BY s.last_name, s.first_name
    ");
    mysqli_stmt_bind_param($stmt, "ii", $class_id, $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $list = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $list[] = [
            'student_id'     => $row['student_id'],
            'student_number' => $row['student_number'],
            'full_name'      => trim($row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'][0] . '. ' : '') . $row['last_name']),
            'course'         => $row['course'],
            'year_level'     => $row['year_level'],
            'profile_photo'  => $row['profile_photo'] ? '../../' . $row['profile_photo'] : '../../uploads/default.jpg',
        ];
    }
    mysqli_stmt_close($stmt);
    echo json_encode(['success' => true, 'classmates' => $list]);
    exit;
}

if ($action === 'profile') {
    $target_id = (int)($_GET['student_id'] ?? 0);

    // Only expose limited public info
    $stmt = mysqli_prepare($con, "
        SELECT student_number, first_name, last_name, middle_name, suffix_name,
               course, year_level, college, profile_photo
        FROM students WHERE student_id = ? LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, "i", $target_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$row) { echo json_encode(['success' => false, 'message' => 'Student not found.']); exit; }

    echo json_encode([
        'success' => true,
        'profile' => [
            'student_number' => $row['student_number'],
            'full_name'      => trim($row['first_name'] . ' ' . ($row['middle_name'] ?? '') . ' ' . $row['last_name'] . ($row['suffix_name'] ? ' ' . $row['suffix_name'] : '')),
            'course'         => $row['course'],
            'year_level'     => $row['year_level'],
            'college'        => $row['college'],
            'profile_photo'  => $row['profile_photo'] ? '../../' . $row['profile_photo'] : '../../uploads/default.jpg',
        ],
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);
