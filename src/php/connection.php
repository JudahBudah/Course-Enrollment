<?php

    $dbhost = "localhost";
    $dbuser = "root";
    $dbpass = "";
    $dbname = "studentenrollment";

    if (!$con = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname)) {
        die ("Failed to connect");
    }