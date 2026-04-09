<?php
// Test script to verify drop logic
include("connection.php");

echo "<h2>Testing Drop Logic</h2>";

// Get a student with enrollments
$student_query = mysqli_query($con, "
    SELECT s.student_id, s.student_number, s.first_name, s.last_name, s.status,
           COUNT(e.enrollment_id) as total_enrollments,
           SUM(CASE WHEN e.status IN ('reserved','confirmed','ongoing') THEN 1 ELSE 0 END) as active_enrollments
    FROM students s
    LEFT JOIN enrollments e ON s.student_id = e.student_id
    GROUP BY s.student_id
    LIMIT 10
");

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Student ID</th><th>Name</th><th>Current Status</th><th>Total Enrollments</th><th>Active Enrollments</th><th>Expected Status</th></tr>";

while ($student = mysqli_fetch_assoc($student_query)) {
    $expected_status = ($student['active_enrollments'] > 0) ? 'Enrolled' : 'Not Enrolled';
    $status_match = ($student['status'] == $expected_status) ? '✓' : '✗';
    $color = ($status_match == '✓') ? 'green' : 'red';
    
    echo "<tr style='color: $color;'>";
    echo "<td>" . $student['student_id'] . "</td>";
    echo "<td>" . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) . "</td>";
    echo "<td><strong>" . $student['status'] . "</strong></td>";
    echo "<td>" . $student['total_enrollments'] . "</td>";
    echo "<td>" . $student['active_enrollments'] . "</td>";
    echo "<td>" . $expected_status . " " . $status_match . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr><h3>Enrollment Details:</h3>";

$enrollment_query = mysqli_query($con, "
    SELECT e.enrollment_id, e.student_id, e.status, s.subject_code, s.subject_name,
           st.first_name, st.last_name, st.status as student_status
    FROM enrollments e
    JOIN classes c ON e.class_id = c.class_id
    JOIN subjects s ON c.subject_id = s.subject_id
    JOIN students st ON e.student_id = st.student_id
    ORDER BY e.student_id, e.enrollment_id DESC
    LIMIT 50
");

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Enrollment ID</th><th>Student</th><th>Subject</th><th>Enrollment Status</th><th>Student Status</th></tr>";

while ($enroll = mysqli_fetch_assoc($enrollment_query)) {
    $color = '';
    if ($enroll['status'] == 'dropped') $color = 'background:#ffcccc;';
    if ($enroll['status'] == 'drop_requested') $color = 'background:#ffeecc;';
    if (in_array($enroll['status'], ['confirmed', 'ongoing'])) $color = 'background:#ccffcc;';
    
    echo "<tr style='$color'>";
    echo "<td>" . $enroll['enrollment_id'] . "</td>";
    echo "<td>" . htmlspecialchars($enroll['first_name'] . ' ' . $enroll['last_name']) . " (ID: " . $enroll['student_id'] . ")</td>";
    echo "<td>" . htmlspecialchars($enroll['subject_code'] . ' - ' . $enroll['subject_name']) . "</td>";
    echo "<td><strong>" . $enroll['status'] . "</strong></td>";
    echo "<td><strong>" . $enroll['student_status'] . "</strong></td>";
    echo "</tr>";
}

echo "</table>";
?>
