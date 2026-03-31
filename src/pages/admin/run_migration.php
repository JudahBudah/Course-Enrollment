<?php
include("../../php/connection.php");
$sql = "ALTER TABLE enrollments MODIFY COLUMN status ENUM('reserved','confirmed','ongoing','dropped','completed') DEFAULT 'reserved'";
if (mysqli_query($con, $sql)) {
    echo "<p style='color:green;font-family:sans-serif;'>✅ Migration successful: enrollments.status updated with reserved/confirmed statuses.</p>";
    echo "<p><a href='admin_home.php'>← Back to Admin</a></p>";
} else {
    echo "<p style='color:red;font-family:sans-serif;'>Error: " . mysqli_error($con) . "</p>";
}
?>
