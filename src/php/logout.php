<?php
session_start();

if (isset($_SESSION['student_number'])) {
    unset($_SESSION['student_number']);
}

session_destroy();
header("Location: ../pages/student_login.php");
die;
?>
