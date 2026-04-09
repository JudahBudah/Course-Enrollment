<?php
require_once __DIR__ . '/no_cache.php';

function check_applicant_login($con) 
{
    if (isset($_SESSION["applicant_id"])) 
    {
        $id = $_SESSION["applicant_id"];
        $stmt = mysqli_prepare($con, "SELECT * FROM applicants WHERE applicant_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) > 0)
        {
            $applicant_data = mysqli_fetch_assoc($result);
            return $applicant_data;
        }
    }

    header("Location: ../../pages/login_hub.php?portal=applicant");
    die;
}
?>
