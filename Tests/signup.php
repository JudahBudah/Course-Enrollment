<?php 
    session_start();

    include("../src/php/connection.php");
    include("../src/php/functions.php");

    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        // something was posted
        $user_name = $_POST["user_name"];
        $password = $_POST["password"];

        if (!empty($user_name) && !empty($password) && !is_numeric($user_name)) {

            // save to database
            $user_id = random_num(10);
            $query = "insert into users (user_id, user_name, password) values ('$user_id', '$user_name', '$password')";

            mysqli_query($con, $query);

            header("Location: ../src/pages/student_login.php");
            die;
        }
        else {
            echo "Username or password in not valid";
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLM | Application</title>
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