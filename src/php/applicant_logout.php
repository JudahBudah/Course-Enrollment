<?php
session_start();

if (isset($_SESSION['applicant_id'])) {
    unset($_SESSION['applicant_id']);
}

session_destroy();
header("Location: ../pages/applicant/applicant_login.php");
die;
?>
