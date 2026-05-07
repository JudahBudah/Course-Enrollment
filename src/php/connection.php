<?php

    // Only set session cookie flags if session hasn't started yet
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', 1);
        // Uncomment the line below if your site runs on HTTPS
        // ini_set('session.cookie_secure', 1);
    }

    $dbhost = "localhost";
    $dbuser = "root";
    $dbpass = "";
    $dbname = "studentenrollment";

    if (!$con = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname)) {
        die ("Failed to connect");
    }

    // Ensure must_change_password column exists on students
    $_r = mysqli_query($con, "SHOW COLUMNS FROM students LIKE 'must_change_password'");
    if ($_r && mysqli_num_rows($_r) === 0) {
        mysqli_query($con, "ALTER TABLE students ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0");
    }