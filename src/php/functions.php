<?php
require_once __DIR__ . '/no_cache.php';

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

    header("Location: ../../pages/login_hub.php?portal=student");
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

    /**
     * Safely fetch a course row for a given course_code.
     * Only selects columns that actually exist in the courses table,
     * so pages don't crash if the migration hasn't been run yet.
     */
    function get_course_info($con, $course_code) {
        if (empty($course_code)) return null;

        // Detect which optional columns exist
        $optional = [
            'curriculum_url', 'description', 'program_objectives', 'career_opportunities',
            'college_description', 'college_history', 'college_vision',
            'college_mission', 'college_objectives', 'college_location', 'college_local_number',
        ];
        $cols_result = mysqli_query($con, "SHOW COLUMNS FROM courses");
        $existing = [];
        while ($col = mysqli_fetch_assoc($cols_result)) {
            $existing[] = $col['Field'];
        }
        $select = ['course_id', 'course_code', 'course_name', 'college_name', 'status'];
        foreach ($optional as $col) {
            if (in_array($col, $existing)) $select[] = $col;
        }

        $fields = implode(', ', $select);
        $stmt = mysqli_prepare($con, "SELECT $fields FROM courses WHERE course_code = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $course_code);
        mysqli_stmt_execute($stmt);
        return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    }
