<?php
session_start();
include("connection.php");
include("admin_functions.php");

// Log logout before destroying session
if (isset($_SESSION['admin_id'])) {
    log_activity($con, 'Admin logged out', 'auth', $_SESSION['admin_username'] ?? 'unknown');
}

session_unset();
session_destroy();
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header("Location: ../pages/login_hub.php?portal=admin");
die;
