<?php

    function check_login($con) 
    {
        if (isset($_SESSION["student_number"])) 
        {
            $sn = $_SESSION["student_number"];
            $stmt = mysqli_prepare($con, "SELECT * FROM students WHERE student_number = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "s", $sn);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) > 0)
            {
                $user_data = mysqli_fetch_assoc($result);
                return $user_data;
            }
        }

        header("Location: student_login.php");
        die;
    }


    function random_num($length) 
    {
        $text = "";
        if ($length < 5)
        {
            $length = 5;
        }

        $len = rand(4, $length);

        for ($i=0; $i < $len; $i++) { 
            $text .= rand(0, 9);
        }

        return $text;
    }
