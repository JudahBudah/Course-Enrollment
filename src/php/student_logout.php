<?php
session_start();
unset($_SESSION["student_id"]);
header("Location: ../pages/student/student_login.php");
die;
