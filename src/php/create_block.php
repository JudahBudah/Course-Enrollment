<?php
session_start();
include("connection.php");
include("admin_functions.php");

$admin_data = check_admin_login($con);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $block_name = mysqli_real_escape_string($con, $_POST['block_name']);
    $course = mysqli_real_escape_string($con, $_POST['course']);
    $year_level = mysqli_real_escape_string($con, $_POST['year_level']);
    $semester = mysqli_real_escape_string($con, $_POST['semester']);
    $school_year = mysqli_real_escape_string($con, $_POST['school_year']);
    $max_students = (int)$_POST['max_students'];

    $query = "INSERT INTO blocks (block_name, course, year_level, semester, school_year, max_students, current_students, status) 
              VALUES ('$block_name', '$course', '$year_level', '$semester', '$school_year', $max_students, 0, 'active')";

    if (mysqli_query($con, $query)) {
        header("Location: ../pages/admin/admin_blocks.php?success=created");
    } else {
        header("Location: ../pages/admin/admin_blocks.php?error=failed");
    }
} else {
    header("Location: ../pages/admin/admin_blocks.php");
}
?>
