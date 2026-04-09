<?php
session_start();
session_unset();
session_destroy();
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header("Location: ../pages/login_hub.php?portal=admin");
die;
