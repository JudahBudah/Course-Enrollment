<?php
// Utility script to sync student enrollment status based on actual enrollments
// Run this once to fix any inconsistencies

include("connection.php");

// Update all students with active enrollments to 'Enrolled'
$update_enrolled = mysqli_query($con, "
    UPDATE students s
    SET s.status = 'Enrolled'
    WHERE s.student_id IN (
        SELECT DISTINCT e.student_id 
        FROM enrollments e 
        WHERE e.status IN ('reserved', 'confirmed', 'ongoing')
    )
    AND s.status = 'Not Enrolled'
");

$enrolled_updated = mysqli_affected_rows($con);

// Update all students with no active enrollments to 'Not Enrolled'
$update_not_enrolled = mysqli_query($con, "
    UPDATE students s
    SET s.status = 'Not Enrolled'
    WHERE s.student_id NOT IN (
        SELECT DISTINCT e.student_id 
        FROM enrollments e 
        WHERE e.status IN ('reserved', 'confirmed', 'ongoing')
    )
    AND s.status = 'Enrolled'
");

$not_enrolled_updated = mysqli_affected_rows($con);

echo "Sync completed!\n";
echo "Students updated to 'Enrolled': $enrolled_updated\n";
echo "Students updated to 'Not Enrolled': $not_enrolled_updated\n";
?>
