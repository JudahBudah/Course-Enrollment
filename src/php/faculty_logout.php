<?php
session_start();

if (isset($_SESSION['faculty_id'])) {
    unset($_SESSION['faculty_id']);
}

session_destroy();
header("Location:../pages/faculty/faculty_login.php");
die;
?>
