<?php
    session_start();

    include("../php/connection.php");
    include("../php/functions.php");

    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        // something was posted
        $user_name = $_POST["user_name"];
        $password = $_POST["password"];

        if (!empty($user_name) && !empty($password) && !is_numeric($user_name)) {

            // read from database
            $query = "select * from users where user_name = '$user_name' limit 1";

            $result = mysqli_query($con, $query);

            if ($result) {

                if ($result && mysqli_num_rows($result) > 0) 
                {
                    $user_data = mysqli_fetch_assoc($result);

                    if ($user_data["password"] === $password)
                    {
                        $_SESSION["user_id"] = $user_data["user_id"];
                        header("Location: student_home.php");
                        die;
                    }
                }
            }
            echo "Username or password is not valid";
        }

        else {
            echo "Username or password is not valid";
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLM | Student Login</title>
</head>
<body>
    <div>
        <form method="post">
            <input type="text" name="user_name">
            <input type="password" name="password">

            <input type="submit" value="Login">
        </form>
    </div>
</body>
</html>