<?php

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