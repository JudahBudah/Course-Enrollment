<?php
// Run this once to update the enrollments table status enum
include("connection.php");

$sql = "ALTER TABLE enrollments MODIFY COLUMN status ENUM('reserved','confirmed','ongoing','dropped','completed') DEFAULT 'reserved'";
if (mysqli_query($con, $sql)) {
    echo "Migration successful: enrollments.status updated.";
} else {
    echo "Error: " . mysqli_error($con);
}
?>
