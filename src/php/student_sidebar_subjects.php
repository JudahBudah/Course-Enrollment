<?php
session_start();
include("connection.php");
include("functions.php");

$user_data = check_login($con);
header('Content-Type: application/json');

$stmt = mysqli_prepare($con, "
    SELECT s.subject_code, s.subject_name, c.schedule_day, c.schedule_time, c.section
    FROM enrollments e
    JOIN classes c ON e.class_id = c.class_id
    JOIN subjects s ON c.subject_id = s.subject_id
    WHERE e.student_id = ? AND e.status = 'confirmed'
    ORDER BY s.subject_code
");
mysqli_stmt_bind_param($stmt, "i", $user_data['student_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$subjects = [];
while ($row = mysqli_fetch_assoc($result)) {
    $subjects[] = [
        'code'     => $row['subject_code'],
        'name'     => $row['subject_name'],
        'day'      => $row['schedule_day'] ?? '',
        'time'     => $row['schedule_time'] ?? '',
        'section'  => $row['section'] ?? '',
    ];
}
mysqli_stmt_close($stmt);
echo json_encode($subjects);
