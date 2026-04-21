<?php
include("php/connection.php");
$q = mysqli_query($con, "
    SELECT e.enrollment_id, e.status, e.school_year AS e_sy, e.semester AS e_sem,
           c.class_id, c.semester AS c_sem, c.school_year AS c_sy, c.grades_finalized
    FROM enrollments e
    JOIN classes c ON e.class_id = c.class_id
    WHERE e.student_id = 1 AND e.status = 'confirmed'
");
echo "<pre>";
while ($r = mysqli_fetch_assoc($q)) print_r($r);

// Also show current system settings
$sem  = mysqli_fetch_assoc(mysqli_query($con, "SELECT setting_value FROM system_settings WHERE setting_key = 'current_semester'"));
$year = mysqli_fetch_assoc(mysqli_query($con, "SELECT setting_value FROM system_settings WHERE setting_key = 'current_school_year'"));
echo "\nSystem semester: " . ($sem['setting_value'] ?? 'NOT SET');
echo "\nSystem school_year: " . ($year['setting_value'] ?? 'NOT SET');
echo "</pre>";
?>
