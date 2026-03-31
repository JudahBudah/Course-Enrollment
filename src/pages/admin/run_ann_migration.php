<?php
include("../../php/connection.php");
mysqli_query($con, "ALTER TABLE announcements ADD COLUMN IF NOT EXISTS media JSON DEFAULT NULL AFTER message");
echo mysqli_error($con) ?: "Migration done.";
?>
