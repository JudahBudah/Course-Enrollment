<?php
// Diagnostic script to check enrollment statuses
include("connection.php");

$student_id = (int)($_GET['student_id'] ?? 0);

if ($student_id == 0) {
    echo "Usage: diagnostic_enrollments.php?student_id=X";
    exit;
}

echo "<h2>Enrollment Status Diagnostic for Student ID: $student_id</h2>";

// Get student info
$student = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM students WHERE student_id = $student_id"));
echo "<h3>Student Info:</h3>";
echo "Name: " . $student['first_name'] . " " . $student['last_name'] . "<br>";
echo "Status: " . $student['status'] . "<br>";
echo "Block ID: " . ($student['block_id'] ?? 'NULL') . "<br><br>";

// Get all enrollments
$enrollments = mysqli_query($con, "
    SELECT e.*, s.subject_code, s.subject_name, c.section
    FROM enrollments e
    JOIN classes c ON e.class_id = c.class_id
    JOIN subjects s ON c.subject_id = s.subject_id
    WHERE e.student_id = $student_id
    ORDER BY e.enrollment_id DESC
");

echo "<h3>All Enrollments:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Enrollment ID</th><th>Subject</th><th>Section</th><th>Status</th><th>Previous Status</th></tr>";

while ($row = mysqli_fetch_assoc($enrollments)) {
    $color = '';
    if ($row['status'] == 'dropped') $color = 'background:#ffcccc;';
    if ($row['status'] == 'drop_requested') $color = 'background:#ffffcc;';
    if ($row['status'] == 'confirmed') $color = 'background:#ccffcc;';
    if ($row['status'] == 'ongoing') $color = 'background:#ccccff;';
    if ($row['status'] == 'reserved') $color = 'background:#ffddaa;';
    
    echo "<tr style='$color'>";
    echo "<td>" . $row['enrollment_id'] . "</td>";
    echo "<td>" . $row['subject_code'] . " - " . $row['subject_name'] . "</td>";
    echo "<td>" . $row['section'] . "</td>";
    echo "<td><strong>" . $row['status'] . "</strong></td>";
    echo "<td>" . ($row['previous_status'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Count by status
echo "<h3>Count by Status:</h3>";
$counts = mysqli_query($con, "
    SELECT status, COUNT(*) as count
    FROM enrollments
    WHERE student_id = $student_id
    GROUP BY status
");

while ($row = mysqli_fetch_assoc($counts)) {
    echo $row['status'] . ": " . $row['count'] . "<br>";
}
?>
