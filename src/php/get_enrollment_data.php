<?php
session_start();
include("connection.php");
include("functions.php");

$user_data = check_login($con);
$student_id = $user_data['student_id'];
$student_course = $user_data['course'];
$student_year = $user_data['year_level'];

// Get course_id from student's course
$course_id = null;
if (!empty($student_course)) {
    $course_query = mysqli_prepare($con, "SELECT course_id FROM courses WHERE course_name = ? OR course_code = ? LIMIT 1");
    mysqli_stmt_bind_param($course_query, "ss", $student_course, $student_course);
    mysqli_stmt_execute($course_query);
    $course_result = mysqli_stmt_get_result($course_query);
    if ($course_row = mysqli_fetch_assoc($course_result)) {
        $course_id = $course_row['course_id'];
    }
}

// Get enrolled subjects (reserved, confirmed, ongoing)
$enrolled_query = "
    SELECT 
        e.enrollment_id,
        e.status,
        s.subject_code,
        s.subject_name,
        s.units,
        s.lecture_hours,
        s.lab_hours,
        c.section,
        c.schedule_day,
        c.schedule_time,
        c.room,
        c.class_id,
        CONCAT(f.first_name, ' ', f.last_name) as faculty_name
    FROM enrollments e
    JOIN classes c ON e.class_id = c.class_id
    JOIN subjects s ON c.subject_id = s.subject_id
    LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
    WHERE e.student_id = ? 
    AND e.status IN ('reserved', 'confirmed', 'ongoing')
    ORDER BY s.subject_code
";
$stmt = mysqli_prepare($con, $enrolled_query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$enrolled_subjects = mysqli_stmt_get_result($stmt);

// Get subjects for retake (failed subjects)
$retake_query = "
    SELECT DISTINCT
        s.subject_id,
        s.subject_code,
        s.subject_name,
        s.units,
        s.lecture_hours,
        s.lab_hours
    FROM grades g
    JOIN subjects s ON g.subject_id = s.subject_id
    WHERE g.student_id = ? 
    AND (g.grade = '5.00' OR g.grade = 'INC' OR g.status = 'Failed')
    AND s.subject_id NOT IN (
        SELECT c.subject_id 
        FROM enrollments e 
        JOIN classes c ON e.class_id = c.class_id 
        WHERE e.student_id = ? 
        AND e.status IN ('reserved', 'confirmed', 'ongoing')
    )
";
$stmt = mysqli_prepare($con, $retake_query);
mysqli_stmt_bind_param($stmt, "ii", $student_id, $student_id);
mysqli_stmt_execute($stmt);
$retake_subjects = mysqli_stmt_get_result($stmt);

// Get available classes for student's course and year level
$available_query = "
    SELECT 
        c.class_id,
        s.subject_code,
        s.subject_name,
        s.units,
        s.lecture_hours,
        s.lab_hours,
        s.year_level,
        s.semester,
        c.section,
        c.schedule_day,
        c.schedule_time,
        c.room,
        c.max_slots,
        c.enrolled_count,
        CONCAT(f.first_name, ' ', f.last_name) as faculty_name
    FROM classes c
    JOIN subjects s ON c.subject_id = s.subject_id
    LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
    WHERE c.status = 'open'
    AND (s.course_id = ? OR s.course_id IS NULL)
    AND c.class_id NOT IN (
        SELECT class_id 
        FROM enrollments 
        WHERE student_id = ? 
        AND status IN ('reserved', 'confirmed', 'ongoing')
    )
    ORDER BY s.subject_code
";
$stmt = mysqli_prepare($con, $available_query);
mysqli_stmt_bind_param($stmt, "ii", $course_id, $student_id);
mysqli_stmt_execute($stmt);
$available_classes = mysqli_stmt_get_result($stmt);

// Get available classes for retake subjects
function get_retake_classes($con, $subject_id, $student_id) {
    $query = "
        SELECT 
            c.class_id,
            c.section,
            c.schedule_day,
            c.schedule_time,
            c.room,
            c.max_slots,
            c.enrolled_count,
            CONCAT(f.first_name, ' ', f.last_name) as faculty_name
        FROM classes c
        LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
        WHERE c.subject_id = ?
        AND c.status = 'open'
        AND c.class_id NOT IN (
            SELECT class_id 
            FROM enrollments 
            WHERE student_id = ? 
            AND status IN ('reserved', 'confirmed', 'ongoing')
        )
    ";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "ii", $subject_id, $student_id);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}
?>
