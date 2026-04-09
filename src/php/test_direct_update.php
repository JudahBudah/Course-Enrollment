<?php
include("connection.php");

echo "<pre>";

// Show current students
$r = mysqli_query($con, "SELECT student_id, first_name, status FROM students");
echo "=== CURRENT STUDENTS ===\n";
while ($row = mysqli_fetch_assoc($r)) {
    echo "ID: {$row['student_id']} | Name: {$row['first_name']} | Status: '{$row['status']}'\n";
}

// Show current enrollments
$r2 = mysqli_query($con, "SELECT enrollment_id, student_id, status FROM enrollments ORDER BY student_id");
echo "\n=== CURRENT ENROLLMENTS ===\n";
while ($row = mysqli_fetch_assoc($r2)) {
    echo "Enrollment: {$row['enrollment_id']} | Student: {$row['student_id']} | Status: '{$row['status']}'\n";
}

// Test direct UPDATE
if (isset($_GET['student_id'])) {
    $sid = (int)$_GET['student_id'];
    echo "\n=== TESTING DIRECT UPDATE for student_id=$sid ===\n";

    $q = mysqli_query($con, "UPDATE students SET status = 'Dropped' WHERE student_id = $sid");
    echo "Query result: " . ($q ? 'true' : 'false') . "\n";
    echo "Affected rows: " . mysqli_affected_rows($con) . "\n";
    echo "Error: " . mysqli_error($con) . "\n";

    $verify = mysqli_query($con, "SELECT status FROM students WHERE student_id = $sid");
    $row = mysqli_fetch_assoc($verify);
    echo "Status after update: '{$row['status']}'\n";
}

echo "</pre>";

echo "<br>Test update for student: ";
$r3 = mysqli_query($con, "SELECT student_id, first_name FROM students");
while ($row = mysqli_fetch_assoc($r3)) {
    echo "<a href='?student_id={$row['student_id']}'>{$row['first_name']} (ID:{$row['student_id']})</a> | ";
}
?>
