<?php
include("src/php/connection.php");

$ids = [13, 11, 6]; // BSIT, BS Arch, BSEE

foreach ($ids as $id) {
    // Remove subjects linked to this course
    mysqli_query($con, "DELETE FROM subjects WHERE course_id = $id");
    // Remove the course itself
    mysqli_query($con, "DELETE FROM courses WHERE course_id = $id");
    echo "Deleted course_id $id and its subjects.<br>";
}

echo "Done.";
?>
